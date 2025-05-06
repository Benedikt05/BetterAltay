<?php

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Human;
use pocketmine\entity\Living;

class TurtleHelmet extends Armor{
	public function __construct(int $meta = 0){
		parent::__construct(self::TURTLE_HELMET, $meta, "Turtle Helmet");
	}

	public function getMaxDurability() : int{
		return 276;
	}

	public function getArmorSlot() : int{
		return self::SLOT_HELMET;
	}

	public function getDefensePoints() : int{
		return 2;
	}

	public function onTickWorn(Living $entity) : bool{
		if($entity instanceof Human && !$entity->isUnderwater()){
			$entity->addEffect(new EffectInstance(Effect::getEffect(Effect::WATER_BREATHING), 200, 0, false));
			return true;
		}

		return false;
	}
}
