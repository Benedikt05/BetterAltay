<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\auth;

/**
 * Contains the decoded claims (payload data) of a JWT.
 * Represents the full set of claims guaranteed by the token issuer.
 */
class JwtClaims{
	/**
	 * @param array<string, mixed> $rawClaims
	 */
	public function __construct(
		/** * @var array<string, mixed> Internal storage for raw claim data
		 *
		 * Claims:
		 * - 'sub'
		 * - 'ipt'
		 * - 'iat'
		 * - 'mid'
		 * - 'tid'
		 * - 'pfcd':
		 * - 'cpk'
		 * - 'xid'
		 * - 'xname':
		 * - 'exp'
		 * - 'iss'
		 * - 'aud'
		 */
		private array $rawClaims
	){
	}

	/**
	 * Returns all raw claims as an associative array.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray() : array{
		return $this->rawClaims;
	}

	/**
	 * Retrieves a specific claim value.
	 *
	 * @param string $key The name of the claim (e.g., 'xname', 'cpk').
	 *
	 * @return mixed|null The claim's value or null if not present.
	 */
	public function get(string $key) : mixed{
		return $this->rawClaims[$key] ?? null;
	}
}