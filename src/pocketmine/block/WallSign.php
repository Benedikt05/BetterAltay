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

use pocketmine\block\material\WoodType;
use pocketmine\item\Sign;

class WallSign extends SignPost{

	public function __construct(protected WoodType $material, int $meta = 0){
		$type = Sign::resolveWoodPrefix($this->material);
		$this->id = "minecraft:" . $type . "wall_sign";
		$this->itemId = "minecraft:" . $type . "sign";
		$this->meta = $meta;
	}

	public function getName() : string{
		return $this->material->getName() . " Wall Sign";
	}

	public function onNearbyBlockChange() : void{
		$side = ($this->meta % 2 === 0) ? $this->meta + 1 : $this->meta - 1;
		if($this->getSide($side)->getId() === BlockNames::AIR){
			$this->getLevelNonNull()->useBreakOn($this);
		}
	}
}
