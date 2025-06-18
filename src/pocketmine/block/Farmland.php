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

use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\GameRules;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;

class Farmland extends Transparent{

	protected $id = self::FARMLAND;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getName() : string{
		return "Farmland";
	}

	public function getHardness() : float{
		return 0.6;
	}

	public function getToolType() : int{
		return BlockToolType::TYPE_SHOVEL;
	}

	protected function recalculateBoundingBox() : ?AxisAlignedBB{
		return new AxisAlignedBB(
			$this->x,
			$this->y,
			$this->z,
			$this->x + 1,
			$this->y + 1, //TODO: this should be 0.9375, but MCPE currently treats them as a full block (https://bugs.mojang.com/browse/MCPE-12109)
			$this->z + 1
		);
	}

	public function onNearbyBlockChange() : void{
		if($this->getSide(Vector3::SIDE_UP)->isSolid()){
			$this->level->setBlock($this, BlockFactory::get(Block::DIRT), true);
		}
	}

	public function ticksRandomly() : bool{
		return true;
	}

	public function onRandomTick() : void{
		if(!$this->canHydrate()){
			if($this->meta > 0){
				$this->meta--;
				$this->level->setBlock($this, $this, false, false);
			}else{
				$this->level->setBlock($this, BlockFactory::get(Block::DIRT), false, true);
			}
		}elseif($this->meta < 7){
			$this->meta = 7;
			$this->level->setBlock($this, $this, false, false);
		}
	}

	protected function canHydrate() : bool{
		//TODO: check rain
		$start = $this->add(-4, 0, -4);
		$end = $this->add(4, 1, 4);
		for($y = $start->y; $y <= $end->y; ++$y){
			for($z = $start->z; $z <= $end->z; ++$z){
				for($x = $start->x; $x <= $end->x; ++$x){
					[$id, ] = RuntimeBlockMapping::fromStaticRuntimeId($this->level->getBlockIdAt($x, $y, $z, 1));
					if($id === Block::STILL_WATER or $id === Block::FLOWING_WATER){
						return true;
					}
				}
			}
		}

		return false;
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		return [
			ItemFactory::get(Item::DIRT)
		];
	}

	public function isAffectedBySilkTouch() : bool{
		return false;
	}

	public function getPickedItem() : Item{
		return ItemFactory::get(Item::DIRT);
	}

	public function onEntityFallenUpon(Entity $entity, float $fallDistance) : void{
		if($entity instanceof Living){
			if($this->level->random->nextFloat() < ($fallDistance - 0.5)){
				$ev = new BlockFormEvent($this, BlockFactory::get(Block::DIRT));

				if(!$this->level->getGameRules()->getBool(GameRules::RULE_MOB_GRIEFING, true)){
					$ev->setCancelled();
				}
				$ev->call();

				if(!$ev->isCancelled()){
					$this->level->setBlock($this, $ev->getNewState(), true);
				}
			}
		}
	}
}
