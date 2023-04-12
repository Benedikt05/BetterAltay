<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\entity\Effect;
use pocketmine\entity\Living;

class HoneyBottle extends Food{
	public function __construct(int $meta = 0){
		parent::__construct(self::HONEY_BOTTLE, $meta, "Honey Bottle");
	}

	public function getMaxStackSize() : int{
		return 16;
	}

	public function requiresHunger() : bool{
		return false;
	}

	public function getResidue() : Item{
		return ItemFactory::get(Item::GLASS_BOTTLE);
	}

	public function onConsume(Living $consumer) : void{
		$consumer->removeEffect(Effect::POISON);
	}

	public function getFoodRestore() : int{
		return 6;
	}

	public function getSaturationRestore() : float{
		return 1.2;
	}
}
