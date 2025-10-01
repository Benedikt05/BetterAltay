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

/**
 * Implementation of MCPE-style chunks with subchunks with XZY ordering.
 */
declare(strict_types=1);

namespace pocketmine\level\format;

use InvalidArgumentException;
use pocketmine\block\BlockFactory;
use pocketmine\entity\Entity;
use pocketmine\level\biome\Biome;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\Player;
use pocketmine\tile\Spawnable;
use pocketmine\tile\Tile;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Binary;
use pocketmine\utils\BinaryStream;
use pocketmine\world\format\PalettedBlockArray;
use SplFixedArray;
use Throwable;
use function array_fill;
use function array_filter;
use function array_flip;
use function array_values;
use function assert;
use function chr;
use function count;
use function file_get_contents;
use function is_array;
use function json_decode;
use function ord;
use function pack;
use function str_repeat;
use function strlen;
use function unpack;
use const pocketmine\RESOURCE_PATH;

class Chunk{

	public const MAX_SUBCHUNKS = 19;
	public const MIN_SUBCHUNKS = -4;

	public const COORD_MASK = 0x0F;

	/** @var int */
	protected $x;
	/** @var int */
	protected $z;

	protected $dimension;

	/** @var bool */
	protected $hasChanged = false;

	/** @var bool */
	protected $isInit = false;

	/** @var bool */
	protected $lightPopulated = false;
	/** @var bool */
	protected $terrainGenerated = false;
	/** @var bool */
	protected $terrainPopulated = false;
	/** @var int */
	protected $inhabitedTime = 0;

	/** @var int */
	protected $height = Chunk::MAX_SUBCHUNKS;

	/** @var SubChunkInterface[] */
	protected $subChunks = [];

	/** @var EmptySubChunk */
	protected $emptySubChunk;

	/** @var Tile[] */
	protected $tiles = [];
	/** @var Tile[] */
	protected $tileList = [];

	/** @var Entity[] */
	protected $entities = [];

	/**
	 * @var SplFixedArray|int[]
	 * @phpstan-var SplFixedArray<int>
	 */
	protected $heightMap;
	/** @var PalettedBlockArray[] */
	protected $biomes = [];

	/** @var CompoundTag[] */
	protected $NBTtiles = [];

	/** @var CompoundTag[] */
	protected $NBTentities = [];

	/**
	 * @param SubChunkInterface[] $subChunks
	 * @param CompoundTag[]       $entities
	 * @param CompoundTag[]       $tiles
	 * @param int[]               $heightMap
	 *
	 * @phpstan-param list<int>   $heightMap
	 */
	public function __construct(int $chunkX, int $chunkZ, int $dimension, array $subChunks = [], array $entities = [], array $tiles = [], array $biomes = [], array $heightMap = []){
		$this->x = $chunkX;
		$this->z = $chunkZ;
		if ($dimension < 0 || $dimension > 2) {
			throw new \InvalidArgumentException("Invalid dimension: " . $dimension);
		}
		$this->dimension = $dimension;

		$this->height = self::getMaxSubChunk($dimension); //TODO: add a way of changing this
		$this->emptySubChunk = EmptySubChunk::getInstance();

		$max = self::getMaxSubChunk($dimension);
		for ($y = self::getMinSubChunk($dimension); $y <= $max; ++$y) {
			if (isset($subChunks[$y]) && $subChunks[$y] instanceof SubChunk){
				$this->subChunks[$y] = $subChunks[$y];
			} else {
				$this->subChunks[$y] = new SubChunk(RuntimeBlockMapping::AIR());
			}

			if (isset($biomes[$y]) && $biomes[$y] instanceof PalettedBlockArray){
				$this->biomes[$y] = $biomes[$y];
			} else {
				$this->biomes[$y] = new PalettedBlockArray(Biome::OCEAN);
			}
		}

		if(count($heightMap) === 256){
			$this->heightMap = SplFixedArray::fromArray($heightMap);
		}else{
			assert(count($heightMap) === 0, "Wrong HeightMap value count, expected 256, got " . count($heightMap));
			$val = ($this->height * 16);
			$this->heightMap = SplFixedArray::fromArray(array_fill(0, 256, $val));
		}

		$this->NBTtiles = $tiles;
		$this->NBTentities = $entities;
	}

