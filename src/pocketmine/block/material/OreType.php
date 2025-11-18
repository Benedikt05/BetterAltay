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

use pocketmine\item\Item;
use pocketmine\item\TieredTool;

class OreType extends Material{

	private static ?self $COAL = null;
	private static ?self $COPPER = null;
	private static ?self $IRON = null;
	private static ?self $GOLD = null;
	private static ?self $DIAMOND = null;
	private static ?self $REDSTONE = null;
	private static ?self $LAPIS_LAZULI = null;
	private static ?self $EMERALD = null;
	private static ?self $QUARTZ = null;
	private static ?self $NETHER_GOLD = null;

	public static function COAL() : self{
		return self::$COAL ??= new self("coal", "Coal", TieredTool::TIER_WOODEN, mt_rand(0, 2));
	}
	public static function COPPER() : self{
		return self::$COPPER ??= new self("copper", "Copper", TieredTool::TIER_STONE, dropCount:  mt_rand(2, 5));
	}
	public static function IRON() : self{
		return self::$IRON ??= new self("iron", "Iron", TieredTool::TIER_STONE);
	}
	public static function GOLD() : self{
		return self::$GOLD ??= new self("gold", "Gold", TieredTool::TIER_IRON);
	}
	public static function DIAMOND() : self{
		return self::$DIAMOND ??= new self("diamond", "Diamond", TieredTool::TIER_IRON, mt_rand(3, 7));
	}
	public static function REDSTONE() : self{
		return self::$REDSTONE ??= new self("redstone", "Redstone", TieredTool::TIER_IRON, mt_rand(1, 5), mt_rand(4, 5));
	}
	public static function LAPIS_LAZULI() : self{
		return self::$LAPIS_LAZULI ??= new self("lapis", "Lapis Lazuli", TieredTool::TIER_STONE, mt_rand(2, 5), mt_rand(4, 9));
	}

	public static function EMERALD() : self{
		return self::$EMERALD ??= new self("emerald", "Emerald", TieredTool::TIER_STONE, mt_rand(3, 7));
	}

	public static function QUARTZ() : self{
		return self::$QUARTZ ??= new self("quartz", "Quartz", TieredTool::TIER_WOODEN, mt_rand(2, 5));
	}

	public static function NETHER_GOLD() : self{
		return self::$NETHER_GOLD ??= new self("nether_gold", "Nether Gold", TieredTool::TIER_WOODEN);
	}

    public function __construct(
		string $type,
		string $name,
		protected int $toolHarvestLevel,
		protected int $xp = 0,
		protected int $dropCount = 1,
	){
        parent::__construct($type, $name);
    }
    
    public function getToolHarvestLevel() : int{
        return $this->toolHarvestLevel;
    }

	public static function values() : array{
		return [
			self::COAL(),
			self::COPPER(),
			self::IRON(),
			self::GOLD(),
			self::DIAMOND(),
			self::REDSTONE(),
			self::LAPIS_LAZULI(),
			self::EMERALD(),
			self::QUARTZ(),
			self::NETHER_GOLD()
		];
	}

	public function getXp() : int{
		return $this->xp;
	}

	public function getDropCount() : int{
		return $this->dropCount;
	}
}