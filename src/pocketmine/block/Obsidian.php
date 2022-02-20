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

use pocketmine\item\TieredTool;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\item\FlintSteel;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Obsidian extends Solid{

	protected $id = self::OBSIDIAN;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getName() : string{
		return "Obsidian";
	}

	public function getToolType() : int{
		return BlockToolType::TYPE_PICKAXE;
	}

	public function getToolHarvestLevel() : int{
		return TieredTool::TIER_DIAMOND;
	}

	public function getHardness() : float{
		return 35; //50 in PC
	}

	public function getBlastResistance() : float{
		return 6000;
	}
	
	public function onActivate(Item $item, Player $player = null): bool {
		if($item instanceof FlintSteel) {
			$x_max = $x_min = $this->x;
			for(
				$x = $this->x + 1; $this->level->getBlockIdAt($x, $this->y, $this->z) == Block::OBSIDIAN; $x++
			) {
				$x_max++;
			}
			for(
				$x = $this->x - 1; $this->level->getBlockIdAt($x, $this->y, $this->z) == Block::OBSIDIAN; $x--
			) {
				$x_min--;
			}
			$count_x = $x_max - $x_min + 1;
			if($count_x >= 4 and $count_x <= 23) {
				$x_max_y = $this->y;
				$x_min_y = $this->y;
				for(
					$y = $this->y; $this->level->getBlockIdAt($x_max, $y, $this->z) == Block::OBSIDIAN; $y++
				) {
					$x_max_y++;
				}
				for(
					$y = $this->y; $this->level->getBlockIdAt($x_min, $y, $this->z) == Block::OBSIDIAN; $y++
				) {
					$x_min_y++;
				}
				$y_max   = min($x_max_y, $x_min_y) - 1;
				$count_y = $y_max - $this->y + 2;
				if($count_y >= 5 and $count_y <= 23) {
					$count_up = 0;
					for(
						$ux = $x_min; ($this->level->getBlockIdAt($ux, $y_max, $this->z) == Block::OBSIDIAN and $ux <= $x_max); $ux++
					) {
						$count_up++;
					}
					if($count_up == $count_x) {
						for($px = $x_min + 1; $px < $x_max; $px++) {
							for($py = $this->y + 1; $py < $y_max; $py++) {
								$this->level->setBlock(new Vector3($px, $py, $this->z), new Portal());
							}
						}
						if($player->isSurvival()) {
							$item = clone $item;
							$item->applyDamage(1);
							$player->getInventory()->setItemInHand($item);
						}

						return true;
					}
				}
			}

			$z_max = $z_min = $this->z;
			for(
				$z = $this->z + 1; $this->level->getBlockIdAt($this->x, $this->y, $z) == Block::OBSIDIAN; $z++
			) {
				$z_max++;
			}
			for(
				$z = $this->z - 1; $this->level->getBlockIdAt($this->x, $this->y, $z) == Block::OBSIDIAN; $z--
			) {
				$z_min--;
			}
			$count_z = $z_max - $z_min + 1;
			if($count_z >= 4 and $count_z <= 23) {
				$z_max_y = $this->y;
				$z_min_y = $this->y;
				for(
					$y = $this->y; $this->level->getBlockIdAt($this->x, $y, $z_max) == Block::OBSIDIAN; $y++
				) {
					$z_max_y++;
				}
				for(
					$y = $this->y; $this->level->getBlockIdAt($this->x, $y, $z_min) == Block::OBSIDIAN; $y++
				) {
					$z_min_y++;
				}
				$y_max   = min($z_max_y, $z_min_y) - 1;
				$count_y = $y_max - $this->y + 2;
				if($count_y >= 5 and $count_y <= 23) {
					$count_up = 0;
					for(
						$uz = $z_min; ($this->level->getBlockIdAt($this->x, $y_max, $uz) == Block::OBSIDIAN and $uz <= $z_max); $uz++
					) {
						$count_up++;
					}
					if($count_up == $count_z) {
						for($pz = $z_min + 1; $pz < $z_max; $pz++) {
							for($py = $this->y + 1; $py < $y_max; $py++) {
								$this->level->setBlock(new Vector3($this->x, $py, $pz), new Portal());
							}
						}
						if($player->isSurvival()) {
							$item = clone $item;
							$item->applyDamage(1);
							$player->getInventory()->setItemInHand($item);
						}

						return true;
					}
				}
			}
		}

		return false;
	}

	public function onBreak(Item $item, Player $player = null): bool {
		parent::onBreak($item);
		foreach($this->getAllSides() as $i => $block){
			if($block instanceof Portal){
				if($block->getSide(Vector3::SIDE_WEST) instanceof Portal or
				   $block->getSide(Vector3::SIDE_EAST) instanceof Portal
				) {//x方向
					for(
						$x = $block->x; $this->getLevel()->getBlockIdAt($x, $block->y, $block->z) == Block::PORTAL; $x++
					) {
						for(
							$y = $block->y; $this->getLevel()->getBlockIdAt($x, $y, $block->z) == Block::PORTAL; $y++
						) {
							$this->getLevel()->setBlock(new Vector3($x, $y, $block->z), new Air());
						}
						for(
							$y = $block->y - 1; $this->getLevel()->getBlockIdAt($x, $y, $block->z) == Block::PORTAL; $y--
						) {
							$this->getLevel()->setBlock(new Vector3($x, $y, $block->z), new Air());
						}
					}
					for(
						$x = $block->x - 1; $this->getLevel()->getBlockIdAt($x, $block->y, $block->z) == Block::PORTAL; $x--
					) {
						for(
							$y = $block->y; $this->getLevel()->getBlockIdAt($x, $y, $block->z) == Block::PORTAL; $y++
						) {
							$this->getLevel()->setBlock(new Vector3($x, $y, $block->z), new Air());
						}
						for(
							$y = $block->y - 1; $this->getLevel()->getBlockIdAt($x, $y, $block->z) == Block::PORTAL; $y--
						) {
							$this->getLevel()->setBlock(new Vector3($x, $y, $block->z), new Air());
						}
					}
				} else {//z方向
					for(
						$z = $block->z; $this->getLevel()->getBlockIdAt($block->x, $block->y, $z) == Block::PORTAL; $z++
					) {
						for(
							$y = $block->y; $this->getLevel()->getBlockIdAt($block->x, $y, $z) == Block::PORTAL; $y++
						) {
							$this->getLevel()->setBlock(new Vector3($block->x, $y, $z), new Air());
						}
						for(
							$y = $block->y - 1; $this->getLevel()->getBlockIdAt($block->x, $y, $z) == Block::PORTAL; $y--
						) {
							$this->getLevel()->setBlock(new Vector3($block->x, $y, $z), new Air());
						}
					}
					for(
						$z = $block->z - 1;$this->getLevel()->getBlockIdAt($block->x, $block->y, $z) == Block::PORTAL; $z--
					) {
						for(
							$y = $block->y; $this->getLevel()->getBlockIdAt($block->x, $y, $z) == Block::PORTAL; $y++
						) {
							$this->getLevel()->setBlock(new Vector3($block->x, $y, $z), new Air());
						}
						for(
							$y = $block->y - 1; $this->getLevel()->getBlockIdAt($block->x, $y, $z) == Block::PORTAL; $y--
						) {
							$this->getLevel()->setBlock(new Vector3($block->x, $y, $z), new Air());
						}
					}
				}
				return true;
			}
		}

		return true;
	}
}
