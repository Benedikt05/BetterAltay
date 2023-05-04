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

use pocketmine\utils\Color;

class Dye extends Item{
	public function __construct(int $meta = 0){
		parent::__construct(self::DYE, $meta, "Dye");
	}

	public function getColorFromMeta() : Color{
		return match ($this->meta) {
			default => new Color(0xf0, 0xf0, 0xf0),
			1 => new Color(0xf9, 0x80, 0x1d),
			2 => new Color(0xc7, 0x4e, 0xbd),
			3 => new Color(0x3a, 0xb3, 0xda),
			4 => new Color(0xfe, 0xd8, 0x3d),
			5 => new Color(0x80, 0xc7, 0x1f),
			6 => new Color(0xf3, 0x8b, 0xaa),
			7 => new Color(0x47, 0x4f, 0x52),
			8 => new Color(0x9d, 0x9d, 0x97),
			9 => new Color(0x16, 0x9c, 0x9c),
			10 => new Color(0x89, 0x32, 0xb8),
			11 => new Color(0x3c, 0x44, 0xaa),
			12 => new Color(0x83, 0x54, 0x32),
			13 => new Color(0x5e, 0x7c, 0x16),
			14 => new Color(0xb0, 0x2e, 0x26),
			15 => new Color(0x1d, 0x1d, 0x21)
		};
	}
}
