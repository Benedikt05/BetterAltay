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

namespace pocketmine\block\material;

class AnvilType extends Material{

	private static ?AnvilType $NORMAL = null;
	private static ?AnvilType $CHIPPED = null;
	private static ?AnvilType $CHISELED = null;

	public static function NORMAL() : AnvilType{
		return self::$NORMAL ??= new AnvilType("normal", "Normal");
	}

	public static function CHIPPED() : AnvilType{
		return self::$CHIPPED ??= new AnvilType("chipped", "Chipped");
	}

	public static function DAMAGED() : AnvilType{
		return self::$CHISELED ??= new AnvilType("damaged", "Damaged");
	}

	/**
	 * @return AnvilType[]
	 */
	public static function values() : array{
		return [
			AnvilType::NORMAL(),
			AnvilType::CHIPPED(),
			AnvilType::DAMAGED(),
		];
	}
}