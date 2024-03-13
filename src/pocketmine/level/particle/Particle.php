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

namespace pocketmine\level\particle;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DataPacket;

abstract class Particle extends Vector3{

	public const TYPE_BUBBLE = 1;
	public const TYPE_BUBBLE_MANUAL = 2;
	public const TYPE_CRITICAL = 3;
	public const TYPE_BLOCK_FORCE_FIELD = 4;
	public const TYPE_SMOKE = 5;
	public const TYPE_EXPLODE = 6;
	public const TYPE_EVAPORATION = 7;
	public const TYPE_FLAME = 8;
	public const TYPE_CANDLE_FLAME = 9;
	public const TYPE_LAVA = 10;
	public const TYPE_LARGE_SMOKE = 11;
	public const TYPE_REDSTONE = 12;
	public const TYPE_RISING_RED_DUST = 13;
	public const TYPE_ITEM_BREAK = 14;
	public const TYPE_SNOWBALL_POOF = 15;
	public const TYPE_HUGE_EXPLODE = 16;
	public const TYPE_HUGE_EXPLODE_SEED = 17;
	public const TYPE_BREEZE_WIND_EXPLOSION = 18;
	public const TYPE_MOB_FLAME = 19;
	public const TYPE_HEART = 20;
	public const TYPE_TERRAIN = 21;
	public const TYPE_SUSPENDED_TOWN = 22, TYPE_TOWN_AURA = 22;
	public const TYPE_PORTAL = 23;
	//24 same as 23
	public const TYPE_SPLASH = 25, TYPE_WATER_SPLASH = 25;
	public const TYPE_WATER_SPLASH_MANUAL = 26;
	public const TYPE_WATER_WAKE = 27;
	public const TYPE_DRIP_WATER = 28;
	public const TYPE_DRIP_LAVA = 29;
	public const TYPE_DRIP_HONEY = 30;
	public const TYPE_STALACTITE_DRIP_WATER = 31;
	public const TYPE_STALACTITE_DRIP_LAVA = 32;
	public const TYPE_FALLING_DUST = 33, TYPE_DUST = 33;
	public const TYPE_MOB_SPELL = 34;
	public const TYPE_MOB_SPELL_AMBIENT = 35;
	public const TYPE_MOB_SPELL_INSTANTANEOUS = 36;
	public const TYPE_INK = 37;
	public const TYPE_SLIME = 38;
	public const TYPE_RAIN_SPLASH = 39;
	public const TYPE_VILLAGER_ANGRY = 40;
	public const TYPE_VILLAGER_HAPPY = 41;
	public const TYPE_ENCHANTMENT_TABLE = 42;
	public const TYPE_TRACKING_EMITTER = 43;
	public const TYPE_NOTE = 44;
	public const TYPE_WITCH_SPELL = 45;
	public const TYPE_CARROT = 46;
	public const TYPE_MOB_APPEARANCE = 47;
	public const TYPE_END_ROD = 48;
	public const TYPE_DRAGONS_BREATH = 49;
	public const TYPE_SPIT = 50;
	public const TYPE_TOTEM = 51;
	public const TYPE_FOOD = 52;
	public const TYPE_FIREWORKS_STARTER = 53;
	public const TYPE_FIREWORKS_SPARK = 54;
	public const TYPE_FIREWORKS_OVERLAY = 55;
	public const TYPE_BALLOON_GAS = 56;
	public const TYPE_COLORED_FLAME = 57;
	public const TYPE_SPARKLER = 58;
	public const TYPE_CONDUIT = 59;
	public const TYPE_BUBBLE_COLUMN_UP = 60;
	public const TYPE_BUBBLE_COLUMN_DOWN = 61;
	public const TYPE_SNEEZE = 62;
	public const TYPE_SHULKER_BULLET = 63;
	public const TYPE_BLEACH = 64;
	public const TYPE_DRAGON_DESTROY_BLOCK = 65;
	public const TYPE_MYCELIUM_DUST = 66;
	public const TYPE_FALLING_RED_DUST = 67;
	public const TYPE_CAMPFIRE_SMOKE = 68;
	public const TYPE_TALL_CAMPFIRE_SMOKE = 69;
	public const TYPE_DRAGON_BREATH_FIRE = 70;
	public const TYPE_DRAGON_BREATH_TRAIL = 71;
	public const TYPE_BLUE_FLAME = 72;
	public const TYPE_SOUL = 73;
	public const TYPE_OBSIDIAN_TEAR = 74;
	public const TYPE_PORTAL_REVERSE = 75;
	public const TYPE_SNOWFLAKE = 76;
	public const TYPE_VIBRATION_SIGNAL = 77;
	public const TYPE_SCULK_SENSOR_REDSTONE = 78;
	public const TYPE_SPORE_BLOSSOM_SHOWER = 79;
	public const TYPE_SPORE_BLOSSOM_AMBIENT = 80;
	public const TYPE_WAX = 81;
	public const TYPE_ELECTRIC_SPARK = 82;
	public const TYPE_SHRIEK = 83;
	public const TYPE_SCULK_SOUL = 84;
	public const TYPE_SONIC_EXPLOSION = 85;
	public const TYPE_BRUSH_DUST = 86;
	public const TYPE_CHERRY_LEAVES = 87;
	public const TYPE_DUST_PLUME = 88;
	public const TYPE_WHITE_SMOKE = 89;
	public const TYPE_VAULT_CONNECTION = 90;
	public const TYPE_WIND_EXPLOSION = 91;

	/**
	 * @return DataPacket|DataPacket[]
	 */
	abstract public function encode();

}
