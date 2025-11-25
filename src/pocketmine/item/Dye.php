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

use pocketmine\block\material\ColorType;
use pocketmine\utils\Color;

class Dye extends Item{
	public function __construct(private ColorType $material, int $meta = 0){
		parent::__construct("minecraft:" . $this->material->getType() . "_dye", $meta, "Dye");
	}

	public function getColor() : Color{
		return match ($this->id) {
			default => new Color(0xf0, 0xf0, 0xf0),
			ItemIds::RED_DYE => new Color(0xf9, 0x80, 0x1d),
			ItemIds::GREEN_DYE => new Color(0xc7, 0x4e, 0xbd),
			ItemIds::BROWN_DYE => new Color(0x3a, 0xb3, 0xda),
			ItemIds::BLUE_DYE => new Color(0xfe, 0xd8, 0x3d),
			ItemIds::PURPLE_DYE => new Color(0x80, 0xc7, 0x1f),
			ItemIds::CYAN_DYE => new Color(0xf3, 0x8b, 0xaa),
			ItemIds::LIGHT_GRAY_DYE => new Color(0x47, 0x4f, 0x52),
			ItemIds::GRAY_DYE => new Color(0x9d, 0x9d, 0x97),
			ItemIds::PINK_DYE => new Color(0x16, 0x9c, 0x9c),
			ItemIds::LIME_DYE => new Color(0x89, 0x32, 0xb8),
			ItemIds::YELLOW_DYE => new Color(0x3c, 0x44, 0xaa),
			ItemIds::LIGHT_BLUE_DYE => new Color(0x83, 0x54, 0x32),
			ItemIds::MAGENTA_DYE => new Color(0x5e, 0x7c, 0x16),
			ItemIds::ORANGE_DYE => new Color(0xb0, 0x2e, 0x26),
			ItemIds::WHITE_DYE => new Color(0x1d, 0x1d, 0x21)
		};
	}
}
