<?php

namespace pocketmine\block\material;

class FlowerType extends Material {

	private static ?FlowerType $DANDOLINE = null;
	private static ?FlowerType $POPPY = null;
	private static ?FlowerType $BLUE_ORCHID = null;
	private static ?FlowerType $ALLIUM = null;
	private static ?FlowerType $AZURE_BLUET = null;
	private static ?FlowerType $RED_TULIP = null;
	private static ?FlowerType $ORANGE_TULIP = null;
	private static ?FlowerType $WHITE_TULIP = null;
	private static ?FlowerType $PINK_TULIP = null;
	private static ?FlowerType $OXEYE_DAISY = null;
	private static ?FlowerType $CORNFLOWER = null;
	private static ?FlowerType $LILY_OF_THE_VALLEY = null;
	private static ?FlowerType $TORCHFLOWER = null;
	private static ?FlowerType $WITHER_ROSE = null;
	private static ?FlowerType $OPEN_EYEBLOSSOM = null;
	private static ?FlowerType $CLOSED_EYEBLOSSOM = null;
	private static ?FlowerType $WILDFLOWERS = null;
	private static ?FlowerType $PINK_PETALS = null;
	private static ?FlowerType $CACTUS_FLOWER = null;

	public static function DANDOLINE() : FlowerType{
		return self::$DANDOLINE ??= new FlowerType("dandoline", "Dandoline");
	}

	public static function POPPY() : FlowerType{
		return self::$POPPY ??= new FlowerType("poppy", "Poppy");
	}

	public static function BLUE_ORCHID() : FlowerType{
		return self::$BLUE_ORCHID ??= new FlowerType("blue_orchid", "Blue Orchid");
	}

	public static function ALLIUM() : FlowerType{
		return self::$ALLIUM ??= new FlowerType("allium", "Allium");
	}

	public static function AZURE_BLUET() : FlowerType{
		return self::$AZURE_BLUET ??= new FlowerType("azure_blue", "Azure Blue");
	}

	public static function RED_TULIP() : FlowerType{
		return self::$RED_TULIP ??= new FlowerType("red_tulip", "Red Tulip");
	}

	public static function ORANGE_TULIP() : FlowerType{
		return self::$ORANGE_TULIP ??= new FlowerType("orange_tulip", "Orange Tulip");
	}

	public static function WHITE_TULIP() : FlowerType{
		return self::$WHITE_TULIP ??= new FlowerType("white_tulip", "White Tulip");
	}

	public static function PINK_TULIP() : FlowerType{
		return self::$PINK_TULIP ??= new FlowerType("pink_tulip", "Pink Tulip");
	}

	public static function OXEYE_DAISY() : FlowerType{
		return self::$OXEYE_DAISY ??= new FlowerType("oxeye_daisy", "Oxeye Daisy");
	}

	public static function CORNFLOWER() : FlowerType{
		return self::$CORNFLOWER ??= new FlowerType("cornflower", "Cornflower");
	}

	public static function LILY_OF_THE_VALLEY() : FlowerType{
		return self::$LILY_OF_THE_VALLEY ??= new FlowerType("lill_of_the_valley", "Lill of the Valley");
	}

	public static function TORCHFLOWER() : FlowerType{
		return self::$TORCHFLOWER ??= new FlowerType("torchflower", "Torch Flower");
	}

	public static function WITHER_ROSE() : FlowerType{
		return self::$WITHER_ROSE ??= new FlowerType("wither_rose", "Wither Rose");
	}

	public static function OPEN_EYEBLOSSOM() : FlowerType{
		return self::$OPEN_EYEBLOSSOM ??= new FlowerType("open_eyeblossom", "Open EyeBlossom");
	}

	public static function CLOSED_EYEBLOSSOM() : FlowerType{
		return self::$CLOSED_EYEBLOSSOM ??= new FlowerType("closed_eyeblossom", "Closed EyeBlossom");
	}

	public static function WILDFLOWERS() : FlowerType{
		return self::$WILDFLOWERS ??= new FlowerType("wilflowers", "Wilflowers");
	}

	public static function PINK_PETALS() : FlowerType{
		return self::$PINK_PETALS ??= new FlowerType("pink_petals", "Pink Peals");
	}

	public static function CACTUS_FLOWER() : FlowerType{
		return self::$CACTUS_FLOWER ??= new FlowerType("cactus_flower", "Cactus Flower");
	}

	public static function values() : array{
		return [
			self::DANDOLINE(),
			self::POPPY(),
			self::BLUE_ORCHID(),
			self::ALLIUM(),
			self::AZURE_BLUET(),
			self::RED_TULIP(),
			self::ORANGE_TULIP(),
			self::WHITE_TULIP(),
			self::PINK_TULIP(),
			self::OXEYE_DAISY(),
			self::CORNFLOWER(),
			self::LILY_OF_THE_VALLEY(),
			self::TORCHFLOWER(),
			self::WITHER_ROSE(),
			self::OPEN_EYEBLOSSOM(),
			self::CLOSED_EYEBLOSSOM(),
			self::WILDFLOWERS(),
			self::PINK_PETALS(),
			self::CACTUS_FLOWER(),
		];
	}
}