<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\auth;

use pocketmine\network\mcpe\JwtUtils;

/**
 * Represents a parsed JSON Web Token (JWT).
 *
 * A JWT is a compact token containing a header, payload (claims), and signature.
 * This class provides easy access to each part of the token.
 */
class JwtToken{

	private string $jwt;

	/** @var array<string, mixed> */
	private array $header;
	private ?JwtClaims $claims = null;
	private string $signature;

	/**
	 * Use JwtToken::parse() to create a new instance.
	 */
	private function __construct(){ }

	/**
	 * Parses a raw JWT string into a JwtToken instance.
	 *
	 * @param string $jwt The raw JWT string (header.payload.signature)
	 *
	 * @return self A JwtToken instance with parsed header, claims, and signature
	 */
	public static function parse(string $jwt) : self{
		[$header, $body, $signature] = JwtUtils::parse($jwt);

		$token = new self;
		$token->jwt = $jwt;
		$token->header = $header;
		$token->claims = new JwtClaims($body);
		$token->signature = $signature;

		return $token;
	}

	/**
	 * Returns the parsed JWT claims (payload).
	 */
	public function getClaims() : JwtClaims{
		return $this->claims;
	}

	/**
	 * Returns the decoded JWT header.
	 */
	public function getHeader() : array{
		return $this->header;
	}

	/**
	 * Returns the JWT signature part.
	 */
	public function getSignature() : string{
		return $this->signature;
	}

	/**
	 * Returns the original raw JWT string
	 */
	public function getJwt() : string{
		return $this->jwt;
	}
}