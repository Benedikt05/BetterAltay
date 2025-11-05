<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\auth;

/**
 * Represents a fully parsed JSON Web Token (JWT) containing the header and claims.
 */
class JwtToken{

	public function __construct(
		/** * @var array JWT Header data. Contains critical metadata for token validation.
		 * @phpstan-var array<string, mixed>
		 *
		 * Key:
		 * - 'alg'
		 * - 'typ'
		 * - 'kid'
		 */
		public array $header,
		public JwtClaims $claims
	){}

	public function getHeader() : array{
		return $this->header;
	}

	public function getClaims() : JwtClaims{
		return $this->claims;
	}
}