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
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\Player;
use function mt_rand;

class Leaves extends Transparent{


	public function __construct(protected WoodType $material, int $meta = 0){
		$this->id = "minecraft:" . $this->material->getType() . "_leaves";
		$this->meta = $meta;
	}

	public function getHardness() : float{
		return 0.2;
	}

	public function getToolType() : int{
		return BlockToolType::TYPE_SHEARS;
	}

	public function getName() : string{
		return $this->material->getName() . " Leaves";
	}

	public function diffusesSkyLight() : bool{
		return true;
	}

	/**
	 * @param true[]                      $visited reference parameter
	 *
	 * @phpstan-param array<string, true> $visited
	 */
	protected function findLog(Block $pos, array &$visited, int $distance, ?int $fromSide = null) : bool{
		$index = $pos->x . "." . $pos->y . "." . $pos->z;
		if(isset($visited[$index])){
			return false;
		}
		if($pos instanceof Wood || $pos instanceof Log){
			return true;
		}elseif($pos->getId() === $this->id and $distance < 3){
			$visited[$index] = true;
			$down = $pos->getSide(Vector3::SIDE_DOWN);
			if($down instanceof Wood){
				return true;
			}
			if($fromSide === null){
				for($side = 2; $side <= 5; ++$side){
					if($this->findLog($pos->getSide($side), $visited, $distance + 1, $side)){
						return true;
					}
				}
			}else{ //No more loops
				switch($fromSide){
					case 2:
						if($this->findLog($pos->getSide(Vector3::SIDE_NORTH), $visited, $distance + 1, $fromSide)){
							return true;
						}elseif($this->findLog($pos->getSide(Vector3::SIDE_WEST), $visited, $distance + 1, $fromSide)){
							return true;
						}elseif($this->findLog($pos->getSide(Vector3::SIDE_EAST), $visited, $distance + 1, $fromSide)){
							return true;
						}
						break;
					case 3:
						if($this->findLog($pos->getSide(Vector3::SIDE_SOUTH), $visited, $distance + 1, $fromSide)){
							return true;
						}elseif($this->findLog($pos->getSide(Vector3::SIDE_WEST), $visited, $distance + 1, $fromSide)){
							return true;
						}elseif($this->findLog($pos->getSide(Vector3::SIDE_EAST), $visited, $distance + 1, $fromSide)){
							return true;
						}
						break;
					case 4:
						if($this->findLog($pos->getSide(Vector3::SIDE_NORTH), $visited, $distance + 1, $fromSide)){
							return true;
						}elseif($this->findLog($pos->getSide(Vector3::SIDE_SOUTH), $visited, $distance + 1, $fromSide)){
							return true;
						}elseif($this->findLog($pos->getSide(Vector3::SIDE_WEST), $visited, $distance + 1, $fromSide)){
							return true;
						}
						break;
					case 5:
						if($this->findLog($pos->getSide(Vector3::SIDE_NORTH), $visited, $distance + 1, $fromSide)){
							return true;
						}elseif($this->findLog($pos->getSide(Vector3::SIDE_SOUTH), $visited, $distance + 1, $fromSide)){
							return true;
						}elseif($this->findLog($pos->getSide(Vector3::SIDE_EAST), $visited, $distance + 1, $fromSide)){
							return true;
						}
						break;
				}
			}
		}

		return false;
	}

	public function onNearbyBlockChange() : void{
		if (($this->meta & (0x01 | 0x02)) === 0x00) {
			$this->meta |= 0x02;
			$this->getLevelNonNull()->setBlock($this, $this, true, false);
		}
	}

	public function ticksRandomly() : bool{
		return true;
	}

	public function onRandomTick() : void{
		if((($this->meta & 0x01) === 0x00) && (($this->meta & 0x02) !== 0x00)){
			$visited = [];

			$ev = new LeavesDecayEvent($this);
			$ev->call();
			if($ev->isCancelled() or $this->findLog($this, $visited, 0)){
				$this->meta &= ~0x02;
				$this->getLevelNonNull()->setBlock($this, $this, false, false);
			}else{
				$this->getLevelNonNull()->useBreakOn($this);
			}
		}
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		$this->meta |= 0x01;
		return $this->getLevelNonNull()->setBlock($this, $this, true);
	}

	public function getVariantBitmask() : int{
		return 0x03;
	}

	public function getDrops(Item $item) : array{
		if(($item->getBlockToolType() & BlockToolType::TYPE_SHEARS) !== 0){
			return $this->getDropsForCompatibleTool($item);
		}

		$drops = [];
		if(mt_rand(1, 20) === 1){ //Saplings
			$drops[] = $this->getSaplingItem();
		}
		if($this->canDropApples() and mt_rand(1, 200) === 1){ //Apples
			$drops[] = ItemFactory::get(ItemIds::APPLE);
		}

		return $drops;
	}

	public function getSaplingItem() : Item{
	return ItemFactory::get("minecraft:" . $this->material->getType() . "_sapling", $this->getVariant());
	}

	public function canDropApples() : bool{
		return $this->material->equals(WoodType::OAK());
	}

	public function getFlameEncouragement() : int{
		return 30;
	}

	public function getFlammability() : int{
		return 60;
	}

	public function getMaterial() : WoodType{
		return $this->material;
	}
}
