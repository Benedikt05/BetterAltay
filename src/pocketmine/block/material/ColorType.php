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

class ColorType extends Material{

	private static ?ColorType $WHITE = null;
	private static ?ColorType $ORANGE = null;
	private static ?ColorType $MAGENTA = null;
	private static ?ColorType $LIGHT_BLUE = null;
	private static ?ColorType $YELLOW = null;
	private static ?ColorType $LIME = null;
	private static ?ColorType $PINK = null;
	private static ?ColorType $GRAY = null;
	private static ?ColorType $LIGHT_GRAY = null;
	private static ?ColorType $CYAN = null;
	private static ?ColorType $PURPLE = null;
	private static ?ColorType $BLUE = null;
	private static ?ColorType $BROWN = null;
	private static ?ColorType $GREEN = null;
	private static ?ColorType $RED = null;
	private static ?ColorType $BLACK = null;

	public static function WHITE() : ColorType{
		return self::$WHITE ??= new ColorType("white", "White");
	}

	public static function ORANGE() : ColorType{
		return self::$ORANGE ??= new ColorType("orange", "Orange");
	}

	public static function MAGENTA() : ColorType{
		return self::$MAGENTA ??= new ColorType("magenta", "Magenta");
	}

	public static function LIGHT_BLUE() : ColorType{
		return self::$LIGHT_BLUE ??= new ColorType("light_blue", "Light Blue");
	}

	public static function YELLOW() : ColorType{
		return self::$YELLOW ??= new ColorType("yellow", "Yellow");
	}

	public static function LIME() : ColorType{
		return self::$LIME ??= new ColorType("lime", "Lime");
	}

	public static function PINK() : ColorType{
		return self::$PINK ??= new ColorType("pink", "Pink");
	}
	public static function GRAY() : ColorType{
		return self::$GRAY ??= new ColorType("gray", "Gray");
	}

	public static function LIGHT_GRAY() : ColorType{
		return self::$LIGHT_GRAY ??= new ColorType("light_gray", "Light Gray");
	}

	public static function CYAN() : ColorType{
		return self::$CYAN ??= new ColorType("cyan", "Cyan");
	}

	public static function PURPLE() : ColorType{
		return self::$PURPLE ??= new ColorType("purple", "Purple");
	}

	public static function BLUE() : ColorType{
		return self::$BLUE ??= new ColorType("blue", "Blue");
	}

	public static function BROWN() : ColorType{
		return self::$BROWN ??= new ColorType("brown", "Brown");
	}

	public static function GREEN() : ColorType{
		return self::$GREEN ??= new ColorType("green", "Green");
	}

	public static function RED() : ColorType{
		return self::$RED ??= new ColorType("red", "Red");
	}

	public static function BLACK() : ColorType{
		return self::$BLACK ??= new ColorType("black", "Black");
	}

	/**
	 * @return ColorType[]
	 */
	public static function values() : array{
		return [
			self::WHITE(),
			self::ORANGE(),
			self::MAGENTA(),
			self::LIGHT_BLUE(),
			self::YELLOW(),
			self::LIME(),
			self::PINK(),
			self::GRAY(),
			self::LIGHT_GRAY(),
			self::CYAN(),
			self::PURPLE(),
			self::BLUE(),
			self::BROWN(),
			self::GREEN(),
			self::RED(),
			self::BLACK(),
		];
	}
}