<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\network\mcpe\auth;

use pocketmine\network\mcpe\auth\validator\LegacyValidator;
use pocketmine\network\mcpe\auth\validator\OdicValidator;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\types\login\AuthenticationType;
use pocketmine\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use function is_array;
use function json_decode;
use function serialize;
use function unserialize;

class VerifyLoginTask extends AsyncTask{

	/** @var string */
	private $chainJwts;
	/** @var string */
	private $clientDataJwt;
	/** @var null|JwtToken */
	private ?JwtToken $token;
	/** @var int */
	private int $authType;

	/**
	 * @var string|null
	 * Whether the keychain signatures were validated correctly. This will be set to an error message if any link in the
	 * keychain is invalid for whatever reason (bad signature, not in nbf-exp window, etc). If this is non-null, the
	 * keychain might have been tampered with. The player will always be disconnected if this is non-null.
	 */
	private $error = "Unknown";
	/**
	 * @var bool
	 * Whether the player is logged into Xbox Live. This is true if any link in the keychain is signed with the Mojang
	 * root public key.
	 */
	private $authenticated = false;

	private function __construct(
		Player $player,
		LoginPacket $packet,
		private string $pem = "",
		private string $expectedIss = "",
	){
		$this->storeLocal([$player, $packet]);

		$chainJwtsExtracted = [];
		$certificateData = json_decode($packet->loginData["Certificate"], true);
		if(is_array($certificateData["chain"] ?? null)){
			$chainJwtsExtracted = $certificateData["chain"];
		}

		$this->token = $packet->token;
		$this->authType = $packet->loginData["AuthenticationType"];
		$this->chainJwts = serialize($chainJwtsExtracted);
		$this->clientDataJwt = $packet->clientDataJwt;
	}

	public static function verify(Player $player, LoginPacket $packet) : void{
		if(!isset($packet->loginData["Token"])){
			$player->getServer()->getAsyncPool()->submitTask(new self($player, $packet));
			return;
		}

		$kid = $packet->token->getHeader()["kid"] ?? "";
		$provider = JwkProvider::getInstance();

		$pem = $provider->getKey($kid, function(?string $pem) use ($packet, $kid, $provider, $player) : void{
			if($pem === null){
				$player->getServer()->getLogger()->warning("JWK: verification failed, no key available for kid $kid");
				$player->onVerifyCompleted($packet, null, false);
				return;
			}

			try{
				$player->getServer()->getAsyncPool()->submitTask(new self($player, $packet, $pem, $provider->getExpectedIssuer()));
			}catch(\Throwable $e){
				$player->getServer()->getLogger()->debug("Failed to schedule VerifyLoginTask: " . $e->getMessage());
				$player->onVerifyCompleted($packet, null, false);
			}
		});

		if($pem === null){
			$player->getServer()->getLogger()->debug("JWK: no cached key for kid $kid, verification deferred until keys applied");
			return;
		}

		try{
			$player->getServer()->getAsyncPool()->submitTask(new self($player, $packet, $pem, $provider->getExpectedIssuer()));
		}catch(\Throwable $e){
			$player->getServer()->getLogger()->debug("Failed to schedule VerifyLoginTask: " . $e->getMessage());
			$player->onVerifyCompleted($packet, null, false);
		}
	}

	public function onRun() : void{
		try{
			$validator = match (true) {
				$this->authType === AuthenticationType::FULL && $this->token !== null => new OdicValidator(
					$this->token,
					$this->clientDataJwt,
					$this->pem,
					$this->expectedIss
				),
				$this->authType === AuthenticationType::SELF_SIGNED => new LegacyValidator(
					unserialize($this->chainJwts),
					$this->clientDataJwt
				),
				default => throw new VerifyLoginException("Unknown or invalid authentication type: " . $this->authType),
			};

			$result = $validator->validate();
			$this->authenticated = $result->isAuthenticated();
			$this->error = $result->getError();
		}catch(VerifyLoginException $e){
			$this->error = $e->getMessage();
		}
	}

	public function onCompletion(Server $server){
		/**
		 * @var Player      $player
		 * @var LoginPacket $packet
		 */
		[$player, $packet] = $this->fetchLocal();
		if(!$player->isConnected()){
			$server->getLogger()->error("Player " . $player->getName() . " was disconnected before their login could be verified");
		}else{
			$player->onVerifyCompleted($packet, $this->error, $this->authenticated);
		}
	}
}
