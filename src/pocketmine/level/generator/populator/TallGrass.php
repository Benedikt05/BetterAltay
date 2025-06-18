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

use pocketmine\block\Block;
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
		$rid = RuntimeBlockMapping::toStaticRuntimeId(Block::TALL_GRASS, 1);
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
		[$b, ] = RuntimeBlockMapping::fromStaticRuntimeId( $this->level->getBlockIdAt($x, $y, $z));
		[$up, ] = RuntimeBlockMapping::fromStaticRuntimeId($this->level->getBlockIdAt($x, $y - 1, $z));
		return ($b === Block::AIR or $b === Block::SNOW_LAYER) and $up === Block::GRASS;
	}

	private function getHighestWorkableBlock(int $x, int $z) : int{
		for($y = 127; $y >= 0; --$y){
			$rid = $this->level->getBlockIdAt($x, $y, $z);
			[$b, ] = RuntimeBlockMapping::fromStaticRuntimeId($rid);
			if($rid === RuntimeBlockMapping::AIR() and $b !== Block::LEAVES and $b !== Block::LEAVES2 and $b !== Block::SNOW_LAYER){
				return $y + 1;
			}
		}

		return -1;
	}
}
