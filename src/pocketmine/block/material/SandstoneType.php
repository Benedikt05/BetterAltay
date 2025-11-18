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

class SandstoneType extends Material{

	private static ?SandstoneType $NORMAL = null;
	private static ?SandstoneType $CUT = null;
	private static ?SandstoneType $CHISELED = null;
	private static ?SandstoneType $SMOOTH = null;

	public static function NORMAL() : SandstoneType{
		return self::$NORMAL ??= new SandstoneType("normal", "Normal");
	}

	public static function CUT() : SandstoneType{
		return self::$CUT ??= new SandstoneType("cut", "Cut");
	}

	public static function CHISELED() : SandstoneType{
		return self::$CHISELED ??= new SandstoneType("chiseled", "Chiseled");
	}

	public static function SMOOTH() : SandstoneType{
		return self::$SMOOTH ??= new SandstoneType("smooth", "Smooth");
	}

	/**
	 * @return SandstoneType[]
	 */
	public static function values() : array{
		return [
			SandstoneType::NORMAL(),
			SandstoneType::CUT(),
			SandstoneType::CHISELED(),
			SandstoneType::SMOOTH(),
		];
	}
}