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

namespace pocketmine\block\material;

class WoodType extends Material{

	private static ?self $OAK = null;
	private static ?self $SPRUCE = null;
	private static ?self $BIRCH = null;
	private static ?self $JUNGLE = null;
	private static ?self $ACACIA = null;
	private static ?self $DARK_OAK = null;
	private static ?self $MANGROVE = null;
	private static ?self $CHERRY = null;
	private static ?self $PALE_OAK = null;

	public static function OAK() : self{
		return self::$OAK ??= new self("oak", "Oak");
	}

	public static function SPRUCE() : self{
		return self::$SPRUCE ??= new self("spruce", "Spruce");
	}

	public static function BIRCH() : self{
		return self::$BIRCH ??= new self("birch", "Birch");
	}

	public static function JUNGLE() : self{
		return self::$JUNGLE ??= new self("jungle", "Jungle");
	}

	public static function ACACIA() : self{
		return self::$ACACIA ??= new self("acacia", "Acacia");
	}

	public static function DARK_OAK() : self{
		return self::$DARK_OAK ??= new self("dark_oak", "Dark Oak");
	}

	public static function MANGROVE() : self{
		return self::$MANGROVE ??= new self("mangrove", "Mangrove");
	}

	public static function CHERRY() : self{
		return self::$CHERRY ??= new self("cherry", "Cherry");
	}

	public static function PALE_OAK() : self{
		return self::$PALE_OAK ??= new self("pale_oak", "Pale Oak");
	}

	/**
	 * @return WoodType[]
	 */
	public static function values() : array{
		return [
			self::OAK(),
			self::SPRUCE(),
			self::BIRCH(),
			self::JUNGLE(),
			self::ACACIA(),
			self::DARK_OAK(),
			self::MANGROVE(),
			self::CHERRY(),
			self::PALE_OAK(),
		];
	}
}
