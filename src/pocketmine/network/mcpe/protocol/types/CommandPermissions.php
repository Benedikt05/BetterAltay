<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

final class CommandPermissions{
	public const NORMAL = 0;
	public const OPERATOR = 1;
	public const AUTOMATION = 2;
	public const HOST = 3;
	public const OWNER = 4;
	public const INTERNAL = 5;
}