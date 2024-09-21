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

use pocketmine\item\Hoe;
use pocketmine\item\Item;
use pocketmine\player\Player;

class Dirt extends Solid{

	protected $id = self::DIRT;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getHardness() : float{
		return 0.5;
	}

	public function getToolType() : int{
		return BlockToolType::TYPE_SHOVEL;
	}

	public function getName() : string{
		if($this->meta === 1){
			return "Coarse Dirt";
		}
		return "Dirt";
	}

	public function onActivate(Item $item, Player $player = null) : bool{
		if($item instanceof Hoe){
			$item->applyDamage(1);
			if($this->meta === 1){
				$this->getLevelNonNull()->setBlock($this, BlockFactory::get(Block::DIRT), true);
			}else{
				$this->getLevelNonNull()->setBlock($this, BlockFactory::get(Block::FARMLAND), true);
			}

			return true;
		}

		return false;
	}
}