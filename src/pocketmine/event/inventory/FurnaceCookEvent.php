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

namespace pocketmine\event\inventory;

use pocketmine\event\block\BlockEvent;
use pocketmine\event\Cancellable;
use pocketmine\item\Item;
use pocketmine\tile\Furnace;

class FurnaceCookEvent extends BlockEvent implements Cancellable{
	/** @var Furnace */
	private $furnace;
	/** @var int */
	private $maxCookTime;


	public function __construct(Furnace $furnace, int $maxCookTime){
		parent::__construct($furnace->getBlock());	
		$this->maxCookTime = $maxCookTime;
		$this->furnace = $furnace;
	}

	public function getFurnace() : Furnace{
		return $this->furnace;
	}

	public function getMaxCookTime() : int{
		return $this->maxCookTime;
	}

	public function setMaxCookTime(int $maxCookTime) : void{
		$this->maxCookTime = $maxCookTime;
	}
}
