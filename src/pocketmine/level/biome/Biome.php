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

namespace pocketmine\level\biome;

use pocketmine\block\Block;
use pocketmine\entity\MCAnimal;
use pocketmine\entity\Creature;
use pocketmine\entity\CreatureType;
use pocketmine\entity\hostile\Creeper;
use pocketmine\entity\hostile\Skeleton;
use pocketmine\entity\hostile\Slime;
use pocketmine\entity\hostile\Spider;
use pocketmine\entity\hostile\Zombie;
use pocketmine\entity\Monster;
use pocketmine\entity\passive\Cat;
use pocketmine\entity\passive\Chicken;
use pocketmine\entity\passive\Cow;
use pocketmine\entity\passive\Pig;
use pocketmine\entity\passive\Sheep;
use pocketmine\entity\passive\Squid;
use pocketmine\entity\WaterAnimal;
use pocketmine\level\ChunkManager;
use pocketmine\level\generator\populator\Populator;
use pocketmine\utils\Random;

abstract class Biome{

	public const OCEAN = 0;
	public const PLAINS = 1;
	public const DESERT = 2;
	public const MOUNTAINS = 3;
	public const FOREST = 4;
	public const TAIGA = 5;
	public const SWAMP = 6;
	public const RIVER = 7;

	public const HELL = 8;
	public const END = 9;

	public const ICE_PLAINS = 12;

	public const SMALL_MOUNTAINS = 20;

	public const BIRCH_FOREST = 27;

	public const MAX_BIOMES = 256;

	/**
	 * @var Biome[]|\SplFixedArray
	 * @phpstan-var \SplFixedArray<Biome>
	 */
	private static $biomes;

	/** @var int */
	private $id;
	/** @var bool */
	private $registered = false;

	/** @var Populator[] */
	private $populators = [];

	/** @var int */
	private $minElevation;
	/** @var int */
	private $maxElevation;

	/** @var Block[] */
	private $groundCover = [];

	/** @var float */
	protected $rainfall = 0.5;
	/** @var float */
	protected $temperature = 0.5;

	/** @var SpawnListEntry[] */
	protected $spawnableMonsterList = [];
	/** @var SpawnListEntry[] */
	protected $spawnableCreatureList = [];
	/** @var SpawnListEntry[] */
	protected $spawnableWaterCreatureList = [];
	/** @var SpawnListEntry[] */
	protected $spawnableCaveCreatureList = [];

	public function __construct(){
		$this->spawnableCreatureList[] = new SpawnListEntry(Sheep::class, 12, 4, 4);
		//$this->spawnableCreatureList[] = new SpawnListEntry(Rabbit::class, 10, 3, 3);
		$this->spawnableCreatureList[] = new SpawnListEntry(Pig::class, 10, 4, 4);
		$this->spawnableCreatureList[] = new SpawnListEntry(Chicken::class, 10, 4, 4);
		$this->spawnableCreatureList[] = new SpawnListEntry(Cow::class, 8, 4, 4);
		$this->spawnableCreatureList[] = new SpawnListEntry(Cat::class, 8, 4, 4);
		$this->spawnableMonsterList[] = new SpawnListEntry(Spider::class, 100, 4, 4);
		$this->spawnableMonsterList[] = new SpawnListEntry(Zombie::class, 100, 4, 4);
		$this->spawnableMonsterList[] = new SpawnListEntry(Skeleton::class, 100, 4, 4);
		$this->spawnableMonsterList[] = new SpawnListEntry(Creeper::class, 100, 4, 4);
		$this->spawnableMonsterList[] = new SpawnListEntry(Slime::class, 100, 4, 4);
		//$this->spawnableMonsterList[] = new SpawnListEntry(Enderman::class, 10, 1, 4);
		//$this->spawnableMonsterList[] = new SpawnListEntry(Witch::class, 5, 1, 1);
		$this->spawnableWaterCreatureList[] = new SpawnListEntry(Squid::class, 10, 4, 4);
		//$this->spawnableCaveCreatureList[] = new SpawnListEntry(Bat::class, 10, 8, 8);
	}

	/**
	 * @return void
	 */
	protected static function register(int $id, Biome $biome){
		self::$biomes[$id] = $biome;
		$biome->setId($id);
	}

	/**
	 * @return void
	 */
	public static function init(){
		self::$biomes = new \SplFixedArray(self::MAX_BIOMES);

		self::register(self::OCEAN, new OceanBiome());
		self::register(self::PLAINS, new PlainBiome());
		self::register(self::DESERT, new DesertBiome());
		self::register(self::MOUNTAINS, new MountainsBiome());
		self::register(self::FOREST, new ForestBiome());
		self::register(self::TAIGA, new TaigaBiome());
		self::register(self::SWAMP, new SwampBiome());
		self::register(self::RIVER, new RiverBiome());

		self::register(self::HELL, new HellBiome());

		self::register(self::ICE_PLAINS, new IcePlainsBiome());
		self::register(self::HELL, new HellBiome());
		self::register(self::END, new EndBiome());
		self::register(self::SMALL_MOUNTAINS, new SmallMountainsBiome());

		self::register(self::BIRCH_FOREST, new ForestBiome(ForestBiome::TYPE_BIRCH));
	}

	public static function getBiome(int $id) : Biome{
		if(self::$biomes[$id] === null){
			self::register($id, new UnknownBiome());
		}
		return self::$biomes[$id];
	}

	/**
	 * @return void
	 */
	public function clearPopulators(){
		$this->populators = [];
	}

	/**
	 * @return void
	 */
	public function addPopulator(Populator $populator){
		$this->populators[] = $populator;
	}

	/**
	 * @return void
	 */
	public function populateChunk(ChunkManager $level, int $chunkX, int $chunkZ, Random $random){
		foreach($this->populators as $populator){
			$populator->populate($level, $chunkX, $chunkZ, $random);
		}
	}

	/**
	 * @return Populator[]
	 */
	public function getPopulators() : array{
		return $this->populators;
	}

	/**
	 * @return void
	 */
	public function setId(int $id){
		if(!$this->registered){
			$this->registered = true;
			$this->id = $id;
		}
	}

	public function getId() : int{
		return $this->id;
	}

	abstract public function getName() : string;

	public function getMinElevation() : int{
		return $this->minElevation;
	}

	public function getMaxElevation() : int{
		return $this->maxElevation;
	}

	/**
	 * @return void
	 */
	public function setElevation(int $min, int $max){
		$this->minElevation = $min;
		$this->maxElevation = $max;
	}

	/**
	 * @return Block[]
	 */
	public function getGroundCover() : array{
		return $this->groundCover;
	}

	/**
	 * @param Block[] $covers
	 *
	 * @return void
	 */
	public function setGroundCover(array $covers){
		$this->groundCover = $covers;
	}

	public function getTemperature() : float{
		return $this->temperature;
	}

	public function getRainfall() : float{
		return $this->rainfall;
	}

	/**
	 * @return SpawnListEntry[]
	 */
	public function getSpawnableList(CreatureType $creatureType) : array{
		$entityClass = $creatureType->getCreatureClass();
		switch($entityClass){
			case WaterAnimal::class:
				return $this->spawnableWaterCreatureList;
			case Creature::class:
				return $this->spawnableCaveCreatureList;
			case MCAnimal::class:
				return $this->spawnableCreatureList;
			case Monster::class:
				return $this->spawnableMonsterList;
		}

		return [];
	}

	public function getSpawningChance() : float{
		return 0.1;
	}
}