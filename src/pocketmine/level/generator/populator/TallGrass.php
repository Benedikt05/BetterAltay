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

namespace pocketmine\level\generator\populator;

use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\block\Leaves;
use pocketmine\level\ChunkManager;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\utils\Random;

class TallGrass extends Populator{
	/** @var ChunkManager */
	private $level;
	/** @var int */
	private $randomAmount = 1;
	/** @var int */
	private $baseAmount = 0;

	/**
	 * @param int $amount
	 *
	 * @return void
	 */
	public function setRandomAmount($amount){
		$this->randomAmount = $amount;
	}

	/**
	 * @param int $amount
	 *
	 * @return void
	 */
	public function setBaseAmount($amount){
		$this->baseAmount = $amount;
	}

	public function populate(ChunkManager $level, int $chunkX, int $chunkZ, Random $random){
		$this->level = $level;
		$amount = $random->nextRange(0, $this->randomAmount) + $this->baseAmount;
		$rid = RuntimeBlockMapping::toRuntimeId(BlockIds::TALL_GRASS, 1);
		for($i = 0; $i < $amount; ++$i){
			$x = $random->nextRange($chunkX * 16, $chunkX * 16 + 15);
			$z = $random->nextRange($chunkZ * 16, $chunkZ * 16 + 15);
			$y = $this->getHighestWorkableBlock($x, $z);

			if($y !== -1 and $this->canTallGrassStay($x, $y, $z)){
				$this->level->setBlockIdAt($x, $y, $z, $rid);
			}
		}
	}

	private function canTallGrassStay(int $x, int $y, int $z) : bool{
		$id = RuntimeBlockMapping::getIdFromRuntimeId( $this->level->getBlockIdAt($x, $y, $z));
		$down = RuntimeBlockMapping::getIdFromRuntimeId($this->level->getBlockIdAt($x, $y - 1, $z));
		return ($id === BlockIds::AIR or $id === BlockIds::SNOW_LAYER) and $down === BlockIds::GRASS_BLOCK;
	}

	private function getHighestWorkableBlock(int $x, int $z) : int{
		for($y = 127; $y >= 0; --$y){
			$rid = $this->level->getBlockIdAt($x, $y, $z);
			[$id, $meta] = RuntimeBlockMapping::fromRuntimeId($rid);
			$block = BlockFactory::get($id, $meta);
			if($rid === RuntimeBlockMapping::AIR() && !$block instanceof Leaves && $id !== BlockIds::SNOW_LAYER){
				return $y + 1;
			}
		}

		return -1;
	}
}
