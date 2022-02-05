<?php

declare(strict_types=1);

namespace pocketmine\entity;

abstract class Animal extends Creature implements Ageable{

	public function isBaby() : bool{
		return $this->getGenericFlag(self::DATA_FLAG_BABY);
	}
}