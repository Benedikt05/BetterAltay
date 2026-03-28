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

namespace pocketmine\network\mcpe\auth\task;

use Firebase\JWT\JWT;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Internet;
use function chr;
use function json_decode;
use function ltrim;
use function pack;

class FetchJwksTask extends AsyncTask{

	public const DISCOVERY_BASE = "https://client.discovery.minecraft-services.net/api/v1.0/discovery/MinecraftPE/builds/";
	public const CONFIGURATION_PATH = "/.well-known/openid-configuration";
	public const FALLBACK_URL = "https://authorization.franchise.minecraft-services.net/.well-known/keys";

	private string $minecraftVersion;

	public function __construct(string $minecraftVersion, \Closure $onComplete){
		$this->minecraftVersion = $minecraftVersion;
		$this->storeLocal($onComplete);
	}

	public function onRun() : void{
		try{
			$discoveryUrl = self::DISCOVERY_BASE . $this->minecraftVersion;
			$err = null;
			$httpCode = null;
			$discoveryRaw = Internet::getURL($discoveryUrl, 10, [], $err, $headers, $httpCode);

			if($discoveryRaw === false || $httpCode !== 200){
				throw new \RuntimeException("Discovery HTTP $httpCode: " . ($err ?? "Unknown"));
			}
			$discovery = json_decode($discoveryRaw, true);
			$serviceUri = $discovery['result']['serviceEnvironments']['auth']['prod']['serviceUri'] ?? null;
			if($serviceUri === null){
				throw new \RuntimeException("Missing serviceUri in discovery");
			}

			$configRaw = Internet::getURL($serviceUri . self::CONFIGURATION_PATH, 10, [], $err, $headers, $httpCode);
			if($configRaw === false || $httpCode !== 200){
				throw new \RuntimeException("Config HTTP $httpCode: " . ($err ?? "Unknown"));
			}

			$config = json_decode($configRaw, true);
			$jwksUri = $config['jwks_uri'] ?? null;
			if($jwksUri === null){
				throw new \RuntimeException("Missing jwks_uri in config");
			}

			$jwksRaw = Internet::getURL($jwksUri, 10, [], $err, $headers, $httpCode);
			if($jwksRaw === false || $httpCode !== 200){
				$jwksRaw = Internet::getURL(self::FALLBACK_URL, 10, [], $err, $headers, $httpCode);
				if($jwksRaw === false || $httpCode !== 200){
					throw new \RuntimeException("JWKS fetch failed");
				}
			}

			$jwks = json_decode($jwksRaw, true);
			if(empty($jwks['keys'])){
				throw new \RuntimeException("Empty JWK response");
			}

			$newKeys = [];
			foreach($jwks['keys'] as $jwk){
				$newKeys[$jwk['kid']] = self::createPemFromModulusAndExponent($jwk['n'], $jwk['e']);
			}

			$this->setResult(['keys' => $newKeys, 'issuer' => $config['issuer'] ?? '']);
		}catch(\Throwable $e){
			$this->setResult(['error' => $e->getMessage()]);
		}
	}

	// https://github.com/firebase/php-jwt/blob/28aa0694bcfdfa5e2959c394d5a1ee7a5083629e/src/JWK.php#L196
	protected static function createPemFromModulusAndExponent(
		string $n,
		string $e
	) : string{
		$mod = JWT::urlsafeB64Decode($n);
		$exp = JWT::urlsafeB64Decode($e);

		$modulus = pack('Ca*a*', 2, self::encodeLength(\strlen($mod)), $mod);
		$publicExponent = pack('Ca*a*', 2, self::encodeLength(\strlen($exp)), $exp);

		$rsaPublicKey = pack(
			'Ca*a*a*',
			48,
			self::encodeLength(\strlen($modulus) + \strlen($publicExponent)),
			$modulus,
			$publicExponent
		);

		// sequence(oid(1.2.840.113549.1.1.1), null)) = rsaEncryption.
		$rsaOID = pack('H*', '300d06092a864886f70d0101010500'); // hex version of MA0GCSqGSIb3DQEBAQUA
		$rsaPublicKey = chr(0) . $rsaPublicKey;
		$rsaPublicKey = chr(3) . self::encodeLength(\strlen($rsaPublicKey)) . $rsaPublicKey;

		$rsaPublicKey = pack(
			'Ca*a*',
			48,
			self::encodeLength(\strlen($rsaOID . $rsaPublicKey)),
			$rsaOID . $rsaPublicKey
		);

		return "-----BEGIN PUBLIC KEY-----\r\n" .
			\chunk_split(\base64_encode($rsaPublicKey), 64) .
			'-----END PUBLIC KEY-----';
	}

	protected static function encodeLength(int $length) : string{
		if($length <= 0x7F){
			return chr($length);
		}

		$temp = ltrim(pack('N', $length), chr(0));
		return pack('Ca*', 0x80 | \strlen($temp), $temp);
	}

	public function onCompletion(Server $server) : void{
		$res = $this->getResult();
		if(isset($res['error'])){
			$server->getLogger()->warning("FetchJwksTask failed: " . $res['error']);
			return;
		}

		$onComplete = $this->fetchLocal();
		$onComplete($res['keys'], $res['issuer']);
	}
}