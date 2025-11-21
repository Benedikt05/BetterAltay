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

use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\item\Hoe;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\Shovel;
use pocketmine\level\generator\object\TallGrass as TallGrassObject;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\Player;
use pocketmine\utils\Random;
use function mt_rand;

class Grass extends Solid{

	protected string $id = self::GRASS_BLOCK;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getName() : string{
		return "Grass";
	}

	public function getHardness() : float{
		return 0.6;
	}

	public function getToolType() : int{
		return BlockToolType::TYPE_SHOVEL;
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		return [
			ItemFactory::get(self::DIRT)
		];
	}

	public function ticksRandomly() : bool{
		return true;
	}

	public function onRandomTick() : void{
		$lightAbove = $this->level->getFullLightAt($this->x, $this->y + 1, $this->z);
		$id = RuntimeBlockMapping::getIdFromRuntimeId($this->level->getBlockIdAt($this->x, $this->y + 1, $this->z));
		if($lightAbove < 4 and (isset(BlockFactory::$lightFilter[$id]) && BlockFactory::$lightFilter[$id] >= 3)){ //2 plus 1 standard filter amount
			//grass dies
			$ev = new BlockSpreadEvent($this, $this, BlockFactory::get(self::DIRT));
			$ev->call();
			if(!$ev->isCancelled()){
				$this->level->setBlock($this, $ev->getNewState(), false, false);
			}
		}elseif($lightAbove >= 9){
			//try grass spread
			for($i = 0; $i < 4; ++$i){
				$x = mt_rand($this->x - 1, $this->x + 1);
				$y = mt_rand($this->y - 3, $this->y + 1);
				$z = mt_rand($this->z - 1, $this->z + 1);
				[$id, $meta] = RuntimeBlockMapping::fromRuntimeId($this->level->getBlockIdAt($x, $y, $z));
				[$up, ] = RuntimeBlockMapping::fromRuntimeId($this->level->getBlockIdAt($x, $y + 1, $z));
				if(
					$id !== self::DIRT or
					$meta === 1 or
					$this->level->getFullLightAt($x, $y + 1, $z) < 4 or
					(isset(BlockFactory::$lightFilter[$up]) && BlockFactory::$lightFilter[$up]) >= 3
				){
					continue;
				}

				$ev = new BlockSpreadEvent($b = $this->level->getBlockAt($x, $y, $z), $this, BlockFactory::get(self::GRASS_BLOCK));
				$ev->call();
				if(!$ev->isCancelled()){
					$this->level->setBlock($b, $ev->getNewState(), false, false);
				}
			}
		}
	}

	public function onActivate(Item $item, Player $player = null) : bool{
		if($item->getId() === ItemIds::DYE and $item->getDamage() === 0x0F){
			$item->pop();
			TallGrassObject::growGrass($this->getLevelNonNull(), $this, new Random(mt_rand()), 8, 2);

			return true;
		}elseif($item instanceof Hoe){
			$item->applyDamage(1);
			$this->getLevelNonNull()->setBlock($this, BlockFactory::get(self::FARMLAND));

			return true;
		}elseif($item instanceof Shovel and $this->getSide(Vector3::SIDE_UP)->getId() === self::AIR){
			$item->applyDamage(1);
			$this->getLevelNonNull()->setBlock($this, BlockFactory::get(self::GRASS_PATH));

			return true;
		}

		return false;
	}
}
