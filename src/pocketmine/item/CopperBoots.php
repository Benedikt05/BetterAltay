<?php

declare(strict_types=1);

namespace pocketmine\item;

class CopperBoots extends Armor{
	public function __construct(int $meta = 0){
		parent::__construct(self::COPPER_BOOTS, $meta, "Copper Boots");
	}

	public function getDefensePoints() : int{
		return 1;
	}

	public function getMaxDurability() : int{
		return 144;
	}

	public function getArmorSlot() : int{
		return 3;
	}
}
