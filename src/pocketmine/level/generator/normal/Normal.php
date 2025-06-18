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

namespace pocketmine\level\generator\normal;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\level\biome\Biome;
use pocketmine\level\ChunkManager;
use pocketmine\level\generator\biome\BiomeSelector;
use pocketmine\level\generator\Generator;
use pocketmine\level\generator\noise\Simplex;
use pocketmine\level\generator\object\OreType;
use pocketmine\level\generator\populator\GroundCover;
use pocketmine\level\generator\populator\Ore;
use pocketmine\level\generator\populator\Populator;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\utils\Random;
use function exp;

class Normal extends Generator{

	/** @var Populator[] */
	private $populators = [];
	/** @var int */
	private $waterHeight = 62;

	/** @var Populator[] */
	private $generationPopulators = [];
	/** @var Simplex */
	private $noiseBase;

	/** @var BiomeSelector */
	private $selector;

	/** @var float[][] */
	private static $GAUSSIAN_KERNEL = [
		[0.003, 0.013, 0.022, 0.013, 0.003],
		[0.013, 0.059, 0.097, 0.059, 0.013],
		[0.022, 0.097, 0.159, 0.097, 0.022],
		[0.013, 0.059, 0.097, 0.059, 0.013],
		[0.003, 0.013, 0.022, 0.013, 0.003]
	];

	public function __construct(array $settings = [])
	{
		parent::__construct($settings);
	}

	public function getName() : string{
		return "normal";
	}

	public function getSettings() : array{
		return [];
	}

	private function pickBiome(int $x, int $z) : Biome{
		$hash = $x * 2345803 ^ $z * 9236449 ^ $this->level->getSeed();
		$hash *= $hash + 223;
		$xNoise = intval($hash) >> 20 & 3;
		$zNoise = intval($hash) >> 22 & 3;
		if($xNoise == 3){
			$xNoise = 1;
		}
		if($zNoise == 3){
			$zNoise = 1;
		}

		return $this->selector->pickBiome($x + $xNoise - 1, $z + $zNoise - 1);
	}

	public function init(ChunkManager $level, Random $random) : void{
		parent::init($level, $random);
		$this->random->setSeed($this->level->getSeed());
		$this->noiseBase = new Simplex($this->random, 4, 1 / 4, 1 / 32);
		$this->random->setSeed($this->level->getSeed());
		$this->selector = new class($this->random) extends BiomeSelector{
			protected function lookup(float $temperature, float $rainfall) : int{
				if($rainfall < 0.25){
					if($temperature < 0.7){
						return Biome::OCEAN;
					}elseif($temperature < 0.85){
						return Biome::RIVER;
					}else{
						return Biome::SWAMP;
					}
				}elseif($rainfall < 0.60){
					if($temperature < 0.25){
						return Biome::ICE_PLAINS;
					}elseif($temperature < 0.75){
						return Biome::PLAINS;
					}else{
						return Biome::DESERT;
					}
				}elseif($rainfall < 0.80){
					if($temperature < 0.25){
						return Biome::TAIGA;
					}elseif($temperature < 0.75){
						return Biome::FOREST;
					}else{
						return Biome::BIRCH_FOREST;
					}
				}else{
					//Previously here, we had a (broken) condition to generate mountains, but fixing it would have
					//caused generation changes on a patch release, so we can't keep it here for now.
					return Biome::RIVER;
				}
			}
		};

		$this->selector->recalculate();

		$cover = new GroundCover();
		$this->generationPopulators[] = $cover;

		$ores = new Ore();
		$ores->setOreTypes([
			new OreType(BlockFactory::get(Block::COAL_ORE), 20, 16, 0, 128),
			new OreType(BlockFactory::get(Block::IRON_ORE), 20, 8, 0, 64),
			new OreType(BlockFactory::get(Block::REDSTONE_ORE), 8, 7, 0, 16),
			new OreType(BlockFactory::get(Block::LAPIS_ORE), 1, 6, 0, 32),
			new OreType(BlockFactory::get(Block::GOLD_ORE), 2, 8, 0, 32),
			new OreType(BlockFactory::get(Block::DIAMOND_ORE), 1, 7, 0, 16),
			new OreType(BlockFactory::get(Block::DIRT), 20, 32, 0, 128),
			new OreType(BlockFactory::get(Block::GRAVEL), 10, 16, 0, 128)
		]);
		$this->populators[] = $ores;
	}

