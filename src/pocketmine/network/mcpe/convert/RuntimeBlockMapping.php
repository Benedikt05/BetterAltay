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

namespace pocketmine\network\mcpe\convert;

use exussum12\xxhash\V32;
use pocketmine\block\BlockIds;
use pocketmine\nbt\LittleEndianNBTStream;
use pocketmine\nbt\NBTStream;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\BinaryStream;
use RuntimeException;
use function file_get_contents;
use function json_decode;
use const pocketmine\RESOURCE_PATH;

/**
 * @internal
 */
final class RuntimeBlockMapping{

	/** @var int[] */
	private static $legacyToRuntimeMap = [];
	/** @var int[] */
	private static $runtimeToLegacyMap = [];
	/** @var CompoundTag[]|null */
	private static $bedrockKnownStates = null;
	/** @var CompoundTag[]|null */
	private static $stateToRuntimeMap = [];

	private static $hasher = null;

	/** @var int */
	private static $airRid = -1;
	/** @var int */
	private static $unknownRid = -1;

	private function __construct(){
		//NOOP
	}

	public static function init() : void{
		$canonicalBlockStatesFile = file_get_contents(RESOURCE_PATH . "vanilla/canonical_block_states.nbt");
		if($canonicalBlockStatesFile === false){
			throw new AssumptionFailedError("Missing required resource file");
		}

		$stream = new NetworkBinaryStream($canonicalBlockStatesFile);
		self::$bedrockKnownStates = [];
		/**
		 * @var int[][] $idToStatesMap string id -> int[] list of candidate state indices
		 */
		$idToStatesMap = [];

		$rid = 0;
		while(!$stream->feof()){
			$state = $stream->getNbtCompoundRoot();
			self::$bedrockKnownStates[] = $state;
			$idToStatesMap[$state->getString("name")][] = $rid;

			self::$stateToRuntimeMap[self::hashBlockStateNBT($state)] = $rid;
			if (self::$airRid === -1 && $state->getString("name") === "minecraft:air") {
				self::$airRid = $rid;
			} elseif (self::$unknownRid === -1 && $state->getString("name") === "minecraft:unknown") {
				self::$unknownRid = $rid;
			}
			$rid++;
		}

		/** @var R12ToCurrentBlockMapEntry[] $legacyStateMap */
		$legacyStateMap = [];
		$legacyStateMapReader = new NetworkBinaryStream(file_get_contents(RESOURCE_PATH . "vanilla/r12_to_current_block_map.bin"));
		$nbtReader = new NetworkLittleEndianNBTStream();
		while(!$legacyStateMapReader->feof()){
			$id = $legacyStateMapReader->getString();
			$meta = $legacyStateMapReader->getLShort();

			$offset = $legacyStateMapReader->getOffset();
			$state = $nbtReader->read($legacyStateMapReader->getBuffer(), false, $offset);
			$legacyStateMapReader->setOffset($offset);
			if(!($state instanceof CompoundTag)){
				throw new RuntimeException("Blockstate should be a TAG_Compound");
			}
			$legacyStateMap[] = new R12ToCurrentBlockMapEntry($id, $meta, $state);
		}

		self::setupLegacyMappings($idToStatesMap, $legacyStateMap);
	}

	private static function setupLegacyMappings(array $idToStatesMap, array $legacyStateMap) : void{
		$legacyIdMap = json_decode(file_get_contents(RESOURCE_PATH . "vanilla/block_id_map.json"), true);
		foreach($legacyStateMap as $pair){
			$id = $legacyIdMap[$pair->getId()] ?? null;
			if($id === null){
				throw new RuntimeException("No legacy ID matches " . $pair->getId());
			}
			$data = $pair->getMeta();
			if($data > 15){
				//we can't handle metadata with more than 4 bits
				continue;
			}
			$mappedState = $pair->getBlockState();

			//TODO HACK: idiotic NBT compare behaviour on 3.x compares keys which are stored by values
			$mappedState->setName("");
			$mappedName = $mappedState->getString("name");
			if(!isset($idToStatesMap[$mappedName])){
				throw new RuntimeException("Mapped new state does not appear in network table");
			}
			foreach($idToStatesMap[$mappedName] as $k){
				$networkState = self::$bedrockKnownStates[$k];
				if($mappedState->equals($networkState)){
					self::registerMapping($k, $id, $data);
					continue 2;
				}
			}
			throw new RuntimeException("Mapped new state does not appear in network table");
		}
		self::registerMapping(self::UNKNOWN(), -1, 0);
	}

