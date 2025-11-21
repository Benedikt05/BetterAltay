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
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\utils\AssumptionFailedError;
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
	/** @var int[]|null */
	private static $stateToRuntimeMap = [];

	private static $runtimeToId = [];
	private static $idToRuntime = [];

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

		$metaMap = json_decode(file_get_contents(RESOURCE_PATH . "vanilla/block_state_meta_map.json"));
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
			self::registerMapping($state->getString("name"), $metaMap[$rid], $rid);

			if (self::$airRid === -1 && $state->getString("name") === "minecraft:air") {
				self::$airRid = $rid;
			} elseif (self::$unknownRid === -1 && $state->getString("name") === "minecraft:unknown") {
				self::$unknownRid = $rid;
			}
			$rid++;
		}
	}

	private static function lazyInit() : void{
		if(self::$bedrockKnownStates === null){
			self::init();
		}
	}

	private static function registerMapping(string $id, int $meta, int $runtimeId) : void{
		self::$idToRuntime[$id][$meta] = $runtimeId;
		self::$runtimeToId[$runtimeId] = [$id, $meta];
	}

	public static function toRuntimeId(string $id, int $meta = 0) : int{
		self::lazyInit();
		return self::$idToRuntime[$id][$meta] ?? self::UNKNOWN();
	}

	public static function fromRuntimeId(int $runtimeId) : array{
		self::lazyInit();
		return self::$runtimeToId[$runtimeId] ?? [BlockIds::UNKNOWN, 0];
	}

	public static function getIdFromRuntimeId(int $runtimeId) : string{
		return self::fromRuntimeId($runtimeId)[0];
	}

	public static function getMetaFromRuntimeId(int $runtimeId) : int{
		return self::fromRuntimeId($runtimeId)[1];
	}

	public static function getBedrockKnownStates() : array{
		self::lazyInit();
		return self::$bedrockKnownStates;
	}

	public static function fromBlockStateNBT(CompoundTag $nbt) : int{
		self::lazyInit();
		$hash = self::hashBlockStateNBT($nbt);
		return self::$stateToRuntimeMap[$hash] ?? self::UNKNOWN();
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
		return self::$hasher ??= new V32();
	}

	/**
	 * @param CompoundTag $blockState
	 *
	 * @return string
	 */
	private static function hashBlockStateNBT(CompoundTag $blockState) : string{
		$writer = new NetworkLittleEndianNBTStream();
		$bytes = $writer->write($blockState);
		return self::getHasher()->hash($bytes);
	}
}
