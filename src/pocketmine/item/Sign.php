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

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockNames;
use pocketmine\block\material\WoodType;

class Sign extends Item{
	public function __construct(protected WoodType $material, $meta = 0){
		parent::__construct("minecraft:" . $material->getType() . "_sign", $meta, "Sign");
	}

	public function getBlock() : Block{
		return BlockFactory::get("minecraft:" . self::resolveWoodPrefix($this->material) . "standing_sign");
	}

	public function getMaxStackSize() : int{
		return 16;
	}

	/**
	 * @return WoodType
	 */
	public function getMaterial() : WoodType{
		return $this->material;
	}

	public static function resolveWoodPrefix(WoodType $material) : string{
		if ($material->equals(WoodType::OAK())) {
			return "";
		}
		if ($material->equals(WoodType::DARK_OAK())) {
			return "darkoak_";
		}

		return $material->getType() . "_";
	}
}