	public function getX() : int{
		return $this->x;
	}

	public function getZ() : int{
		return $this->z;
	}

	public function getDimension() : int{
		return $this->dimension;
	}

	/**
	 * @return void
	 */
	public function setX(int $x){
		$this->x = $x;
	}

	/**
	 * @return void
	 */
	public function setZ(int $z){
		$this->z = $z;
	}

	/**
	 * Returns the chunk height in count of subchunks.
	 */
	public function getHeight() : int{
		return $this->height;
	}

	/**
	 * Gets the runtime ID of the block at the specified coordinates.
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $layer 0-3
	 *
	 * @return int
	 */
	public function getBlockId(int $x, int $y, int $z, int $layer) : int{
		return $this->getSubChunk($y >> 4)->getBlockId($x, $y & self::COORD_MASK, $z, $layer);
	}

	/**
	 * Sets the runtime ID of the block at the specified coordinates.
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $id
	 * @param int $layer 0-3
	 *
	 * @return bool
	 */
	public function setBlockId(int $x, int $y, int $z, int $id, int $layer) : bool{
		if($this->getSubChunk($y >> 4, true)->setBlockId($x, $y & self::COORD_MASK, $z, $id, $layer)){
			$this->hasChanged = true;
			return true;
		}

		return false;
	}

	/**
	 * Returns the sky light level at the specified chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $y 0-255
	 * @param int $z 0-15
	 *
	 * @return int 0-15
	 */
	public function getBlockSkyLight(int $x, int $y, int $z) : int{
		return $this->getSubChunk($y >> 4)->getBlockSkyLight($x, $y & self::COORD_MASK, $z);
	}

	/**
	 * Sets the sky light level at the specified chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $y 0-255
	 * @param int $z 0-15
	 * @param int $level 0-15
	 *
	 * @return void
	 */
	public function setBlockSkyLight(int $x, int $y, int $z, int $level) : void{
		if($this->getSubChunk($y >> 4, true)->setBlockSkyLight($x, $y & self::COORD_MASK, $z, $level)){
			$this->hasChanged = true;
		}
	}

	/**
	 * @return void
	 */
	public function setAllBlockSkyLight(int $level) : void{
		$char = chr(($level & self::COORD_MASK) | ($level << 4));
		$data = str_repeat($char, 2048);
		for($y = $this->getHighestSubChunkIndex(); $y >= 0; --$y){
			$this->getSubChunk($y, true)->setBlockSkyLightArray($data);
		}
	}

	/**
	 * Returns the block light level at the specified chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $y 0-255
	 * @param int $z 0-15
	 *
	 * @return int 0-15
	 */
	public function getBlockLight(int $x, int $y, int $z) : int{
		return $this->getSubChunk($y >> 4)->getBlockLight($x, $y & self::COORD_MASK, $z);
	}

	/**
	 * Sets the block light level at the specified chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $y 0-255
	 * @param int $z 0-15
	 * @param int $level 0-15
	 *
	 * @return void
	 */
	public function setBlockLight(int $x, int $y, int $z, int $level) : void{
		if($this->getSubChunk($y >> 4, true)->setBlockLight($x, $y & self::COORD_MASK, $z, $level)){
			$this->hasChanged = true;
		}
	}

	/**
	 * @return void
	 */
	public function setAllBlockLight(int $level){
		$char = chr(($level & self::COORD_MASK) | ($level << 4));
		$data = str_repeat($char, 2048);
		for($y = $this->getHighestSubChunkIndex(); $y >= 0; --$y){
			$this->getSubChunk($y, true)->setBlockLightArray($data);
		}
	}

	/**
	 * Returns the Y coordinate of the highest non-air block at the specified X/Z chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $z 0-15
	 *
	 * @return int
	 */
	public function getHighestBlockAt(int $x, int $z): ?int{
		$max = self::getMaxSubChunk($this->dimension);
		$min = self::getMinSubChunk($this->dimension);
		for($y = $max; $y >= $min; --$y){
			$height = $this->getSubChunk($y)->getHighestBlockAt($x, $z);
			if($height !== null){
				return $height | ($y << 4);
			}
		}

		return null;
	}

