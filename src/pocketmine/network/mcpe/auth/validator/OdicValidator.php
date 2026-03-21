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

namespace pocketmine\network\mcpe\auth\validator;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use pocketmine\network\mcpe\auth\JwtToken;
use pocketmine\network\mcpe\auth\VerifyLoginException;
use function chunk_split;
use function openssl_pkey_get_public;

class OdicValidator implements Validator{

	public const MOJANG_AUDIENCE = "api://auth-minecraft-services/multiplayer";

	public function __construct(
		private JwtToken $token,
		private string $clientDataJwt,
		private string $pem,
		private string $expectedIss,
	){ }

	public function validate() : ValidatorResult{
		try{
			$header = $this->token->getHeader();

			if(!isset($header["kid"], $header["alg"]) || $header["alg"] !== "RS256"){
				throw new VerifyLoginException("Unexpected JWT algorithm");
			}

			try{
				$publicKey = openssl_pkey_get_public($this->pem);
				$oidcData = JWT::decode($this->token->getJwt(), new Key($publicKey, 'RS256'));
			}catch(\Exception $e){
				throw new VerifyLoginException($e->getMessage(), 0, $e);
			}

			if(!isset($oidcData->aud) || $oidcData->aud !== self::MOJANG_AUDIENCE){
				throw new VerifyLoginException("Invalid audience");
			}

			if(!isset($oidcData->iss) || $oidcData->iss !== $this->expectedIss){
				throw new VerifyLoginException("Invalid issuer");
			}

			if(!isset($oidcData->cpk)){
				throw new VerifyLoginException("Missing client public key");
			}

			$pemKey = "-----BEGIN PUBLIC KEY-----\n" . chunk_split($oidcData->cpk, 64) . "-----END PUBLIC KEY-----";
			JWT::decode($this->clientDataJwt, new Key($pemKey, 'ES384'));

			return new ValidatorResult(true);
		} catch(\Exception $e) {
			return new ValidatorResult(false, $e->getMessage());
		}
	}
}