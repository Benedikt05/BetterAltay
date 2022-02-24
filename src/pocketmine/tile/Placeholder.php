<?php

declare(strict_types=1);

namespace pocketmine\tile;

use pocketmine\nbt\tag\CompoundTag;

class Placeholder extends Tile{
	use PlaceholderTrait;

	protected function readSaveData(CompoundTag $nbt) : void{
		$this->loadBlock($nbt);
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$this->saveBlock($nbt);
	}
}