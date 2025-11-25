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
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Sugarcane extends Flowable{

	protected string $id = self::REEDS;

	protected string $itemId = ItemIds::SUGAR_CANE;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getName() : string{
		return "Sugarcane";
	}

	public function onActivate(Item $item, Player $player = null) : bool{
		if($item->getId() === ItemIds::BONE_MEAL){
			if($this->getSide(Vector3::SIDE_DOWN)->getId() !== self::REEDS){
				for($y = 1; $y < 3; ++$y){
					$b = $this->getLevelNonNull()->getBlockAt($this->x, $this->y + $y, $this->z);
					if($b->getId() === self::AIR){
						$ev = new BlockGrowEvent($b, BlockFactory::get(self::REEDS));
						$ev->call();
						if($ev->isCancelled()){
							break;
						}
						$this->getLevelNonNull()->setBlock($b, $ev->getNewState(), true);
					}else{
						break;
					}
				}
				$this->meta = 0;
				$this->getLevelNonNull()->setBlock($this, $this, true);
			}

			$item->pop();

			return true;
		}

		return false;
	}

	public function onNearbyBlockChange() : void{
		$down = $this->getSide(Vector3::SIDE_DOWN);
		if($down->isTransparent() and $down->getId() !== self::REEDS){
			$this->getLevelNonNull()->useBreakOn($this);
		}
	}

	public function ticksRandomly() : bool{
		return true;
	}

	public function onRandomTick() : void{
		if($this->getSide(Vector3::SIDE_DOWN)->getId() !== self::REEDS){
			if($this->meta === 0x0F){
				for($y = 1; $y < 3; ++$y){
					$b = $this->getLevelNonNull()->getBlockAt($this->x, $this->y + $y, $this->z);
					if($b->getId() === self::AIR){
						$ev = new BlockGrowEvent($b, BlockFactory::get(self::REEDS));
						$ev->call();
						if($ev->isCancelled()){
							break;
						}
						$this->getLevelNonNull()->setBlock($b, $ev->getNewState(), true);
						break;
					}
				}
				$this->meta = 0;
				$this->getLevelNonNull()->setBlock($this, $this, true);
			}else{
				++$this->meta;
				$this->getLevelNonNull()->setBlock($this, $this, true);
			}
		}
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		$down = $this->getSide(Vector3::SIDE_DOWN);
		if($down->getId() === self::REEDS){
			$this->getLevelNonNull()->setBlock($blockReplace, BlockFactory::get(self::REEDS), true);

			return true;
		}elseif($down->getId() === self::GRASS_BLOCK or $down->getId() === self::DIRT or $down->getId() === self::SAND){
			$block0 = $down->getSide(Vector3::SIDE_NORTH);
			$block1 = $down->getSide(Vector3::SIDE_SOUTH);
			$block2 = $down->getSide(Vector3::SIDE_WEST);
			$block3 = $down->getSide(Vector3::SIDE_EAST);
			if(($block0 instanceof Water) or ($block1 instanceof Water) or ($block2 instanceof Water) or ($block3 instanceof Water)){
				$this->getLevelNonNull()->setBlock($blockReplace, BlockFactory::get(self::REEDS), true);

				return true;
			}
		}

		return false;
	}

	public function getVariantBitmask() : int{
		return 0;
	}
}
