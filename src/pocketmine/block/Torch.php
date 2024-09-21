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
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class Torch extends Flowable{

	protected $id = self::TORCH;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getLightLevel() : int{
		return 14;
	}

	public function getName() : string{
		return "Torch";
	}

	public function onNearbyBlockChange() : void{
		$below = $this->getSide(Vector3::SIDE_DOWN);
		$meta = $this->getDamage();
		static $faces = [
			0 => Vector3::SIDE_DOWN,
			1 => Vector3::SIDE_WEST,
			2 => Vector3::SIDE_EAST,
			3 => Vector3::SIDE_NORTH,
			4 => Vector3::SIDE_SOUTH,
			5 => Vector3::SIDE_DOWN
		];
		$face = $faces[$meta] ?? Vector3::SIDE_DOWN;

		if($this->getSide($face)->isTransparent() and !($face === Vector3::SIDE_DOWN and ($below->getId() === self::FENCE or $below->getId() === self::COBBLESTONE_WALL))){
			$this->getLevelNonNull()->useBreakOn($this);
		}
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		$below = $this->getSide(Vector3::SIDE_DOWN);

		if(!$blockClicked->isTransparent() and $face !== Vector3::SIDE_DOWN){
			$faces = [
				Vector3::SIDE_UP => 5,
				Vector3::SIDE_NORTH => 4,
				Vector3::SIDE_SOUTH => 3,
				Vector3::SIDE_WEST => 2,
				Vector3::SIDE_EAST => 1
			];
			$this->meta = $faces[$face];
			$this->getLevelNonNull()->setBlock($blockReplace, $this, true, true);

			return true;
		}elseif(!$below->isTransparent() or $below->getId() === self::FENCE or $below->getId() === self::COBBLESTONE_WALL){
			$this->meta = 0;
			$this->getLevelNonNull()->setBlock($blockReplace, $this, true, true);

			return true;
		}

		return false;
	}

	public function getVariantBitmask() : int{
		return 0;
	}
}