	public function getMaxY() : int{
		return ($this->getHighestSubChunkIndex() << 4) | self::COORD_MASK;
	}

	/**
	 * Returns the heightmap value at the specified X/Z chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $z 0-15
	 */
	public function getHeightMap(int $x, int $z) : int{
		return $this->heightMap[($z << 4) | $x];
	}

	/**
	 * Returns the heightmap value at the specified X/Z chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $z 0-15
	 *
	 * @return void
	 */
	public function setHeightMap(int $x, int $z, int $value){
		$this->heightMap[($z << 4) | $x] = $value;
	}

	/**
	 * Recalculates the heightmap for the whole chunk.
	 *
	 * @return void
	 */
	public function recalculateHeightMap(){
		for($z = 0; $z < 16; ++$z){
			for($x = 0; $x < 16; ++$x){
				$this->recalculateHeightMapColumn($x, $z);
			}
		}
	}

	/**
	 * Recalculates the heightmap for the block column at the specified X/Z chunk coordinates
	 *
	 * @param int $x 0-15
	 * @param int $z 0-15
	 *
	 * @return int New calculated heightmap value (0-256 inclusive)
	 */
	public function recalculateHeightMapColumn(int $x, int $z) : int{
		$y = $this->getHighestBlockAt($x, $z);
		if ($y === null) {
			$this->setHeightMap($x, $z, Level::Y_MIN);
			return Level::Y_MIN;
		}
		for(; $y >= 0; --$y){
			[$id, ] = RuntimeBlockMapping::fromStaticRuntimeId($id = $this->getBlockId($x, $y, $z, 0));
			if(BlockFactory::$lightFilter[$id] ?? 15 > 1 or BlockFactory::$diffusesSkyLight[$id] ?? false){
				break;
			}
		}

		$this->setHeightMap($x, $z, $y + 1);
		return $y + 1;
	}

	/**
	 * Performs basic sky light population on the chunk.
	 * This does not cater for adjacent sky light, this performs direct sky light population only. This may cause some strange visual artifacts
	 * if the chunk is light-populated after being terrain-populated.
	 *
	 * TODO: fast adjacent light spread
	 *
	 * @return void
	 */
	public function populateSkyLight(){
		$maxY = $this->getMaxY();

		$this->setAllBlockSkyLight(0);

		for($x = 0; $x < 16; ++$x){
			for($z = 0; $z < 16; ++$z){
				$y = $maxY;
				$heightMap = $this->getHeightMap($x, $z);
				for(; $y >= $heightMap; --$y){
					$this->setBlockSkyLight($x, $y, $z, 15);
				}

				$light = 15;
				for(; $y >= 0; --$y){
					[$id, ] = RuntimeBlockMapping::fromStaticRuntimeId($this->getBlockId($x, $y, $z, 0));
					$light -= BlockFactory::$lightFilter[$id] ?? 15;
					if($light <= 0){
						break;
					}
					$this->setBlockSkyLight($x, $y, $z, $light);
				}
			}
		}
	}

	/**
	 * Returns the biome ID at the specified X/Z chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $z 0-15
	 *
	 * @return int 0-255
	 */
	public function getBiomeId(int $x, int $y, int $z) : int{
		return $this->getBiomePalette($y >> 4)->get($x & self::COORD_MASK, $y & self::COORD_MASK, $z & self::COORD_MASK);
	}

	/**
	 * Sets the biome ID at the specified X/Y/Z chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $z 0-15
	 * @param int $biomeId 0-255
	 *
	 * @return void
	 */
	public function setBiomeId(int $x, int $y, int $z, int $biomeId) : void{
		$this->hasChanged = true;
		$this->getBiomePalette($y >> 4)->set($x & self::COORD_MASK, $y & self::COORD_MASK, $z & self::COORD_MASK, $biomeId);
	}

	/**
	 * Returns a column of sky light values from bottom to top at the specified X/Z chunk block coordinates.
	 *
	 * @param int $x 0-15
	 * @param int $z 0-15
	 */
	public function getBlockSkyLightColumn(int $x, int $z) : string{
		$result = "";
		foreach($this->subChunks as $subChunk){
			$result .= $subChunk->getBlockSkyLightColumn($x, $z);
		}
		return $result;
	}

