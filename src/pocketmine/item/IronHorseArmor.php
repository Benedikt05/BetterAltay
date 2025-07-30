<?php

declare(strict_types=1);

namespace pocketmine\item;

class IronHorseArmor extends Item{
	public function __construct(int $meta = 0){
		parent::__construct(self::IRON_HORSE_ARMOR, $meta, "Iron Horse Armor");
	}

	public function getMaxStackSize() : int{
		return 1;
	}
}

