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

class SandstoneSlab extends StoneSlab{

	public function __construct(protected SandstoneType $material, int $meta = 0){
		if ($this->material->equals(SandstoneType::NORMAL())){
			$this->id = self::SANDSTONE_SLAB;
		} else {
			$this->id = "minecraft:" . $this->material->getType() . "_sandstone_slab";
		}

		parent::__construct($meta);
	}

	public function getName() : string{
		if ($this->material->equals(SandstoneType::NORMAL())){
			return "Sandstone Slab";
		}

		return $this->material->getName() . " Sandstone Slab";
	}

	public function getDoubleSlabId() : string{
		if ($this->material->equals(SandstoneType::NORMAL())){
			return  self::SANDSTONE_DOUBLE_SLAB;
		}

		return $this->id = "minecraft:" . $this->material->getType() . "_sandstone_double_slab";
	}

	public function getMaterial() : SandstoneType{
		return $this->material;
	}
}