	public function generateChunk(int $chunkX, int $chunkZ) : void{
		$this->random->setSeed(0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->level->getSeed());

		$noise = $this->noiseBase->getFastNoise3D(16, 128, 16, 4, 8, 4, $chunkX * 16, 0, $chunkZ * 16);

		$chunk = $this->level->getChunk($chunkX, $chunkZ);

		$biomeCache = [];

		$bedrockRid = RuntimeBlockMapping::toStaticRuntimeId(Block::BEDROCK);
		$stoneRid = RuntimeBlockMapping::toStaticRuntimeId(Block::STONE);
		$stillWaterRid = RuntimeBlockMapping::toStaticRuntimeId(Block::STILL_WATER);
		for($x = 0; $x < 16; ++$x){
			for($z = 0; $z < 16; ++$z){
				$minSum = 0;
				$maxSum = 0;
				$weightSum = 0;

				$biome = $this->pickBiome($chunkX * 16 + $x, $chunkZ * 16 + $z);
				for($y = 0; $y <= Level::Y_MAX; $y++){
					$chunk->setBiomeId($x, Level::Y_MIN+$y, $z, $biome->getId());
				}

				for($sx = -2; $sx <= 2; ++$sx){
					for($sz = -2; $sz <= 2; ++$sz){
						$weight = self::$GAUSSIAN_KERNEL[$sx + 2][$sz + 2];

						if($sx === 0 and $sz === 0){
							$adjacent = $biome;
						}else{
							$index = Level::chunkHash($chunkX * 16 + $x + $sx, $chunkZ * 16 + $z + $sz);
							if(isset($biomeCache[$index])){
								$adjacent = $biomeCache[$index];
							}else{
								$biomeCache[$index] = $adjacent = $this->pickBiome($chunkX * 16 + $x + $sx, $chunkZ * 16 + $z + $sz);
							}
						}

						$minSum += ($adjacent->getMinElevation() - 1) * $weight;
						$maxSum += $adjacent->getMaxElevation() * $weight;

						$weightSum += $weight;
					}
				}

				$minSum /= $weightSum;
				$maxSum /= $weightSum;

				$smoothHeight = ($maxSum - $minSum) / 2;

				for($y = 0; $y < 128; ++$y){
					if($y === 0){
						$chunk->setBlockId($x, $y, $z, $bedrockRid, 0);
						continue;
					}
					$noiseValue = $noise[$x][$z][$y] - 1 / $smoothHeight * ($y - $smoothHeight - $minSum);

					if($noiseValue > 0){
						$chunk->setBlockId($x, $y, $z, $stoneRid, 0);
					}elseif($y <= $this->waterHeight){
						$chunk->setBlockId($x, $y, $z, $stillWaterRid, 0);
					}
				}
			}
		}

		foreach($this->generationPopulators as $populator){
			$populator->populate($this->level, $chunkX, $chunkZ, $this->random);
		}
	}

	public function populateChunk(int $chunkX, int $chunkZ) : void{
		$this->random->setSeed(0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->level->getSeed());
		foreach($this->populators as $populator){
			$populator->populate($this->level, $chunkX, $chunkZ, $this->random);
		}

		$chunk = $this->level->getChunk($chunkX, $chunkZ);
		$biome = Biome::getBiome($chunk->getBiomeId(7, 7,7));
		$biome->populateChunk($this->level, $chunkX, $chunkZ, $this->random);
	}

	public function getSpawn() : Vector3{
		return new Vector3(127.5, 128, 127.5);
	}
}
