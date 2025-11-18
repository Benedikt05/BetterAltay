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

namespace pocketmine\level\format\io\leveldb;

use pocketmine\level\format\Chunk;
use pocketmine\level\format\io\BaseLevelProvider;
use pocketmine\level\format\io\exception\CorruptedChunkException;
use pocketmine\level\format\io\exception\UnsupportedChunkFormatException;
use pocketmine\level\format\SubChunk;
use pocketmine\level\generator\Flat;
use pocketmine\level\generator\GeneratorManager;
use pocketmine\level\Level;
use pocketmine\level\LevelException;
use pocketmine\nbt\LittleEndianNBTStream;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\utils\Binary;
use pocketmine\utils\BinaryStream;
use pocketmine\world\format\PalettedBlockArray;
use UnexpectedValueException;
use function array_values;
use function chr;
use function count;
use function defined;
use function explode;
use function extension_loaded;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function is_dir;
use function mkdir;
use function ord;
use function pack;
use function rtrim;
use function strlen;
use function substr;
use function time;
use function trim;
use function unpack;
use const INT32_MAX;
use const LEVELDB_ZLIB_RAW_COMPRESSION;

class LevelDB extends BaseLevelProvider{

	//According to Tomasso, these aren't supposed to be readable anymore. Thankfully he didn't change the readable ones...
	public const FINALISATION_DONE = 2;

	public const ENTRY_FLAT_WORLD_LAYERS = "game_flatworldlayers";

	public const GENERATOR_LIMITED = 0;
	public const GENERATOR_INFINITE = 1;
	public const GENERATOR_FLAT = 2;

	public const CURRENT_STORAGE_VERSION = 10;
	public const CURRENT_CHUNK_VERSION = 41;
	public const CURRENT_SUBCHUNK_VERSION = 8;

	/** @var \LevelDB */
	protected $db;

	private static function checkForLevelDBExtension() : void{
		if(!extension_loaded('leveldb')){
			throw new LevelException("The leveldb PHP extension is required to use this world format");
		}

		if(!defined('LEVELDB_ZLIB_RAW_COMPRESSION')){
			throw new LevelException("Given version of php-leveldb doesn't support zlib raw compression");
		}
	}

	private static function createDB(string $path) : \LevelDB{
		return new \LevelDB($path . "/db", [
			"compression" => LEVELDB_ZLIB_RAW_COMPRESSION
		]);
	}

	public function __construct(string $path){
		self::checkForLevelDBExtension();
		parent::__construct($path);

		$this->db = self::createDB($path);
	}

	protected function loadLevelData() : void{
		$rawLevelData = file_get_contents($this->getPath() . "level.dat");
		if($rawLevelData === false or strlen($rawLevelData) <= 8){
			throw new LevelException("Truncated level.dat");
		}
		$nbt = new LittleEndianNBTStream();
		try{
			$levelData = $nbt->read(substr($rawLevelData, 8));
		}catch(UnexpectedValueException $e){
			throw new LevelException("Invalid level.dat (" . $e->getMessage() . ")", 0, $e);
		}
		if($levelData instanceof CompoundTag){
			$this->levelData = $levelData;
		}else{
			throw new LevelException("Invalid level.dat");
		}

		$version = $this->levelData->getInt("StorageVersion", INT32_MAX, true);
		if($version > self::CURRENT_STORAGE_VERSION){
			throw new LevelException("Specified LevelDB world format version ($version) is not supported");
		}
	}

	protected function fixLevelData() : void{
		$db = self::createDB($this->path);

		if(!$this->levelData->hasTag("generatorName", StringTag::class)){
			if($this->levelData->hasTag("Generator", IntTag::class)){
				switch($this->levelData->getInt("Generator")){ //Detect correct generator from MCPE data
					case self::GENERATOR_FLAT:
						$this->levelData->setString("generatorName", "flat");
						if(($layers = $db->get(self::ENTRY_FLAT_WORLD_LAYERS)) !== false){ //Detect existing custom flat layers
							$layers = trim($layers, "[]");
						}else{
							$layers = "7,3,3,2";
						}
						$this->levelData->setString("generatorOptions", "2;" . $layers . ";1");
						break;
					case self::GENERATOR_INFINITE:
						//TODO: add a null generator which does not generate missing chunks (to allow importing back to MCPE and generating more normal terrain without PocketMine messing things up)
						$this->levelData->setString("generatorName", "default");
						$this->levelData->setString("generatorOptions", "");
						break;
					case self::GENERATOR_LIMITED:
						throw new LevelException("Limited worlds are not currently supported");
					default:
						throw new LevelException("Unknown LevelDB world format type, this level cannot be loaded");
				}
			}else{
				$this->levelData->setString("generatorName", "default");
			}
		}elseif(($generatorName = self::hackyFixForGeneratorClasspathInLevelDat($this->levelData->getString("generatorName"))) !== null){
			$this->levelData->setString("generatorName", $generatorName);
		}

		if(!$this->levelData->hasTag("generatorOptions", StringTag::class)){
			$this->levelData->setString("generatorOptions", "");
		}
	}

