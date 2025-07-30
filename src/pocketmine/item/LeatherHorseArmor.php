<?php

declare(strict_types=1);

namespace pocketmine\item;

class LeatherHorseArmor extends Item{
	public function __construct(int $meta = 0){
		parent::__construct(self::LEATHER_HORSE_ARMOR, $meta, "Leather Horse Armor");
	}

	public function getMaxStackSize() : int{
		return 1;
	}
}