	/**
	 * Returns a column of block light values from bottom to top at the specified X/Z chunk block coordinates.
	 *
	 * @param int $x 0-15
	 * @param int $z 0-15
	 */
	public function getBlockLightColumn(int $x, int $z) : string{
		$result = "";
		foreach($this->subChunks as $subChunk){
			$result .= $subChunk->getBlockLightColumn($x, $z);
		}
		return $result;
	}

	public function isLightPopulated() : bool{
		return $this->lightPopulated;
	}

	/**
	 * @return void
	 */
	public function setLightPopulated(bool $value = true){
		$this->lightPopulated = $value;
		$this->hasChanged = true;
	}

	public function isPopulated() : bool{
		return $this->terrainPopulated;
	}

	/**
	 * @return void
	 */
	public function setPopulated(bool $value = true){
		$this->terrainPopulated = $value;
		$this->hasChanged = true;
	}

	public function isGenerated() : bool{
		return $this->terrainGenerated;
	}

	/**
	 * @return void
	 */
	public function setGenerated(bool $value = true){
		$this->terrainGenerated = $value;
		$this->hasChanged = true;
	}

	/**
	 * @param int $value
	 */
	public function setInhabitedTime(int $value) : void{
		$this->inhabitedTime = $value;
	}

	/**
	 * @return int
	 */
	public function getInhabitedTime() : int{
		return $this->inhabitedTime;
	}

	/**
	 * @return void
	 */
	public function addEntity(Entity $entity){
		if($entity->isClosed()){
			throw new InvalidArgumentException("Attempted to add a garbage closed Entity to a chunk");
		}
		$this->entities[$entity->getId()] = $entity;
		if(!($entity instanceof Player) and $this->isInit){
			$this->hasChanged = true;
		}
	}

	/**
	 * @return void
	 */
	public function removeEntity(Entity $entity){
		unset($this->entities[$entity->getId()]);
		if(!($entity instanceof Player) and $this->isInit){
			$this->hasChanged = true;
		}
	}

	/**
	 * @return void
	 */
	public function addTile(Tile $tile){
		if($tile->isClosed()){
			throw new InvalidArgumentException("Attempted to add a garbage closed Tile to a chunk");
		}
		$this->tiles[$tile->getId()] = $tile;
		if(isset($this->tileList[$index = (($tile->x & self::COORD_MASK) << 12) | (($tile->z & self::COORD_MASK) << 8) | ($tile->y & 0xff)]) and $this->tileList[$index] !== $tile){
			$this->tileList[$index]->close();
		}
		$this->tileList[$index] = $tile;
		if($this->isInit){
			$this->hasChanged = true;
		}
	}

	/**
	 * @return void
	 */
	public function removeTile(Tile $tile){
		unset($this->tiles[$tile->getId()]);
		unset($this->tileList[(($tile->x & self::COORD_MASK) << 12) | (($tile->z & self::COORD_MASK) << 8) | ($tile->y & 0xff)]);
		if($this->isInit){
			$this->hasChanged = true;
		}
	}

	/**
	 * Returns an array of entities currently using this chunk.
	 *
	 * @return Entity[]
	 */
	public function getEntities() : array{
		return $this->entities;
	}

	/**
	 * @return Entity[]
	 */
	public function getSavableEntities() : array{
		return array_filter($this->entities, function(Entity $entity) : bool{ return $entity->canSaveWithChunk() and !$entity->isClosed(); });
	}

	/**
	 * @return Tile[]
	 */
	public function getTiles() : array{
		return $this->tiles;
	}

	/**
	 * Returns the tile at the specified chunk block coordinates, or null if no tile exists.
	 *
	 * @param int $x 0-15
	 * @param int $y 0-255
	 * @param int $z 0-15
	 *
	 * @return Tile|null
	 */
	public function getTile(int $x, int $y, int $z){
		$index = ($x << 12) | ($z << 8) | $y;
		return $this->tileList[$index] ?? null;
	}

	/**
	 * Called when the chunk is unloaded, closing entities and tiles.
	 */
	public function onUnload() : void{
		foreach($this->getEntities() as $entity){
			if($entity instanceof Player){
				continue;
			}
			$entity->close();
		}

		foreach($this->getTiles() as $tile){
			$tile->close();
		}
	}

