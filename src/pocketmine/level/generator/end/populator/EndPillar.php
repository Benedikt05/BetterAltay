<?php

/*
 *               _ _
 *         /\   | | |
 *        /  \  | | |_ __ _ _   _
 *       / /\ \ | | __/ _` | | | |
 *      / ____ \| | || (_| | |_| |
 *     /_/    \_|_|\__\__,_|\__, |
 *                           __/ |
 *                          |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author TuranicTeam
 * @link https://github.com/TuranicTeam/Altay
 *
 */

declare(strict_types=1);

namespace pocketmine\level\generator\end\populator;

use pocketmine\block\Block;
use pocketmine\level\ChunkManager;
use pocketmine\level\generator\populator\Populator;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\utils\Random;
use function cos;
use function deg2rad;
use function intval;
use function mt_rand;
use function pi;
use function sin;

class EndPillar extends Populator{
	/** @var ChunkManager */
	private $level;
	private $randomAmount;
	private $baseAmount;

	public function __construct(int $randomAmount = 0, int $baseAmount = 0){
		$this->baseAmount = $baseAmount;
		$this->randomAmount = $randomAmount;
	}

	public function setRandomAmount($amount){
		$this->randomAmount = $amount;
	}

	public function setBaseAmount($amount){
		$this->baseAmount = $amount;
	}

	public function populate(ChunkManager $level, $chunkX, $chunkZ, Random $random) : void{
		if(mt_rand(0, 99) < 10){
			$this->level = $level;
			$amount = $random->nextRange(0, $this->randomAmount + 1) + $this->baseAmount;
			$rid = RuntimeBlockMapping::toStaticRuntimeId(Block::OBSIDIAN);
			for($i = 0; $i < $amount; ++$i){
				$x = $random->nextRange($chunkX * 16, $chunkX * 16 + 15);
				$z = $random->nextRange($chunkZ * 16, $chunkZ * 16 + 15);
				$y = $this->getHighestWorkableBlock($x, $z);
				[$id, ] = RuntimeBlockMapping::fromStaticRuntimeId($this->level->getBlockIdAt($x, $y, $z));
				if($id == Block::END_STONE){
					$height = mt_rand(28, 50);
					for($ny = $y; $ny < $y + $height; $ny++){
						for($r = 0.5; $r < 5; $r += 0.5){
							$nd = 360 / (2 * pi() * $r);
							for($d = 0; $d < 360; $d += $nd){
								$level->setBlockIdAt(intval($x + (cos(deg2rad($d)) * $r)), $ny, intval($z + (sin(deg2rad($d)) * $r)), $rid);
							}
						}
					}
				}
			}
		}
	}


	private function getHighestWorkableBlock($x, $z){
		for($y = 127; $y >= 0; --$y){
			[$b, ] = RuntimeBlockMapping::fromStaticRuntimeId($this->level->getBlockIdAt($x, $y, $z));
			if($b == Block::END_STONE){
				break;
			}
		}

		return $y === 0 ? -1 : $y;
	}
}