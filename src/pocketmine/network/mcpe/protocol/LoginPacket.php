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

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\auth\JwtClaims;
use pocketmine\network\mcpe\auth\JwtToken;
use pocketmine\network\mcpe\JwtUtils;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\MainLogger;
use pocketmine\utils\Utils;
use pocketmine\utils\UUID;
use RuntimeException;
use Throwable;
use function get_class;
use function json_decode;

class LoginPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::LOGIN_PACKET;

	/** @var string */
	public string $username;
	/** @var int */
	public int $protocol;
	/** @var string */
	public string $clientUUID;
	/** @var int */
	public int $clientId;
	/** @var string|null */
	public ?string $xuid = null;
	/** @var string */
	public string $identityPublicKey;
	/** @var string */
	public string $serverAddress;
	/** @var string */
	public string $locale;

	/**
	 * @var array<string, mixed> Raw login data from the client
	 * @phpstan-var array{
	 *   AuthenticationType: int,
	 *   Certificate: string,
	 *   Token: string
	 * }
	 */
	public array $loginData = [];
	/** @var string */
	public string $clientDataJwt;
	/**
	 * @var mixed[] decoded payload of the clientData JWT
	 * @phpstan-var array<string, mixed>
	 */
	public array $clientData = [];

	/**
	 * This field may be used by plugins to bypass keychain verification. It should only be used for plugins such as
	 * Specter where passing verification would take too much time and not be worth it.
	 *
	 * @var bool
	 */
	public bool $skipVerification = false;

	public function canBeSentBeforeLogin() : bool{
		return true;
	}

	public function mayHaveUnreadBytes() : bool{
		return $this->protocol !== ProtocolInfo::CURRENT_PROTOCOL;
	}

	protected function decodePayload() : void{
		$this->protocol = $this->getInt();

		try{
			$this->decodeConnectionRequest();
		}catch(Throwable $e){
			if($this->protocol === ProtocolInfo::CURRENT_PROTOCOL){
				throw $e;
			}

			$logger = MainLogger::getLogger();
			$logger->debug(get_class($e) . " was thrown while decoding connection request in login (protocol version $this->protocol): " . $e->getMessage());
			foreach(Utils::printableTrace($e->getTrace()) as $line){
				$logger->debug($line);
			}
		}
	}

	protected function decodeConnectionRequest() : void{
		$buffer = new BinaryStream($this->getString());

		$this->loginData = json_decode($buffer->get($buffer->getLInt()), true);

		$chainArray = null;

		if(isset($this->loginData["Certificate"]) && is_string($this->loginData["Certificate"])){
			$certificateData = json_decode($this->loginData["Certificate"], true);
			if(isset($certificateData["chain"]) && is_array($certificateData["chain"])){
				$chainArray = $certificateData["chain"];
			}
		}

		if(is_array($chainArray)){
			var_dump("legacy auth");
			$hasExtraData = false;
			foreach($chainArray as $chain){
				$webtoken = Utils::decodeJWT($chain);
				if(isset($webtoken["extraData"])){
					if($hasExtraData){
						throw new RuntimeException("Found 'extraData' multiple times in key chain");
					}
					$hasExtraData = true;

					$this->username = $webtoken["extraData"]["displayName"] ?? $this->username;
					$this->clientUUID = $webtoken["extraData"]["identity"] ?? $this->clientUUID;
					$this->xuid = $webtoken["extraData"]["XUID"] ?? $this->xuid;
				}

				if(isset($webtoken["identityPublicKey"])){
					$this->identityPublicKey = $webtoken["identityPublicKey"];
				}
			}
		}elseif(isset($this->loginData["Token"])){
			try{
				$authToken = $this->loginData["Token"];
				[$header, $claimsArray,] = JwtUtils::parse($authToken);
				$claims = new JwtClaims($claimsArray);
				$token = new JwtToken($header, $claims);
				//var_dump($token->header);

				$this->username = $claims->get("xname") ?? $this->username;

				if(($xid = $claims->get("xid")) !== null){
					$this->clientUUID = UUID::fromXuid($xid)->toString();
					$this->xuid = $xid;
				}

				$this->identityPublicKey = $claims->get("cpk") ?? $this->identityPublicKey;
			}catch(\Throwable $e){
				var_dump("Could not parse token: " . $e->getMessage());
			}
		}else{
			var_dump("Neither Certificate nor Token field found");
		}

		$this->clientDataJwt = $buffer->get($buffer->getLInt());
		$this->clientData = Utils::decodeJWT($this->clientDataJwt);

		$this->clientId = $this->clientData["ClientRandomId"] ?? null;
		$this->serverAddress = $this->clientData["ServerAddress"] ?? null;

		$this->locale = $this->clientData["LanguageCode"] ?? null;
	}

	public function getAuthenticationType() : int{
		return $this->loginData["AuthenticationType"];
	}
	
	protected function encodePayload() : void{
		//TODO
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleLogin($this);
	}
}
