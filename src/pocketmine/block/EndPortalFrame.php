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
use pocketmine\Player;
use pocketmine\math\AxisAlignedBB;

class EndPortalFrame extends Solid{

	protected $id = self::END_PORTAL_FRAME;

	private const SIDES = [Vector3::SIDE_NORTH, Vector3::SIDE_EAST, Vector3::SIDE_SOUTH, Vector3::SIDE_WEST];

	private $eye = false;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getLightLevel() : int{
		return 1;
	}

	public function getName() : string{
		return "End Portal Frame";
	}

	public function getHardness() : float{
		return -1;
	}

	public function getBlastResistance() : float{
		return 18000000;
	}

	public function isBreakable(Item $item) : bool{
		return false;
	}

	protected function recalculateBoundingBox() : ?AxisAlignedBB{

		return new AxisAlignedBB(
			$this->x,
			$this->y,
			$this->z,
			$this->x + 1,
			$this->y + (($this->getDamage() & 0x04) > 0 ? 1 : 0.8125),
			$this->z + 1
		);
	}
	
	public function place(Item $item, Block $block, Block $target, int $face, Vector3 $facePos, Player $player = null): bool{
		$faces = [
			0 => 3,
			1 => 0,
			2 => 1,
			3 => 2,
		];
		$this->meta = $faces[$player instanceof Player ? $player->getDirection() : 0];
		$this->getLevel()->setBlock($block, $this, true, true);
		return true;
	}

	public function onActivate(Item $item, Player $player = null): bool{
		if(($this->getDamage() & 0x04) === 0 && $player instanceof Player && $item->getId() === Item::ENDER_EYE){
			$this->eye = true;
			$this->setDamage($this->getDamage() + 4);
			$this->getLevel()->setBlock($this, $this, false, true);
			$this->tryCreatingPortal($this);
			return true;
		}elseif($item->getId() !== Item::ENDER_EYE && ($this->getDamage() & 0x04) === 4){
			$this->setDamage($this->getDamage() & 0x04 - 4);
			$this->eye = false;
			$this->getLevel()->dropItem($this->add(0.5, 0.75, 0.5), Item::get(Item::ENDER_EYE));
			$this->tryDestroyingPortal($this);
		}
		return false;
	}

	public function isCompletedPortal(Block $center) : bool{
		for($i = 0; $i < 4; ++$i){
			for($j = -1; $j <= 1; ++$j){
				$block = $center->getSide(self::SIDES[$i], 2)->getSide(self::SIDES[($i + 1) % 4], $j);
				if(!($block instanceof EndPortalFrame)){
					return false;
				}
			}
		}
		return true;
	}

	public function tryCreatingPortal(Block $block) : void{
		for($i = 0; $i < 4; ++$i){
			for($j = -1; $j <= 1; ++$j){
				$center = $block->getSide(self::SIDES[$i], 2)->getSide(self::SIDES[($i + 1) % 4], $j);
				if($this->isCompletedPortal($center) && $this->eye === true){
					$this->createPortal($center);
				}
			}
		}
	}

	public function createPortal(Block $center) : void{
		$pos = $center->asPosition();
		for($i = -1; $i <= 1; ++$i){
			for($j = -1; $j <= 1; ++$j){
				$this->getLevel()->setBlock(new Vector3($pos->x + $i, $pos->y, $pos->z + $j), Block::get(Block::END_PORTAL, 0), false);
			}
		}
	}

	public function tryDestroyingPortal(Block $block) : void{
		for($i = 0; $i < 4; ++$i){
			for($j = -1; $j <= 1; ++$j){
				$center = $block->getSide(self::SIDES[$i], 2)->getSide(self::SIDES[($i + 1) % 4], $j);
				if(!$this->isCompletedPortal($center)){
					$this->destroyPortal($center);
				}
			}
		}
	}

	public function destroyPortal(Block $center) : void{
		$pos = $center->asPosition();
		$level = $pos->getLevel();
		for($i = -1; $i <= 1; ++$i){
			for($j = -1; $j <= 1; ++$j){
				if($level->getBlockAt($pos->x + $i, $pos->y, $pos->z + $j)->getId() === Block::END_PORTAL){
					$level->setBlock(new Vector3($pos->x + $i, $pos->y, $pos->z + $j), Block::get(Block::AIR), false);
				}
			}
		}
	}
}
