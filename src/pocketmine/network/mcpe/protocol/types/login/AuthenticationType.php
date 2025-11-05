<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types\login;

class AuthenticationType{

	private function __construct(){
		//NOOP
	}

	public const FULL = 0;
	public const GUEST = 1;
	public const SELF_SIGNED = 2;

}