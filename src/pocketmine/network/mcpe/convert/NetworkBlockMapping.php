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

use pocketmine\block\BlockIds;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\utils\AssumptionFailedError;
use RuntimeException;
use function file_get_contents;
use const pocketmine\RESOURCE_PATH;

/**
 * @internal
 */
final class NetworkBlockMapping{

	/** @var int[] */
	private static $legacyToNetworkMap = [];
	/** @var int[] */
	private static $networkToLegacyMap = [];
	/** @var CompoundTag[]|null */
	private static $bedrockKnownStates = null;

	public const NO_NETWORK_ID = 0;

	private function __construct(){
		//NOOP
	}

	public static function init() : void{
		$blockPaletteFile = file_get_contents(RESOURCE_PATH . "vanilla/block_palette.nbt");
		if($blockPaletteFile === false){
			throw new AssumptionFailedError("Missing required resource file");
		}
		$blockPaletteGunzip = @gzdecode($blockPaletteFile);
		if($blockPaletteGunzip === false){
			throw new AssumptionFailedError("Invalid block palette file compression algorithm");
		}
		$stream = new NetworkBinaryStream($blockPaletteGunzip);

		$list = [];
		foreach ($stream->getNbtCompoundRoot(new BigEndianNBTStream)->getTag("blocks") as $value) {
			$list[] = $value;
		}

		self::$bedrockKnownStates = $list;

		self::setupBlockMappings();
	}

	private static function setupBlockMappings(): void
	{
		$idToStatesMap = [];
		$idToNetIdsMap = [];
		foreach (self::$bedrockKnownStates as $state) {
			$idToStatesMap[$stringId = $state->getString("name")][] = $state->getInt("block_id");
			$idToNetIdsMap[$stringId][] = $state->getInt("network_id");
		}

		$previousName = "";
		$legacyMeta = 0;
		foreach ($idToNetIdsMap as $name => $nIds) {
			if ($name !== $previousName || $legacyMeta > 15) {
				$legacyMeta = 0;
			}

			$netId = $nIds[$legacyMeta] ?? null;
			$legacyId = $idToStatesMap[$name][$legacyMeta] ?? null;
			if($netId === null){
				throw new RuntimeException("No network ID matches $name");
			}
			if($legacyId === null){
				throw new RuntimeException("No legacy ID matches $name");
			}

			self::registerMapping($netId, $legacyId, $legacyMeta);

			++$legacyMeta;
			$previousName = $name;
		}
	}

	private static function lazyInit() : void{
		if(self::$bedrockKnownStates === null){
			self::init();
		}
	}

	public static function toStaticNetworkId(int $id, int $meta = 0) : int{
		self::lazyInit();
		/*
		 * try id+meta first
		 * if not found, try id+0 (strip meta)
		 * if still not found, return update! block
		 */
		return self::$legacyToNetworkMap[($id << 4) | $meta] ?? self::$legacyToNetworkMap[$id << 4] ?? self::$legacyToNetworkMap[BlockIds::INFO_UPDATE << 4];
	}

	/**
	 * @return int[] [id, meta]
	 */
	public static function fromStaticNetworkId(int $networkId) : array{
		self::lazyInit();
		$v = self::$networkToLegacyMap[$networkId];
		return [$v >> 4, $v & 0xf];
	}

	private static function registerMapping(int $staticNetworkId, int $legacyId, int $legacyMeta) : void{
		self::$legacyToNetworkMap[($legacyId << 4) | $legacyMeta] = $staticNetworkId;
		self::$networkToLegacyMap[$staticNetworkId] = ($legacyId << 4) | $legacyMeta;
	}

	/**
	 * @return CompoundTag[]
	 */
	public static function getBedrockKnownStates() : array{
		self::lazyInit();
		return self::$bedrockKnownStates;
	}
}
