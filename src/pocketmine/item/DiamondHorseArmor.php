<?php

declare(strict_types=1);

namespace pocketmine\item;

class DiamondHorseArmor extends Item{
	public function __construct(int $meta = 0){
		parent::__construct(self::DIAMOND_HORSE_ARMOR, $meta, "Diamond Horse Armor");
	}

	public function getMaxStackSize() : int{
		return 1;
	}
}

