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

namespace pocketmine\block;

use pocketmine\block\material\WoodType;

class WoodenPressurePlate extends StonePressurePlate{

	public function __construct(private WoodType $material, int $meta = 0){
		$type = $this->material->getType();
		if ($this->material->equals(WoodType::OAK())) {
			$type = "wooden";
		}
		$this->id = "minecraft:" . $type . "_pressure_plate";
		parent::__construct($meta);
	}


	public function getName() : string{
		return $this->material->getName() . " Pressure Plate";
	}

	public function getFuelTime() : int{
		return 300;
	}

	public function getToolType() : int{
		return BlockToolType::TYPE_AXE;
	}

	public function getToolHarvestLevel() : int{
		return 0; //TODO: fix hierarchy problem
	}

	/**
	 * @return WoodType
	 */
	public function getMaterial() : WoodType{
		return $this->material;
	}
}