	public static function getProviderName() : string{
		return "leveldb";
	}

	public function getWorldMaxHeight() : int{
		return 320;
	}

	public function getWorldMinHeight() : int{
		return -64;
	}

	public static function isValid(string $path) : bool{
		return file_exists($path . "/level.dat") and is_dir($path . "/db/");
	}

	public static function generate(string $path, string $name, int $seed, string $generator, array $options = []){
		self::checkForLevelDBExtension();

		if(!file_exists($path . "/db")){
			mkdir($path . "/db", 0777, true);
		}

		switch($generator){
			case Flat::class:
				$generatorType = self::GENERATOR_FLAT;
				break;
			default:
				$generatorType = self::GENERATOR_INFINITE;
			//TODO: add support for limited worlds
		}

		$spawn = (new $generator)->getSpawn()->floor();
		$levelData = new CompoundTag("", [
			//Vanilla fields
			new IntTag("DayCycleStopTime", -1),
			new IntTag("Difficulty", Level::getDifficultyFromString((string) ($options["difficulty"] ?? "normal"))),
			new ByteTag("ForceGameType", 0),
			new IntTag("GameType", 0),
			new IntTag("Generator", $generatorType),
			new LongTag("LastPlayed", time()),
			new StringTag("LevelName", $name),
			new IntTag("NetworkVersion", ProtocolInfo::CURRENT_PROTOCOL),
			//new IntTag("Platform", 2), //TODO: find out what the possible values are for
			new LongTag("RandomSeed", $seed),
			new IntTag("SpawnX", $spawn->x),
			new IntTag("SpawnY", $spawn->y),
			new IntTag("SpawnZ", $spawn->z),
			new IntTag("StorageVersion", self::CURRENT_STORAGE_VERSION),
			new LongTag("Time", 0),
			new ByteTag("eduLevel", 0),
			new ByteTag("falldamage", 1),
			new ByteTag("firedamage", 1),
			new ByteTag("hasBeenLoadedInCreative", 1), //badly named, this actually determines whether achievements can be earned in this world...
			new ByteTag("immutableWorld", 0),
			new FloatTag("lightningLevel", 0.0),
			new IntTag("lightningTime", 0),
			new ByteTag("pvp", 1),
			new FloatTag("rainLevel", 0.0),
			new IntTag("rainTime", 0),
			new ByteTag("spawnMobs", 1),
			new ByteTag("texturePacksRequired", 0), //TODO

			//Additional PocketMine-MP fields
			new CompoundTag("GameRules", []),
			new ByteTag("hardcore", ($options["hardcore"] ?? false) === true ? 1 : 0),
			new StringTag("generatorName", GeneratorManager::getGeneratorName($generator)),
			new StringTag("generatorOptions", $options["preset"] ?? "")
		]);

		$nbt = new LittleEndianNBTStream();
		$buffer = $nbt->write($levelData);
		file_put_contents($path . "level.dat", Binary::writeLInt(self::CURRENT_STORAGE_VERSION) . Binary::writeLInt(strlen($buffer)) . $buffer);

		$db = self::createDB($path);

		if($generatorType === self::GENERATOR_FLAT and isset($options["preset"])){
			$layers = explode(";", $options["preset"])[1] ?? "";
			if($layers !== ""){
				$out = "[";
				foreach(Flat::parseLayers($layers) as $result){
					$out .= $result[0] . ","; //only id, meta will unfortunately not survive :(
				}
				$out = rtrim($out, ",") . "]"; //remove trailing comma
				$db->put(self::ENTRY_FLAT_WORLD_LAYERS, $out); //Add vanilla flatworld layers to allow terrain generation by MCPE to continue seamlessly
			}
		}
	}

	public function saveLevelData(){
		$this->levelData->setInt("NetworkVersion", ProtocolInfo::CURRENT_PROTOCOL);
		$this->levelData->setInt("StorageVersion", self::CURRENT_STORAGE_VERSION);

		$nbt = new LittleEndianNBTStream();
		$buffer = $nbt->write($this->levelData);
		file_put_contents($this->getPath() . "level.dat", Binary::writeLInt(self::CURRENT_STORAGE_VERSION) . Binary::writeLInt(strlen($buffer)) . $buffer);
	}

	public function getGenerator() : string{
		return $this->levelData->getString("generatorName", "");
	}

	public function getGeneratorOptions() : array{
		return ["preset" => $this->levelData->getString("generatorOptions", "")];
	}

