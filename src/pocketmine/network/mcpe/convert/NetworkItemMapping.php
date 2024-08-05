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

use InvalidArgumentException;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\SingletonTrait;
use UnexpectedValueException;
use function array_key_exists;
use function file_get_contents;
use function is_array;
use function is_numeric;
use function is_string;
use function json_decode;
use const pocketmine\RESOURCE_PATH;

/**
 * This class handles translation between network item ID+metadata to PocketMine-MP internal ID+metadata and vice versa.
 */
final class NetworkItemMapping{ // TODO: not finished and might need a fourth rewrite
	use SingletonTrait;

	private array $mappedCoreToNetMap = [];
	private array $netToMappedCoreMap = [];
	private array $coreToNetMap = [];
	private array $netToCoreMap = [];
	private array $nonLegacyCoreToNetMap = [];
	private array $netToNonLegacyCoreMap = [];

	private static function make() : self{
		// $data = file_get_contents(RESOURCE_PATH . '/vanilla/r16_to_current_item_map.json');
		// if($data === false) throw new AssumptionFailedError("Missing required resource file");
		// $json = json_decode($data, true);
		// if(!is_array($json) or !isset($json["simple"], $json["complex"]) || !is_array($json["simple"]) || !is_array($json["complex"])){
		// 	throw new AssumptionFailedError("Invalid item table format");
		// }

		// $legacyStringToIntMapRaw = file_get_contents(RESOURCE_PATH . '/vanilla/item_id_map.json');
		// if($legacyStringToIntMapRaw === false){
		// 	throw new AssumptionFailedError("Missing required resource file");
		// }
		// $legacyStringToIntMap = json_decode($legacyStringToIntMapRaw, true);
		// if(!is_array($legacyStringToIntMap)){
		// 	throw new AssumptionFailedError("Invalid mapping table format");
		// }

		// /** @phpstan-var array<string, int> $simpleMappings */
		// $simpleMappings = [];
		// foreach($json["simple"] as $oldId => $newId){
		// 	if(!is_string($oldId) || !is_string($newId)){
		// 		throw new AssumptionFailedError("Invalid item table format");
		// 	}
		// 	if(!isset($legacyStringToIntMap[$oldId])){
		// 		//new item without a fixed legacy ID - we can't handle this right now
		// 		continue;
		// 	}
		// 	$simpleMappings[$newId] = $legacyStringToIntMap[$oldId];
		// }
		// foreach($legacyStringToIntMap as $stringId => $intId){
		// 	if(isset($simpleMappings[$stringId])){
		// 		throw new UnexpectedValueException("Old ID $stringId collides with new ID");
		// 	}
		// 	$simpleMappings[$stringId] = $intId;
		// }

		// /** @phpstan-var array<string, array{int, int}> $complexMappings */
		// $complexMappings = [];
		// foreach($json["complex"] as $oldId => $map){
		// 	if(!is_string($oldId) || !is_array($map)){
		// 		throw new AssumptionFailedError("Invalid item table format");
		// 	}
		// 	foreach($map as $meta => $newId){
		// 		if(!is_numeric($meta) || !is_string($newId)){
		// 			throw new AssumptionFailedError("Invalid item table format");
		// 		}
		// 		$legacyStringToIntMap["minecraft:stone_block_slab"] = 44;
		// 		$complexMappings[$newId] = [$legacyStringToIntMap[$oldId], (int) $meta];
		// 	}
		// }
		$itemMappingsRaw = file_get_contents(RESOURCE_PATH . '/vanilla/item_mappings.json');
		if($itemMappingsRaw === false) throw new AssumptionFailedError("Missing required resource file");
		$itemMappings = json_decode($itemMappingsRaw, true);
		if(!is_array($itemMappings)){
			throw new AssumptionFailedError("Invalid item table format");
		}

		$legacyItemIdsRaw = file_get_contents(RESOURCE_PATH . '/vanilla/legacy_item_ids.json');
		if($legacyItemIdsRaw === false){
			throw new AssumptionFailedError("Missing required resource file");
		}
		$legacyItemIds = json_decode($legacyItemIdsRaw, true);
		if(!is_array($legacyItemIds)){
			throw new AssumptionFailedError("Invalid mapping table format");
		}

		return new self(ItemTypeDictionary::getInstance(), $itemMappings, $legacyItemIds);
	}

