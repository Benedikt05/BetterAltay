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

namespace pocketmine\network\mcpe\protocol\types;

interface WindowTypes{

	public const NONE = -9;

	public const INVENTORY = -1;
	public const CONTAINER = 0;
	public const WORKBENCH = 1;
	public const FURNACE = 2;
	public const ENCHANTMENT = 3;
	public const BREWING_STAND = 4;
	public const ANVIL = 5;
	public const DISPENSER = 6;
	public const DROPPER = 7;
	public const HOPPER = 8;
	public const CAULDRON = 9;
	public const MINECART_CHEST = 10;
	public const MINECART_HOPPER = 11;
	public const HORSE = 12;
	public const BEACON = 13;
	public const STRUCTURE_EDITOR = 14;
	public const TRADING = 15;
	public const COMMAND_BLOCK = 16;
	public const JUKEBOX = 17;
	public const ARMOR = 18;
	public const HAND = 19;
	public const COMPOUND_CREATOR = 20;
	public const ELEMENT_CONSTRUCTOR = 21;
	public const MATERIAL_REDUCER = 22;
	public const LAB_TABLE = 23;
	public const LOOM = 24;
	public const LECTERN = 25;
	public const GRINDSTONE = 26;
	public const BLAST_FURNACE = 27;
	public const SMOKER = 28;
	public const STONECUTTER = 29;
	public const CARTOGRAPHY = 30;
	public const HUD = 31;
	public const JIGSAW_EDITOR = 32;
	public const SMITHING_TABLE = 33;
	public const CHEST_BOAT = 34;
	public const DECORATED_POT = 35;
	public const CRAFTER = 36;

}
