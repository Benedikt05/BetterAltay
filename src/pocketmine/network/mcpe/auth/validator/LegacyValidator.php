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

use pocketmine\network\mcpe\auth\VerifyLoginException;
use function base64_decode;
use function chr;
use function count;
use function explode;
use function json_decode;
use function ltrim;
use function ord;
use function str_split;
use function strlen;
use function strtr;
use function time;
use function wordwrap;

class LegacyValidator implements Validator{
	public const MOJANG_ROOT_PUBLIC_KEY = "MHYwEAYHKoZIzj0CAQYFK4EEACIDYgAECRXueJeTDqNRRgJi/vlRufByu/2G0i2Ebt6YMar5QX/R0DIIyrJMcUpruK4QveTfJSTp3Shlq4Gk34cD/4GUWwkv0DVuzeuB+tXija7HBxii03NHDbPAD0AKnLr2wdAp";

	private const CLOCK_DRIFT_MAX = 60;

	private bool $authenticated = false;

	/**
	 * @param string[]  $chainsJwts
	 * @param string $clientDataJwt
	 */
	public function __construct(private array $chainJwts, private string $clientDataJwt){

	}

	public function validate() : ValidatorResult{
		try{
			$currentKey = null;
			$first = true;

			foreach($this->chainJwts as $jwt){
				$this->validateToken($jwt, $currentKey, $first);
				$first = false;
			}

			$this->validateToken($this->clientDataJwt, $currentKey);

			return new ValidatorResult($this->authenticated);
		} catch(\Exception $e){
			return new ValidatorResult(false, $e->getMessage());
		}
	}

	private function validateToken(string $jwt, ?string &$currentPublicKey, bool $first = false) : void{
		$rawParts = explode('.', $jwt, 3);
		if(count($rawParts) !== 3){
			throw new VerifyLoginException("Wrong number of JWT parts, expected 3, got " . count($rawParts));
		}
		[$headB64, $payloadB64, $sigB64] = $rawParts;

		$headers = json_decode(base64_decode(strtr($headB64, '-_', '+/'), true), true);

		if($currentPublicKey === null){
			if(!$first){
				throw new VerifyLoginException("%pocketmine.disconnect.invalidSession.missingKey");
			}

			//First link, check that it is self-signed
			$currentPublicKey = $headers["x5u"];
		}elseif($headers["x5u"] !== $currentPublicKey){
			//Fast path: if the header key doesn't match what we expected, the signature isn't going to validate anyway
			throw new VerifyLoginException("%pocketmine.disconnect.invalidSession.badSignature");
		}

		$plainSignature = base64_decode(strtr($sigB64, '-_', '+/'), true);

		//OpenSSL wants a DER-encoded signature, so we extract R and S from the plain signature and crudely serialize it.

		if(strlen($plainSignature) !== 96){
			throw new VerifyLoginException("Wrong signature length, expected 96, got " . strlen($plainSignature));
		}

		[$rString, $sString] = str_split($plainSignature, 48);

		$rString = ltrim($rString, "\x00");
		if(ord($rString[0]) >= 128){ //Would be considered signed, pad it with an extra zero
			$rString = "\x00" . $rString;
		}

		$sString = ltrim($sString, "\x00");
		if(ord($sString[0]) >= 128){ //Would be considered signed, pad it with an extra zero
			$sString = "\x00" . $sString;
		}

		//0x02 = Integer ASN.1 tag
		$sequence = "\x02" . chr(strlen($rString)) . $rString . "\x02" . chr(strlen($sString)) . $sString;
		//0x30 = Sequence ASN.1 tag
		$derSignature = "\x30" . chr(strlen($sequence)) . $sequence;

		$v = openssl_verify("$headB64.$payloadB64", $derSignature, "-----BEGIN PUBLIC KEY-----\n" . wordwrap($currentPublicKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----\n", OPENSSL_ALGO_SHA384);
		if($v !== 1){
			throw new VerifyLoginException("%pocketmine.disconnect.invalidSession.badSignature");
		}

		if($currentPublicKey === self::MOJANG_ROOT_PUBLIC_KEY){
			$this->authenticated = true; //we're signed into xbox live
		}

		$claims = json_decode(base64_decode(strtr($payloadB64, '-_', '+/'), true), true);

		$time = time();
		if(isset($claims["nbf"]) and $claims["nbf"] > $time + self::CLOCK_DRIFT_MAX){
			throw new VerifyLoginException("%pocketmine.disconnect.invalidSession.tooEarly");
		}

		if(isset($claims["exp"]) and $claims["exp"] < $time - self::CLOCK_DRIFT_MAX){
			throw new VerifyLoginException("%pocketmine.disconnect.invalidSession.tooLate");
		}

		$currentPublicKey = $claims["identityPublicKey"] ?? null; //if there are further links, the next link should be signed with this
	}
}