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

namespace pocketmine\level\generator;

use InvalidArgumentException;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\level\ChunkManager;
use pocketmine\level\format\Chunk;
use pocketmine\level\generator\object\OreType;
use pocketmine\level\generator\populator\Ore;
use pocketmine\level\generator\populator\Populator;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use function array_map;
use function count;
use function explode;
use function preg_match;
use function preg_match_all;
use function trim;

class Flat extends Generator{

	/** @var Populator[] */
	private $populators = [];

	/** @var array<int, string}> */
	private $structure = [];

	/** @var int */
	private $floorLevel = 0;

	/** @var int */
	private $biome = 1; // plains

	/** @var mixed[] */
	private $options = [];

	/** @var string */
	private $preset = "";

	/** @var ChunkManager */
	protected $level;

	/** @var Random */
	protected $random;

	public function __construct(array $options = []){
		$this->options = $options;
		$this->random = new Random();

		if(isset($this->options["preset"]) && $this->options["preset"] !== ""){
			$this->preset = (string) $this->options["preset"];
		}else{
			$this->preset = implode(";", [
				implode(",", [
					BlockIds::BEDROCK,
					"2x" . BlockIds::DIRT,
					BlockIds::GRASS_BLOCK,
				]),
				"1",
				""
			]);
		}

		$this->parsePreset();

		if(isset($this->options["decoration"])){
			$ores = new Ore();
			$ores->setOreTypes([
				new OreType(BlockFactory::get(BlockIds::COAL_ORE), 20, 16, 0, 128),
				new OreType(BlockFactory::get(BlockIds::IRON_ORE), 20, 8, 0, 64),
				new OreType(BlockFactory::get(BlockIds::REDSTONE_ORE), 8, 7, 0, 16),
				new OreType(BlockFactory::get(BlockIds::LAPIS_ORE), 1, 6, 0, 32),
				new OreType(BlockFactory::get(BlockIds::GOLD_ORE), 2, 8, 0, 32),
				new OreType(BlockFactory::get(BlockIds::DIAMOND_ORE), 1, 7, 0, 16),
				new OreType(BlockFactory::get(BlockIds::DIRT), 20, 32, 0, 128),
				new OreType(BlockFactory::get(BlockIds::GRAVEL), 10, 16, 0, 128),
			]);
			$this->populators[] = $ores;
		}
	}

	public function getName() : string{
		return "flat";
	}

	public function getSettings() : array{
		return $this->options;
	}

	public function init(ChunkManager $level, Random $random) : void{
		$this->level = $level;
		$this->random = $random;
	}

	/**
	 * @throws InvalidGeneratorOptionsException
	 */
	protected function parsePreset() : void{
		$parts = explode(";", $this->preset);
		$layers = $parts[0] ?? "";
		$this->biome = (int) ($parts[1] ?? 1);
		$options = $parts[2] ?? "";

		$this->structure = self::parseLayers($layers);
		$this->floorLevel = max(0, count($this->structure) - 1);

		//TODO: more error checking
		preg_match_all('#(([0-9a-z_]{1,})\(?([0-9a-z_ =:.\-]{0,})\)?),?#i', $options, $matches);
		foreach($matches[2] as $i => $option){
			$params = true;
			if(($matches[3][$i] ?? "") !== ""){
				$params = [];
				$p = explode(" ", $matches[3][$i]);
				foreach($p as $kv){
					$kv = explode("=", $kv, 2);
					if(isset($kv[1])){
						$params[$kv[0]] = $kv[1];
					}
				}
			}
			$this->options[$option] = $params;
		}
	}

	public static function parseLayers(string $layers) : array{
		$result = [];
		$tokens = array_map('\trim', explode(',', $layers, Level::Y_MAX - Level::Y_MIN));
		$y = -64;
		foreach($tokens as $token){
			if($token === ""){
				continue;
			}

			$count = 1;
			$blockStr = $token;

			if(preg_match('#^(\d+)[x\*](.+)$#i', $token, $m)){
				$count = (int) $m[1];
				$blockStr = trim($m[2]);
			}else if(preg_match('#^(.+)[x\*](\d+)$#i', $token, $m)){
				$blockStr = trim($m[1]);
				$count = (int) $m[2];
			}

			try{
				$block = BlockFactory::get($blockStr);
			}catch(InvalidArgumentException $e){
				throw new InvalidGeneratorOptionsException("Invalid preset layer \"$token\": " . $e->getMessage(), 0, $e);
			}

			for($cY = $y, $y += $count; $cY < $y; ++$cY){
				$result[$cY] = $block->getId();
			}
		}


		return $result;
	}

	public function generateChunk(int $chunkX, int $chunkZ) : void{
		$this->random->setSeed(0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->level->getSeed());

		/** @var Chunk $chunk */
		$chunk = $this->level->getChunk($chunkX, $chunkZ);

		foreach($this->structure as $y => $id){
			$rid = BlockFactory::get($id)->getRuntimeId();
			for($x = 0; $x < 16; ++$x){
				for($z = 0; $z < 16; ++$z){
					$chunk->setBlockId($x, $y, $z, $rid);
				}
			}
		}

		for($x = 0; $x < 16; ++$x){
			for($z = 0; $z < 16; ++$z){
				$chunk->setBiomeId($x, 7, $z, $this->biome);
				if(method_exists($chunk, 'setHeightMap')){
					$chunk->setHeightMap($x, $z, $this->floorLevel);
				}
			}
		}

		if(method_exists($chunk, 'recalculateHeightMap')){
			$chunk->recalculateHeightMap();
		}
		if(method_exists($chunk, 'initLighting')){
			$chunk->initLighting();
		}

		$this->level->setChunk($chunkX, $chunkZ, $chunk);
	}

	public function populateChunk(int $chunkX, int $chunkZ) : void{
		if(empty($this->populators)){
			return;
		}
		$this->random->setSeed($this->level->getSeed());
		$this->random->setSeed($this->random->nextInt() ^ ($chunkX << 8) ^ $chunkZ ^ $this->level->getSeed());
		foreach($this->populators as $populator){
			$populator->populate($this->level, $chunkX, $chunkZ, $this->random);
		}
	}

	public function getSpawn() : Vector3{
		return new Vector3(0.5, -64 + $this->floorLevel + 1, 0.5);
	}
}