	public function __construct(ItemTypeDictionary $dictionary, array $itemMappings, array $legacyItemIds){
		foreach($dictionary->getEntries() as $entry){
			$stringId = $entry->getStringId();
			$netId = $entry->getNumericId();

			if (isset($itemMappings[$stringId])) {
				foreach ($itemMappings[$stringId] as $meta => $newStrId) {
					$meta = intval($meta);
					$legacyId = $legacyItemIds[$newStrId];

					if (!isset($this->mappedCoreToNetMap[$legacyId])) {
						$this->mappedCoreToNetMap[$legacyId] = [];
					}

					$this->mappedCoreToNetMap[$legacyId][$meta] = $netId;
					$this->netToMappedCoreMap[$legacyId] = [$legacyId, $meta];
				}
			}elseif(isset($legacyItemIds[$stringId])) {
				if (!isset($this->coreToNetMap[$netId])) {
					$this->coreToNetMap[$netId] = [];
				}

				$this->coreToNetMap[$legacyItemIds[$stringId]][0] = $netId;
				$this->netToCoreMap[$netId] = [$legacyItemIds[$stringId], 0];
			} else {
				// var_dump("It is non legacy - todo");
				// continue;
				if (!isset($this->nonLegacyCoreToNetMap[$netId])) {
					$this->nonLegacyCoreToNetMap[$netId] = [];
				}

				$this->nonLegacyCoreToNetMap[$netId][0] = $netId;
				$this->netToNonLegacyCoreMap[$netId] = [$netId, 0];
				// $this->coreToNetMap[$netId][0] = [];
			}
		}
	}

	/**
	 * @return int[]
	 * @phpstan-return array{int, int}
	 */
	public function toNetworkId(int $internalId, int $internalMeta) : array{
		if($internalMeta === -1){
			$internalMeta = 0x7fff;
		}
		if (isset($this->mappedCoreToNetMap[$internalId][$internalMeta])) {
			return [$this->mappedCoreToNetMap[$internalId][$internalMeta], 0];
		}
		if(isset($this->coreToNetMap[$internalId][$internalMeta])){
			return [$this->coreToNetMap[$internalId][$internalMeta], $internalMeta];
		}
		if (isset($this->nonLegacyCoreToNetMap[$internalId][$internalMeta])) {
			return [$this->nonLegacyCoreToNetMap[$internalId][$internalMeta], 0];
		}

		// throw new InvalidArgumentException("Unmapped ID/metadata combination $internalId:$internalMeta");
		return [NetworkBlockMapping::toStaticNetworkId($internalId, $internalMeta), $internalMeta]; // TODO: HACK (in the rewrite this will be removed)
	}

	/**
	 * @return int[]
	 * @phpstan-return array{int, int}
	 */
	public function fromNetworkId(int $networkId, int $networkMeta, bool &$isMapped = false) : array{
		if($networkMeta !== 0){
			throw new UnexpectedValueException("Unexpected non-zero network meta on complex item mapping");
		}
		if (isset($this->mappedCoreToNetMap[$networkId][$networkMeta])) {
			$isMapped = true;
			return $this->mappedCoreToNetMap[$networkId];
		}
		if(isset($this->netToCoreMap[$networkId])){
			return $this->netToCoreMap[$networkId];
		}
		if (isset($this->netToNonLegacyCoreMap[$networkId][$networkMeta])) {
			return $this->netToNonLegacyCoreMap[$networkId];
		}
		throw new UnexpectedValueException("Unmapped network ID/metadata combination $networkId:$networkMeta");
	}

	/**
	 * @return int[]
	 * @phpstan-return array{int, int}
	 */
	public function fromNetworkIdWithWildcardHandling(int $networkId, int $networkMeta) : array{
		$isMapped = false;
		if($networkMeta !== 0x7fff){
			return $this->fromNetworkId($networkId, $networkMeta);
		}
		[$id, $meta] = $this->fromNetworkId($networkId, 0, $isMapped);
		return [$id, $isMapped ? $meta : -1];
	}
}
