<?php

declare(strict_types=1);

namespace pocketmine\item;

class CopperLeggings extends Armor{
	public function __construct(int $meta = 0){
		parent::__construct(self::COPPER_LEGGINGS, $meta, "Copper Leggings");
	}

	public function getDefensePoints() : int{
		return 3;
	}

	public function getMaxDurability() : int{
		return 166;
	}

	public function getArmorSlot() : int{
		return 2;
	}
}
