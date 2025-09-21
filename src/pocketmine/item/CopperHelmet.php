<?php

declare(strict_types=1);

namespace pocketmine\item;

class CopperHelmet extends Armor{
	public function __construct(int $meta = 0){
		parent::__construct(self::COPPER_HELMET, $meta, "Copper Helmet");
	}

	public function getDefensePoints() : int{
		return 2;
	}

	public function getMaxDurability() : int{
		return 122;
	}

	public function getArmorSlot() : int{
		return 0;
	}
	
	public function getTier() : int{
		return self::TIER_COPPER;
	}
}
