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

namespace pocketmine\block\state;

use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;

class BlockStateSerializer {

	public static function writeToNBT(StateData $states, CompoundTag $nbt): void {
		foreach ($states->getAll() as $name => $value) {
			if (is_bool($value)) {
				$nbt->setByte($name, $value ? 1 : 0);
			} elseif (is_int($value)) {
				$nbt->setInt($name, $value);
			} elseif (is_string($value)) {
				$nbt->setString($name, $value);
			}
		}
	}

	public static function readFromNBT(CompoundTag $nbt): StateData {
		$states = new StateData();

		foreach ($nbt->getValue() as $name => $tag) {
			if ($tag instanceof ByteTag) {
				$states->set($name, (bool) $tag->getValue());
			} elseif ($tag instanceof IntTag) {
				$states->set($name, (int) $tag->getValue());
			} elseif ($tag instanceof StringTag) {
				$states->set($name, (string) $tag->getValue());
			}
		}

		return $states;
	}
}