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

namespace pocketmine\level\generator\object;

use pocketmine\block\BlockFactory;
use pocketmine\block\BlockNames;
use pocketmine\block\material\WoodType;
use pocketmine\level\ChunkManager;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\utils\Random;
use function abs;

abstract class Tree{
	/** @var bool[] */
	public $overridable = [
		BlockNames::AIR => true,
		BlockNames::OAK_SAPLING => true,
		BlockNames::SPRUCE_SAPLING => true,
		BlockNames::ACACIA_SAPLING => true,
		BlockNames::BAMBOO_SAPLING => true,
		BlockNames::BIRCH_SAPLING => true,
		BlockNames::CHERRY_SAPLING => true,
		BlockNames::DARK_OAK_SAPLING => true,
		BlockNames::JUNGLE_SAPLING => true,
		BlockNames::PALE_OAK_SAPLING => true,
		BlockNames::OAK_LEAVES => true,
		BlockNames::SPRUCE_LEAVES => true,
		BlockNames::ACACIA_LEAVES => true,
		BlockNames::BIRCH_LEAVES => true,
		BlockNames::CHERRY_LEAVES => true,
		BlockNames::DARK_OAK_LEAVES => true,
		BlockNames::JUNGLE_LEAVES => true,
		BlockNames::PALE_OAK_LEAVES => true,
		BlockNames::SNOW_LAYER => true,
	];

	/** @var string */
	public $trunkBlock = BlockNames::OAK_LOG;
	/** @var string */
	public $leafBlock = BlockNames::OAK_LEAVES;
	/** @var int */
	public $treeHeight = 7;

	/**
	 * @return void
	 */
	public static function growTree(ChunkManager $level, int $x, int $y, int $z, Random $random, WoodType $type){
		switch(true){
			case $type->equals(WoodType::SPRUCE()):
				$tree = new SpruceTree();
				break;
			case $type->equals(WoodType::BIRCH()):
				if($random->nextBoundedInt(39) === 0){
					$tree = new BirchTree(true);
				}else{
					$tree = new BirchTree();
				}
				break;
			case $type->equals(WoodType::JUNGLE()):
				$tree = new JungleTree();
				break;
			case $type->equals(WoodType::ACACIA()):
			case $type->equals(WoodType::CHERRY()):
			case $type->equals(WoodType::MANGROVE()):
			case $type->equals(WoodType::PALE_OAK()):
			case $type->equals(WoodType::DARK_OAK()):
				return; //TODO
			default:
				$tree = new OakTree();
				/*if($random->nextRange(0, 9) === 0){
					$tree = new BigTree();
				}else{*/

				//}
				break;
		}
		if($tree->canPlaceObject($level, $x, $y, $z, $random)){
			$tree->placeObject($level, $x, $y, $z, $random);
		}
	}

	public function canPlaceObject(ChunkManager $level, int $x, int $y, int $z, Random $random) : bool{
		$radiusToCheck = 0;
		for($yy = 0; $yy < $this->treeHeight + 3; ++$yy){
			if($yy === 1 or $yy === $this->treeHeight){
				++$radiusToCheck;
			}
			for($xx = -$radiusToCheck; $xx < ($radiusToCheck + 1); ++$xx){
				for($zz = -$radiusToCheck; $zz < ($radiusToCheck + 1); ++$zz){
					$id = RuntimeBlockMapping::getIdFromRuntimeId($level->getBlockIdAt($x + $xx, $y + $yy, $z + $zz));
					if(!isset($this->overridable[$id])){
						return false;
					}
				}
			}
		}

		return true;
	}

	/**
	 * @return void
	 */
	public function placeObject(ChunkManager $level, int $x, int $y, int $z, Random $random){

		$this->placeTrunk($level, $x, $y, $z, $random, $this->treeHeight - 1);

		$rid = RuntimeBlockMapping::toRuntimeId($this->leafBlock);
		for($yy = $y - 3 + $this->treeHeight; $yy <= $y + $this->treeHeight; ++$yy){
			$yOff = $yy - ($y + $this->treeHeight);
			$mid = (int) (1 - $yOff / 2);
			for($xx = $x - $mid; $xx <= $x + $mid; ++$xx){
				$xOff = abs($xx - $x);
				for($zz = $z - $mid; $zz <= $z + $mid; ++$zz){
					$zOff = abs($zz - $z);
					if($xOff === $mid and $zOff === $mid and ($yOff === 0 or $random->nextBoundedInt(2) === 0)){
						continue;
					}
					$id = RuntimeBlockMapping::getIdFromRuntimeId($level->getBlockIdAt($xx, $yy, $zz));
					if(!(BlockFactory::$solid[$id] ?? true)){
						$level->setBlockIdAt($xx, $yy, $zz, $rid);
					}
				}
			}
		}
	}

	/**
	 * @return void
	 */
	protected function placeTrunk(ChunkManager $level, int $x, int $y, int $z, Random $random, int $trunkHeight){
		// The base dirt block
		$level->setBlockIdAt($x, $y - 1, $z, RuntimeBlockMapping::toRuntimeId(BlockNames::DIRT));

		$rid = RuntimeBlockMapping::toRuntimeId($this->trunkBlock);
		for($yy = 0; $yy < $trunkHeight; ++$yy){
			$id =  RuntimeBlockMapping::getIdFromRuntimeId($level->getBlockIdAt($x, $y + $yy, $z));
			if(isset($this->overridable[$id])){
				$level->setBlockIdAt($x, $y + $yy, $z, $rid);
			}
		}
	}
}