	/**
	 * Deserializes tiles and entities from NBT
	 *
	 * @return void
	 */
	public function initChunk(Level $level){
		if(!$this->isInit){
			$changed = false;

			$level->timings->syncChunkLoadEntitiesTimer->startTiming();
			foreach($this->NBTentities as $nbt){
				$idTag = $nbt->getTag("identifier");
				if(!($idTag instanceof IntTag) && !($idTag instanceof StringTag)){ //allow mixed types (because of leveldb)
					$changed = true;
					continue;
				}

				try{
					$entity = Entity::createEntity($idTag->getValue(), $level, $nbt);
					if(!($entity instanceof Entity)){
						$changed = true;
						continue;
					}
				}catch(Throwable $t){
					$level->getServer()->getLogger()->logException($t);
					$changed = true;
					continue;
				}
			}
			$this->NBTentities = [];
			$level->timings->syncChunkLoadEntitiesTimer->stopTiming();

			$level->timings->syncChunkLoadTileEntitiesTimer->startTiming();
			foreach($this->NBTtiles as $nbt){
				if(!$nbt->hasTag(Tile::TAG_ID, StringTag::class)){
					$changed = true;
					continue;
				}

				if(Tile::createTile($nbt->getString(Tile::TAG_ID), $level, $nbt) === null){
					$changed = true;
					continue;
				}
			}

			$this->NBTtiles = [];
			$level->timings->syncChunkLoadTileEntitiesTimer->stopTiming();

			$this->hasChanged = $changed;

			$this->isInit = true;
		}
	}

	/**
	 * @return int[]
	 */
	public function getHeightMapArray() : array{
		return $this->heightMap->toArray();
	}

	public function hasChanged() : bool{
		return $this->hasChanged;
	}

	/**
	 * @return void
	 */
	public function setChanged(bool $value = true){
		$this->hasChanged = $value;
	}

	/**
	 * Returns the subchunk at the specified subchunk Y coordinate, or an empty, unmodifiable stub if it does not exist or the coordinate is out of range.
	 *
	 * @param bool $generateNew Whether to create a new, modifiable subchunk if there is not one in place
	 */
	public function getSubChunk(int $y, bool $generateNew = false) : SubChunkInterface{
		if($y < self::getMinSubChunk($this->dimension) or $y > self::getMaxSubChunk($this->dimension)){
			return $this->emptySubChunk;
		}elseif($generateNew && $this->subChunks[$y] instanceof EmptySubChunk){
			$this->subChunks[$y] = new SubChunk(RuntimeBlockMapping::AIR());
		}

		return $this->subChunks[$y];
	}

	public function getBiomePalette(int $y, bool $generateNew = false) : PalettedBlockArray{
		if($y < self::getMinSubChunk($this->dimension) or $y > self::getMaxSubChunk($this->dimension)){
			return new PalettedBlockArray(Biome::OCEAN);
		}elseif($generateNew && !isset($this->biomes[$y])){
			$this->biomes[$y] = new PalettedBlockArray(Biome::OCEAN);
		}

		return $this->biomes[$y];
	}

	/**
	 * Sets a subchunk in the chunk index
	 *
	 * @param bool $allowEmpty Whether to check if the chunk is empty, and if so replace it with an empty stub
	 */
	public function setSubChunk(int $y, SubChunkInterface $subChunk = null, bool $allowEmpty = false) : bool{
		if($y < self::getMinSubChunk($this->dimension) or $y >= self::getMaxSubChunk($this->dimension)){
			return false;
		}
		if($subChunk === null or ($subChunk->isEmpty() and !$allowEmpty)){
			$this->subChunks[$y] = $this->emptySubChunk;
		}else{
			$this->subChunks[$y] = $subChunk;
		}
		$this->hasChanged = true;
		return true;
	}

	/**
	 * @return SubChunkInterface[]
	 *
	 */
	public function getSubChunks() : array{
		return $this->subChunks;
	}

