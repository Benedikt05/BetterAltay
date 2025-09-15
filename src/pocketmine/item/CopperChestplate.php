<?php

declare(strict_types=1);

namespace pocketmine\item;

class CopperChestplate extends Armor{
	public function __construct(int $meta = 0){
		parent::__construct(self::COPPER_CHESTPLATE, $meta, "Copper Chestplate");
	}

	public function getDefensePoints() : int{
		return 4;
	}

	public function getMaxDurability() : int{
		return 177;
	}

	public function getArmorSlot() : int{
		return 1;
	}

	public function getTier() : int{
		return self::TIER_COPPER;
	}
}
