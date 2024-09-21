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

use pocketmine\event\block\BlockGrowEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use function mt_rand;

class NetherWartPlant extends Flowable{
	protected $id = Block::NETHER_WART_PLANT;

	protected $itemId = Item::NETHER_WART;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getName() : string{
		return "Nether Wart";
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		$down = $this->getSide(Vector3::SIDE_DOWN);
		if($down->getId() === Block::SOUL_SAND){
			$this->getLevelNonNull()->setBlock($blockReplace, $this, false, true);

			return true;
		}

		return false;
	}

	public function onNearbyBlockChange() : void{
		if($this->getSide(Vector3::SIDE_DOWN)->getId() !== Block::SOUL_SAND){
			$this->getLevelNonNull()->useBreakOn($this);
		}
	}

	public function ticksRandomly() : bool{
		return true;
	}

	public function onRandomTick() : void{
		if($this->meta < 3 and mt_rand(0, 10) === 0){ //Still growing
			$block = clone $this;
			$block->meta++;
			$ev = new BlockGrowEvent($this, $block);
			$ev->call();
			if(!$ev->isCancelled()){
				$this->getLevelNonNull()->setBlock($this, $ev->getNewState(), false, true);
			}
		}
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		return [
			ItemFactory::get($this->getItemId(), 0, ($this->getDamage() === 3 ? mt_rand(2, 4) : 1))
		];
	}

	public function isAffectedBySilkTouch() : bool{
		return false;
	}
}