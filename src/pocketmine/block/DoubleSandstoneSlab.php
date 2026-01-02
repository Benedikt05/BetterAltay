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

use pocketmine\block\material\SandstoneType;

class DoubleSandstoneSlab extends DoubleSlab{

	public function __construct(protected SandstoneType $material, int $meta = 0){
		if ($this->material->equals(SandstoneType::NORMAL())){
			$this->id = self::SANDSTONE_DOUBLE_SLAB;
		} else {
			$this->id = "minecraft:" . $this->material->getType() . "_sandstone_double_slab";
		}

		$this->meta = $meta;
	}

	public function getName() : string{
		if ($this->material->equals(SandstoneType::NORMAL())){
			return "Sandstone Double Slab";
		}

		return $this->material->getName() . " Sandstone Double Slab";
	}

	public function getSlabId() : string{
		if ($this->material->equals(SandstoneType::NORMAL())){
			return self::SANDSTONE_SLAB;
		}

		return $this->id = "minecraft:" . $this->material->getType() . "_sandstone_slab";
	}
}