	public function getDifficulty() : int{
		return $this->levelData->getInt("Difficulty", Level::DIFFICULTY_NORMAL);
	}

	public function setDifficulty(int $difficulty){
		$this->levelData->setInt("Difficulty", $difficulty); //yes, this is intended! (in PE: int, PC: byte)
	}

	/**
	 * @throws UnsupportedChunkFormatException
	 * @throws \Exception
	 */
	protected function readChunk(int $chunkX, int $chunkZ, int $dimension) : ?Chunk{
		$index = Key::get($chunkX, $chunkZ, $dimension);
		$chunkVersionRaw = $this->db->get($index . Key::TAG_CHUNK_VERSION);
		if($chunkVersionRaw === false){
			return null;
		}

		/** @var SubChunk[] $subChunks */
		$subChunks = [];
		/** @var PalettedBlockArray[] $biomes */
		$biomes = [];

		$chunkVersion = ord($chunkVersionRaw);
		$hasBeenUpgraded = $chunkVersion < self::CURRENT_CHUNK_VERSION;

		switch($chunkVersion){
			case self::CURRENT_CHUNK_VERSION:
				$maxY = Chunk::getMaxSubChunk($dimension);
				$biomeStream = new BinaryStream();
				$lastBiome = null;

				if (($rawBiomes = $this->db->get($index . Key::TAG_BIOMES)) !== false){
					if(strlen($rawBiomes) <= 512){
						throw new CorruptedChunkException("Biomes must have at least 513 bytes.");
					}

					$biomeStream->setBuffer($rawBiomes);
					$biomeStream->get(512);
				}

				for ($y = Chunk::getMinSubChunk($dimension); $y <= $maxY; ++$y) {
					if(($rawSubChunk = $this->db->get($index . Key::TAG_SUBCHUNK . chr($y))) !== false){
						$subChunks[$y] = $this->decodeSubChunk(new BinaryStream($rawSubChunk));
					}

					if (!$biomeStream->feof()) {
						$biome = $this->decodeBiome($biomeStream);
						if ($biome === null && $lastBiome === null) {
							throw new CorruptedChunkException("Unable to decode biome, and the last biome was null");
						}

						if ($biome !== null) {
							$lastBiome = $biome;
						} else {
							$biome = $lastBiome;
						}
						$biomes[$y] = $biome;
					}
				}
				break;
			default:
				//TODO: set chunks read-only so the version on disk doesn't get overwritten
				throw new UnsupportedChunkFormatException("don't know how to decode chunk format version $chunkVersion");
		}

		$nbt = new LittleEndianNBTStream();

		/** @var CompoundTag[] $entities */
		$entities = [];
		if(($entityData = $this->db->get($index . Key::TAG_ENTITIES)) !== false and $entityData !== ""){
			$entityTags = $nbt->read($entityData, true);
			foreach((is_array($entityTags) ? $entityTags : [$entityTags]) as $entityTag){
				if(!($entityTag instanceof CompoundTag)){
					throw new CorruptedChunkException("Entity root tag should be TAG_Compound");
				}
				if($entityTag->hasTag("identifier", IntTag::class)){
					$entityTag->setInt("identifier", $entityTag->getInt("id") & 0xff); //remove type flags - TODO: use these instead of removing them)
				}
				$entities[] = $entityTag;
			}
		}

		/** @var CompoundTag[] $tiles */
		$tiles = [];
		if(($tileData = $this->db->get($index . Key::TAG_BLOCK_ENTITIES)) !== false and $tileData !== ""){
			$tileTags = $nbt->read($tileData, true);
			foreach((is_array($tileTags) ? $tileTags : [$tileTags]) as $tileTag){
				if(!($tileTag instanceof CompoundTag)){
					throw new CorruptedChunkException("Tile root tag should be TAG_Compound");
				}
				$tiles[] = $tileTag;
			}
		}

		$chunk = new Chunk(
			$chunkX,
			$chunkZ,
			$dimension,
			$subChunks,
			$entities,
			$tiles,
			$biomes,
			[]
		);

		//TODO: tile ticks, biome states (?)

		$chunk->setGenerated();
		$chunk->setPopulated();
		$chunk->setLightPopulated();
		$chunk->setChanged($hasBeenUpgraded); //trigger rewriting chunk to disk if it was converted from an older format

		return $chunk;
	}

