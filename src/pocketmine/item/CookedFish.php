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

namespace pocketmine\item;

class CookedFish extends Food{
	protected int $foodRestore;
	protected float $saturationRestore;

	public function __construct(string $id = self::COOKED_COD, string $name = "Cooked Cod", int $foodRestore = 5, float $saturationRestore = 6){
		parent::__construct($id, 0, $name);
		$this->foodRestore = $foodRestore;
		$this->saturationRestore = $saturationRestore;
	}

	public function getFoodRestore() : int{
		return $this->foodRestore;
	}

	public function getSaturationRestore() : float{
		return $this->saturationRestore;
	}
}
