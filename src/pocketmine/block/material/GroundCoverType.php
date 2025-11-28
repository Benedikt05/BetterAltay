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

class GroundCoverType extends Material{

	private static ?GroundCoverType $NORMAL = null;
	private static ?GroundCoverType $SHORT = null;
	private static ?GroundCoverType $FERN = null;

	public static function NORMAL() : GroundCoverType{
		return self::$NORMAL ??= new GroundCoverType("normal", "Normal");
	}

	public static function SHORT() : GroundCoverType{
		return self::$SHORT ??= new GroundCoverType("short", "Short");
	}

	public static function FERN() : GroundCoverType{
		return self::$FERN ??= new GroundCoverType("fern", "Fern");
	}

	/**
	 * @return GroundCoverType[]
	 */
	public static function values() : array{
		return [
			GroundCoverType::NORMAL(),
			GroundCoverType::SHORT(),
			GroundCoverType::FERN(),
		];
	}
}