	protected function writeChunk(Chunk $chunk) : void{
		$index = Key::get($chunk->getX(), $chunk->getZ(), $chunk->getDimension());
		$this->db->put($index . Key::TAG_CHUNK_VERSION, chr(self::CURRENT_CHUNK_VERSION));

		$chunk->collectGarbage();
		$subChunks = $chunk->getSubChunks();
		foreach($subChunks as $y => $subChunk){
			$key = $index . Key::TAG_SUBCHUNK . chr($y);
			if($subChunk->isEmpty()){
				$this->db->delete($key);
			}else{
				$stream = new BinaryStream();
				$subChunk->diskSerialize($stream);
				$this->db->put($key, $stream->getBuffer());
			}
		}

		$biomeStream = new BinaryStream();
		$biomeStream->put(str_repeat("\x00", 512));
		$chunk->diskSerializeBiomes($biomeStream);
		$this->db->put($index . Key::TAG_BIOMES, $biomeStream->getBuffer());

		$this->db->put($index . Key::TAG_FINALISATION, chr(self::FINALISATION_DONE));

		/** @var CompoundTag[] $tiles */
		$tiles = [];
		foreach($chunk->getTiles() as $tile){
			$tiles[] = $tile->saveNBT();
		}
		$this->writeTags($tiles, $index . Key::TAG_BLOCK_ENTITIES);

		/** @var CompoundTag[] $entities */
		$entities = [];
		foreach($chunk->getSavableEntities() as $entity){
			$entity->saveNBT();
			$entities[] = $entity->namedtag;
		}
		$this->writeTags($entities, $index . Key::TAG_ENTITIES);
	}

	public function decodeSubChunk(BinaryStream $stream) : SubChunk {
		if ($stream->feof()) {
			throw new CorruptedChunkException("Unexpected empty data");
		}

		$version = $stream->getByte();
		if ($version === 1) {
			return new SubChunk(RuntimeBlockMapping::AIR(), [$this->decodeBlockPalette($stream)]);
		}

		if ($version < 8) {
			throw new CorruptedChunkException("Unknown subchunk version " . $version);
		}

		$storageCount = $stream->getByte();
		if($version >= 9){
			$stream->getByte();
		}

		$storages = [];
		for ($i = 0; $i < $storageCount; ++$i) {
			$storages[$i] = $this->decodeBlockPalette($stream);
		}

		return new SubChunk(RuntimeBlockMapping::AIR(), $storages);
	}

	public function decodeBiome(BinaryStream $stream): ?PalettedBlockArray
	{
		$bitsPerBlock = $stream->getByte() >> 1;
		if ($bitsPerBlock === 127) {
			return null;
		}

		try{
			$words = $stream->get(PalettedBlockArray::getExpectedWordArraySize($bitsPerBlock));
		}catch(\InvalidArgumentException $e){
			throw new \Exception("Failed to deserialize paletted biomes: " . $e->getMessage(), 0, $e);
		}
		$palette = [];
		$paletteCount = $bitsPerBlock !== 0 ? $stream->getLInt() : 1;

		for($i = 0; $i < $paletteCount; ++$i){
			$palette[] = $stream->getLInt();
		}

		return PalettedBlockArray::fromData($bitsPerBlock, $words, $palette);
	}

	public function decodeBiomes() {

	}

	/**
	 * @param BinaryStream $stream
	 *
	 * @return PalettedBlockArray
	 */
	public function decodeBlockPalette(BinaryStream $stream): PalettedBlockArray {
		$bitsPerBlock = $stream->getByte() >> 1;
		try{
			$words = $stream->get(PalettedBlockArray::getExpectedWordArraySize($bitsPerBlock));
		}catch(\Exception $e){
			throw new CorruptedChunkException("Failed to deserialize paletted storage: " . $e->getMessage(), 0, $e);
		}

		$paletteCount = $bitsPerBlock !== 0 ? $stream->getLInt() : 1;

		$palette = [];
		$nbt = new LittleEndianNBTStream();

		for($i = 0; $i < $paletteCount; ++$i) {
			try {
				$offset = $stream->getOffset();
				$blockStateNbt = $nbt->read($stream->getBuffer(), false, $offset);
				if (!$blockStateNbt instanceof CompoundTag) {
					throw new CorruptedChunkException("Expected TAG_Compound, got " . get_class($blockStateNbt));
				}

				$stream->setOffset($offset);
				$runtimeId = RuntimeBlockMapping::fromBlockStateNBT($blockStateNbt);
			} catch (\Exception $e) {
				throw new CorruptedChunkException("Invalid blockstate NBT at offset $i in paletted storage: " . $e->getMessage(), 0, $e);
			}

			$palette[] = $runtimeId;
		}

		return PalettedBlockArray::fromData($bitsPerBlock, $words, $palette);
	}

	/**
	 * @param CompoundTag[] $targets
	 */
	private function writeTags(array $targets, string $index) : void{
		if(count($targets) > 0){
			$nbt = new LittleEndianNBTStream();
			$this->db->put($index, $nbt->write($targets));
		}else{
			$this->db->delete($index);
		}
	}

	public function getDatabase() : \LevelDB{
		return $this->db;
	}

	public function close() : void{
		unset($this->db);
	}
}
