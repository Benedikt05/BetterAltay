<?php

declare(strict_types=1);

namespace pocketmine\block\material;

class StoneType extends Material{

	private static ?StoneType $NORMAL = null;
	private static ?StoneType $GRANITE = null;
	private static ?StoneType $POLISHED_GRANITE = null;
	private static ?StoneType $DIORITE = null;
	private static ?StoneType $POLISHED_DIORITE = null;
	private static ?StoneType $ANDESITE = null;
	private static ?StoneType $POLISHED_ANDESITE = null;

	public static function NORMAL() : StoneType{
		return self::$NORMAL ??= new StoneType("stone", "Stone");
	}

	public static function GRANITE() : StoneType{
		return self::$GRANITE ??= new StoneType("granite", "Granite");
	}

	public static function POLISHED_GRANITE() : StoneType{
		return self::$POLISHED_GRANITE ??= new StoneType("polished_granite", "Polished Granite");
	}

	public static function DIORITE() : StoneType{
		return self::$DIORITE ??= new StoneType("diorite", "Diorite");
	}

	public static function POLISHED_DIORITE() : StoneType{
		return self::$POLISHED_DIORITE ??= new StoneType("polished_diorite", "Polished Diorite");
	}

	public static function ANDESITE() : StoneType{
		return self::$ANDESITE ??= new StoneType("andesite", "Andesite");
	}

	public static function POLISHED_ANDESITE() : StoneType{
		return self::$POLISHED_ANDESITE ??= new StoneType("polished_andesite", "Polished Andesite");
	}

	/**
	 * @return StoneType[]
	 */
	public static function values() : array{
		return [
			self::NORMAL(),
			self::GRANITE(),
			self::POLISHED_GRANITE(),
			self::DIORITE(),
			self::POLISHED_DIORITE(),
			self::ANDESITE(),
			self::POLISHED_ANDESITE(),
		];
	}
}