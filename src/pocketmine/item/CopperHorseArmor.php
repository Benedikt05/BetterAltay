<?php

declare(strict_types=1);

namespace pocketmine\item;

class CopperHorseArmor extends Item{
	public function __construct(int $meta = 0){
		parent::__construct(self::COPPER_HORSE_ARMOR, $meta, "Copper Horse Armor");
	}

	public function getMaxStackSize() : int{
		return 1;
	}
}

