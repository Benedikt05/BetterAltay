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

use pocketmine\nbt\tag\CompoundTag;

final class ItemTypeEntry{
	private string $stringId;
	private int $numericId;
	private bool $componentBased;
	private int $itemVersion;
	private CompoundTag $itemComponentData;

	public function __construct(string $stringId, int $numericId, bool $componentBased, int $itemVersion, CompoundTag $itemComponentData){
		$this->stringId = $stringId;
		$this->numericId = $numericId;
		$this->componentBased = $componentBased;
		$this->itemVersion = $itemVersion;
		$this->itemComponentData = $itemComponentData;
	}

	public function getStringId() : string{ return $this->stringId; }

	public function getNumericId() : int{ return $this->numericId; }

	public function isComponentBased() : bool{ return $this->componentBased; }

	public function getItemVersion() : int{ return $this->itemVersion; }

	public function getItemComponentData() : CompoundTag{ return $this->itemComponentData; }
}