	/**
	 * Returns the Y coordinate of the highest non-empty subchunk in this chunk.
	 */
	public function getHighestSubChunkIndex() : int{
		$max = self::getMaxSubChunk($this->dimension);
		$min = self::getMinSubChunk($this->dimension);

		for($y = $max; $y >= $min; --$y){
			if (!isset($this->subChunks[$y])) {
			}
			if($this->subChunks[$y] instanceof EmptySubChunk || $this->subChunks[$y]->isEmpty()){
				//No need to thoroughly prune empties at runtime, this will just reduce performance.
				continue;
			}
			return $y;
		}

		return $min - 1;
	}

	/**
	 * Returns the count of subchunks that need sending to players
	 */
	public function getSubChunkSendCount() : int{
		return $this->getHighestSubChunkIndex() - self::getMinSubChunk($this->dimension) + 1;
	}

	/**
	 * Disposes of empty subchunks and frees data where possible
	 */
	public function collectGarbage() : void{
		foreach($this->subChunks as $y => $subChunk){
			if($subChunk instanceof SubChunk){
				if(!$subChunk->isEmpty()){
					$subChunk->collectGarbage();
				}
			}
		}

		foreach($this->biomes as $biome){
			$biome->collectGarbage();
		}
	}

	/**
	 * Serializes the chunk for sending to players
	 */
	public function networkSerialize(?string $networkSerializedTiles, int $dimensionId) : string{
		$stream = new NetworkBinaryStream();
		$subChunkCount = $this->getSubChunkSendCount();
		$writtenCount = 0;

		for($y = self::getMinSubChunk($this->dimension); $writtenCount < $subChunkCount; ++$y, ++$writtenCount){
			$this->subChunks[$y]->networkSerialize($stream);
		}

		//TODO: right now we don't support 3D natively, so we just 3Dify our 2D biomes so they fill the column
		$this->networkSerializeBiomes($stream);

		$stream->put(chr(0)); //border block array count
		//Border block entry format: 1 byte (4 bits X, 4 bits Z). These are however useless since they crash the regular client.

		$stream->put($networkSerializedTiles ?? $this->networkSerializeTiles());

		return $stream->getBuffer();
	}

	/**
	 * Serializes all tiles in network format for chunk sending. This is necessary because fastSerialize() doesn't
	 * include tiles; they have to be encoded on the main thread.
	 */
	public function networkSerializeTiles() : string{
		$result = "";
		foreach($this->tiles as $tile){
			if($tile instanceof Spawnable){
				$result .= $tile->getSerializedSpawnCompound();
			}
		}

		return $result;
	}

	private function networkSerializeBiomes(NetworkBinaryStream $stream) : void{
		/** @var string[]|null $biomeIdMap */
		static $biomeIdMap = null;
		if($biomeIdMap === null){
			$biomeIdMapRaw = file_get_contents(RESOURCE_PATH . '/vanilla/biomes.json');
			if($biomeIdMapRaw === false) throw new AssumptionFailedError();
			$biomeIdMapDecoded = json_decode($biomeIdMapRaw, true);
			if(!is_array($biomeIdMapDecoded)) throw new AssumptionFailedError();
			$biomeIdMap = array_flip($biomeIdMapDecoded);
		}

		foreach($this->biomes as $biomePalette) {
			$biomePaletteBitsPerBlock = $biomePalette->getBitsPerBlock();
			$stream->putByte(($biomePaletteBitsPerBlock << 1) | 1);
			$stream->put($biomePalette->getWordArray());

			$biomePaletteArray = $biomePalette->getPalette();
			if($biomePaletteBitsPerBlock !== 0){
				$stream->putUnsignedVarInt(count($biomePaletteArray) << 1);
			}
			foreach($biomePaletteArray as $p){
				if($biomeIdMap[$p] === null){
					$p = BiomeIds::OCEAN;
				}
				$stream->put(Binary::writeUnsignedVarInt($p << 1));
			}
		}
	}

	public function diskSerializeBiomes(BinaryStream $stream) : void{
		foreach($this->biomes as $biome) {
			$stream->putByte($biome->getBitsPerBlock() << 1);
			$stream->put($biome->getWordArray());

			$palette = $biome->getPalette();
			if($biome->getBitsPerBlock() !== 0){
				$stream->putLInt(count($palette));
			}
			foreach($palette as $p){
				$stream->putLInt($p);
			}
		}
	}

