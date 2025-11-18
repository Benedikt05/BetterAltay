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
use pocketmine\level\sound\DoorSound;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;

abstract class Door extends Transparent{

	public function isSolid() : bool{
		return false;
	}

	public function isPassable() : bool{
		return (($this->meta >> 2) & 1) === 1;
	}

	public function getDirection() : int{
		return $this->meta & 0b11;
	}

	public function isOpen() : bool{
		return (($this->meta >> 2) & 1) === 1;
	}

	public function isUpper() : bool{
		return (($this->meta >> 3) & 1) === 1;
	}

	public function isHingeRight() : bool{
		return (($this->meta >> 4) & 1) === 1;
	}

	public function buildMeta(int $direction, int $open, int $upper, int $hinge) : int{
		return
			($direction & 0b11) |
			(($open & 1) << 2) |
			(($upper & 1) << 3) |
			(($hinge & 1) << 4);
	}

	protected function recalculateBoundingBox() : ?AxisAlignedBB{

		$f = 0.1875;

		$j = $this->getDirection();
		$isOpen = $this->isOpen();
		$isRight = $this->isHingeRight();

		$bb = new AxisAlignedBB(
			$this->x,
			$this->y,
			$this->z,
			$this->x + 1,
			$this->y + 2,
			$this->z + 1
		);

		switch($j){
			case 0:
				if($isOpen){
					if(!$isRight){
						$bb->setBounds($this->x, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + $f);
					}else{
						$bb->setBounds($this->x, $this->y, $this->z + 1 - $f, $this->x + 1, $this->y + 1, $this->z + 1);
					}
				}else{
					$bb->setBounds($this->x, $this->y, $this->z, $this->x + $f, $this->y + 1, $this->z + 1);
				}
				break;

			case 1:
				if($isOpen){
					if(!$isRight){
						$bb->setBounds($this->x + 1 - $f, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + 1);
					}else{
						$bb->setBounds($this->x, $this->y, $this->z, $this->x + $f, $this->y + 1, $this->z + 1);
					}
				}else{
					$bb->setBounds($this->x, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + $f);
				}
				break;

			case 2:
				if($isOpen){
					if(!$isRight){
						$bb->setBounds($this->x, $this->y, $this->z + 1 - $f, $this->x + 1, $this->y + 1, $this->z + 1);
					}else{
						$bb->setBounds($this->x, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + $f);
					}
				}else{
					$bb->setBounds($this->x + 1 - $f, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + 1);
				}
				break;

			case 3:
				if($isOpen){
					if(!$isRight){
						$bb->setBounds($this->x, $this->y, $this->z, $this->x + $f, $this->y + 1, $this->z + 1);
					}else{
						$bb->setBounds($this->x + 1 - $f, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + 1);
					}
				}else{
					$bb->setBounds($this->x, $this->y, $this->z + 1 - $f, $this->x + 1, $this->y + 1, $this->z + 1);
				}
				break;
		}

		return $bb;
	}

	public function onNearbyBlockChange() : void{
		if($this->getSide(Vector3::SIDE_DOWN)->getId() === BlockNames::AIR){ //Replace with common break method
			$this->getLevelNonNull()->setBlock($this, BlockFactory::get(BlockNames::AIR));

			$up = $this->getSide(Vector3::SIDE_UP);
			if($up instanceof Door){
				$this->getLevelNonNull()->setBlock($up, BlockFactory::get(BlockNames::AIR));
			}
		}
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		if($face !== Vector3::SIDE_UP){
			return false;
		}

		$blockUp = $this->getSide(Vector3::SIDE_UP);
		$blockDown = $this->getSide(Vector3::SIDE_DOWN);

		if(!$blockUp->canBeReplaced() || $blockDown->isTransparent()){
			return false;
		}

		$direction = $player instanceof Player ? ($player->getDirection() & 0x03) : 0;

		$faces = [0 => 3, 1 => 4, 2 => 2, 3 => 5];
		$next = $this->getSide($faces[($direction + 2) % 4]);
		$next2 = $this->getSide($faces[$direction]);

		$hinge = ($next->getId() === $this->getId() || (!$next2->isTransparent() && $next->isTransparent())) ? 1 : 0;

		$bottomMeta = $this->buildMeta($direction, 0, 0, $hinge);
		$this->getLevelNonNull()->setBlock($blockReplace, BlockFactory::get($this->getId(), $bottomMeta), true);

		$topMeta = $this->buildMeta($direction, 0, 1, $hinge);
		$this->getLevelNonNull()->setBlock($blockUp, BlockFactory::get($this->getId(), $topMeta), true);

		return true;
	}

	public function onActivate(Item $item, Player $player = null) : bool{
		if($this->isUpper()){ //Top
			$down = $this->getSide(Vector3::SIDE_DOWN);
			if($down->getId() === $this->getId()){
				$downMeta = $down->getDamage() ^ (1 << 2);
				$this->level->setBlock($down, BlockFactory::get($this->getId(), $downMeta), true);
				$this->level->addSound(new DoorSound($this));
				return true;
			}
			return false;
		}

		$this->meta ^= (1 << 2);
		$this->level->setBlock($this, $this, true);
		$this->level->addSound(new DoorSound($this));
		return true;
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		if(!$this->isUpper()){ //bottom half only
			return parent::getDropsForCompatibleTool($item);
		}

		return [];
	}

	public function isAffectedBySilkTouch() : bool{
		return false;
	}

	public function getAffectedBlocks() : array{
		if($this->isUpper()){
			$down = $this->getSide(Vector3::SIDE_DOWN);
			if($down->getId() === $this->getId()){
				return [$this, $down];
			}
		}else{
			$up = $this->getSide(Vector3::SIDE_UP);
			if($up->getId() === $this->getId()){
				return [$this, $up];
			}
		}

		return parent::getAffectedBlocks();
	}
}
