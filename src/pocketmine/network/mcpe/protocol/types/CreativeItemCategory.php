<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

final class CreativeItemCategory{

	private function __construct(){
		//NOOP
	}

	public const ALL = 0;
	public const CONSTRUCTION = 1;
	public const NATURE = 2;
	public const EQUIPMENT = 3;
	public const ITEMS = 4;
	public const ITEM_COMMAND_ONLY = 5;
	public const UNDEFINED = 6;
}