	/**
	 * Fast-serializes the chunk for passing between threads
	 * TODO: tiles and entities
	 */
	public function fastSerialize() : string{
		$stream = new BinaryStream();
		$stream->putInt($this->x);
		$stream->putInt($this->z);
		$stream->putByte($this->dimension);
		$stream->putByte(($this->lightPopulated ? 4 : 0) | ($this->terrainPopulated ? 2 : 0) | ($this->terrainGenerated ? 1 : 0));
		if($this->terrainGenerated){
			$count = 0;
			$subChunkStream = new BinaryStream();
			foreach($this->subChunks as $y => $subChunk){
				if($subChunk instanceof EmptySubChunk || $subChunk->isEmpty()){
					continue;
				}
				++$count;
				$subChunkStream->putByte($y);
				$subChunk->fastSerialize($subChunkStream, $this->lightPopulated);
				$this->fastSerializeBiome($subChunkStream, $y);
			}

			$stream->putByte($count);
			$stream->put($subChunkStream->getBuffer());

			if($this->lightPopulated){
				$stream->put(pack("v*", ...$this->heightMap));
			}
		}

		return $stream->getBuffer();
	}

	private function fastSerializeBiome(BinaryStream $stream, int $y) : void{
		$biome = $this->getBiomePalette($y);
		$wordArray = $biome->getWordArray();
		$palette = $biome->getPalette();

		$stream->putByte($biome->getBitsPerBlock());
		$stream->put($wordArray);
		$serialPalette = pack("L*", ...$palette);
		$stream->putInt(strlen($serialPalette));
		$stream->put($serialPalette);
	}

	/**
	 * Deserializes a fast-serialized chunk
	 */
	public static function fastDeserialize(string $data) : Chunk{
		$stream = new BinaryStream($data);

		$x = $stream->getInt();
		$z = $stream->getInt();
		$dimension = $stream->getByte();
		$flags = $stream->getByte();
		$lightPopulated = (bool) ($flags & 4);
		$terrainPopulated = (bool) ($flags & 2);
		$terrainGenerated = (bool) ($flags & 1);

		$subChunks = [];
		$biomes = [];
		$biomeIds = "";
		$heightMap = [];
		if($terrainGenerated){
			$count = $stream->getByte();
			for($y = 0; $y < $count; ++$y){
				$index = Binary::signByte($stream->getByte());
				$subChunks[$index] = SubChunk::fastDeserialize($stream, $lightPopulated);
				$biomes[$index] = self::fastDeserializeBiome($stream);
			}

			if($lightPopulated){
				/** @var int[] $unpackedHeightMap */
				$unpackedHeightMap = unpack("v*", $stream->get(512)); //unpack() will never fail here
				$heightMap = array_values($unpackedHeightMap);
			}
		}

		$chunk = new Chunk($x, $z, $dimension, $subChunks, [], [], $biomes, $heightMap);
		$chunk->setGenerated($terrainGenerated);
		$chunk->setPopulated($terrainPopulated);
		$chunk->setLightPopulated($lightPopulated);
		$chunk->setChanged(false);

		return $chunk;
	}

	private static function fastDeserializeBiome(BinaryStream $stream) : PalettedBlockArray{
		$bitsPerBlock = $stream->getByte();
		$words = $stream->get(PalettedBlockArray::getExpectedWordArraySize($bitsPerBlock));
		/** @var int[] $unpackedPalette */
		$unpackedPalette = unpack("L*", $stream->get($stream->getInt()));
		$palette = array_values($unpackedPalette);

		return PalettedBlockArray::fromData($bitsPerBlock, $words, $palette);
	}

	/**
	 * @param int $dimension 0 = Overworld, 1 = Nether, 2 = End
	 *
	 * @return int
	 */
	public static function getMinSubChunk(int $dimension): int {
		return match ($dimension) {
			DimensionIds::NETHER, DimensionIds::THE_END => 0,
			default => -4
		};
	}

	/**
	 * @param int $dimension 0 = Overworld, 1 = Nether, 2 = End
	 *
	 * @return int
	 */
	public static function getMaxSubChunk(int $dimension) : int{
		return match ($dimension) {
			DimensionIds::NETHER => 7,
			DimensionIds::THE_END => 15,
			default => 19
		};
	}
}
