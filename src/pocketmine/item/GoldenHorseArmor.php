<?php

declare(strict_types=1);

namespace pocketmine\item;

class GoldenHorseArmor extends Item{
	public function __construct(int $meta = 0){
		parent::__construct(self::GOLDEN_HORSE_ARMOR, $meta, "Golden Horse Armor");
	}

	public function getMaxStackSize() : int{
		return 1;
	}
}

