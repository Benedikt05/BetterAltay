<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

final class PermissionLevel{

	private function __construct(){
		//NOOP
	}

	public const ANY = "any";
	public const GAMEDIRECTORS = "gamedirectors";
	public const ADMIN = "admin";
	public const HOST = "host";
	public const OWNER = "owner";
	public const INTERNAL = "internal";

}