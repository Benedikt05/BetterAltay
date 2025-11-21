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

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;

class UnknownBlock extends Transparent{

	protected string $id = BlockIds::UNKNOWN;

	public function __construct(){
		parent::__construct($this->id, 0, "Unknown Block");
		$this->itemId = BlockIds::AIR;
	}

	public function getHardness() : float{
		return 0;
	}

	public function canBePlaced() : bool{
		return false;
	}

	public function getDrops(Item $item) : array{
		return [];
	}

	public function getRuntimeId() : int{
		return RuntimeBlockMapping::UNKNOWN();
	}
}