	private static function lazyInit() : void{
		if(self::$bedrockKnownStates === null){
			self::init();
		}
	}

	public static function toStaticRuntimeId(int $id, int $meta = 0) : int{
		self::lazyInit();
		/*
		 * try id+meta first
		 * if not found, try id+0 (strip meta)
		 * if still not found, return update! block
		 */
		return self::$legacyToRuntimeMap[($id << 4) | $meta] ?? self::$legacyToRuntimeMap[$id << 4] ?? self::$legacyToRuntimeMap[BlockIds::INFO_UPDATE << 4];
	}

	/**
	 * @return int[] [id, meta]
	 */
	public static function fromStaticRuntimeId(int $runtimeId) : array{
		self::lazyInit();
		$v = self::$runtimeToLegacyMap[$runtimeId] ?? RuntimeBlockMapping::$runtimeToLegacyMap[self::$unknownRid];
		return [$v >> 4, $v & 0xf];
	}

	private static function registerMapping(int $staticRuntimeId, int $legacyId, int $legacyMeta) : void{
		self::$legacyToRuntimeMap[($legacyId << 4) | $legacyMeta] = $staticRuntimeId;
		self::$runtimeToLegacyMap[$staticRuntimeId] = ($legacyId << 4) | $legacyMeta;
	}

	/**
	 * @return CompoundTag[]
	 */
	public static function getBedrockKnownStates() : array{
		self::lazyInit();
		return self::$bedrockKnownStates;
	}

	public static function fromBlockStateNBT(CompoundTag $nbt) {
		$hash = self::hashBlockStateNBT($nbt);
		return self::$stateToRuntimeMap[$hash] ?? throw new RuntimeException("Unknown block state NBT");
	}

	public static function AIR() : int{
		self::lazyInit();
		return self::$airRid;
	}

	public static function UNKNOWN() : int{
		self::lazyInit();
		return self::$unknownRid;
	}

	private static function getHasher() : V32{
		if (self::$hasher === null){
			self::$hasher = new V32();
		}

		return self::$hasher;
	}

	/**
	 * @param CompoundTag $blockState
	 *
	 * @return string
	 */
	private static function hashBlockStateNBT(CompoundTag $blockState) : string {
		$name = $blockState->getTag("name");
		if (!$name instanceof StringTag) {
			throw new \InvalidArgumentException("Missing or invalid 'name'");
		}

		$states = $blockState->getTag("states");
		if (!$states instanceof CompoundTag) {
			throw new \InvalidArgumentException("Missing or invalid 'states'");
		}

		$version = $blockState->getTag("version");
		if (!$version instanceof IntTag) {
			throw new \InvalidArgumentException("Missing or invalid 'version'");
		}

		$stream = new BinaryStream();
		$nbtStream = new LittleEndianNBTStream();

		$name->write($nbtStream);
		self::serializeNBT($nbtStream, $states);
		$version->write($nbtStream);

		$stream->put($nbtStream->getString());

		return self::getHasher()->hash($stream->getBuffer());
	}

	/**
	 * @param NBTStream   $writer
	 * @param CompoundTag $states
	 *
	 * @return void
	 */
	private static function serializeNBT(NBTStream $writer, CompoundTag $states) : void {
		$tags = $states->getValue();
		ksort($tags);

		foreach ($tags as $tag) {
			if ($tag instanceof CompoundTag) {
				self::serializeNBT($writer, $tag);
			} elseif ($tag instanceof ListTag) {
				foreach ($tag->getValue() as $childTag) {
					if ($childTag instanceof CompoundTag) {
						self::serializeNBT($writer, $childTag);
					} else {
						$childTag->write($writer);
					}
				}
			} else {
				$tag->write($writer);
			}
		}
	}
}