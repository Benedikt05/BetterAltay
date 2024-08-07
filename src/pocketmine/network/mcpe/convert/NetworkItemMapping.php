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
use function file_get_contents;
use function is_array;
use function json_decode;
use const pocketmine\RESOURCE_PATH;

/**
 * This class handles translation between network item ID+metadata to PocketMine-MP internal ID+metadata and vice versa.
 */
final class NetworkItemMapping{
	use SingletonTrait;

	private array $mappedIdsToNetMap = [];
	private array $netToMappedIdsMap = [];
	private array $legacyToNetMap = [];
	private array $netToCLegacyMap = [];
	private array $nonLegacyToNetMap = [];
	private array $netToNonLegacyMap = [];

	private static function make() : self{
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

					if (!isset($this->mappedIdsToNetMap[$legacyId])) {
						$this->mappedIdsToNetMap[$legacyId] = [];
					}

					$this->mappedIdsToNetMap[$legacyId][$meta] = $netId;
					$this->netToMappedIdsMap[$legacyId] = [$legacyId, $meta];
				}
			}elseif(isset($legacyItemIds[$stringId])) {
				if (!isset($this->legacyToNetMap[$netId])) {
					$this->legacyToNetMap[$netId] = [];
				}

				$legacyId = $legacyItemIds[$stringId];

				$this->legacyToNetMap[$legacyId] = $netId;
				$this->netToCLegacyMap[$netId] = $legacyId;
			} else {
				if (!isset($this->nonLegacyToNetMap[$netId])) {
					$this->nonLegacyToNetMap[$netId] = [];
				}

				// todo: what nonesense is this, should wait until i found a good idea for it.
				$this->nonLegacyToNetMap[$netId] = $netId;
				$this->netToNonLegacyMap[$netId] = $netId;
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
		if (isset($this->mappedIdsToNetMap[$internalId][$internalMeta])) {
			return [$this->mappedIdsToNetMap[$internalId][$internalMeta], 0];
		}
		if(isset($this->legacyToNetMap[$internalId])){
			return [$this->legacyToNetMap[$internalId], $internalMeta];
		}
		if (isset($this->nonLegacyToNetMap[$internalId])) {
			return [$this->nonLegacyToNetMap[$internalId], $internalMeta];
		}

		throw new InvalidArgumentException("Unmapped ID/metadata combination $internalId:$internalMeta");
	}

	/**
	 * @return int[]
	 * @phpstan-return array{int, int}
	 */
	public function fromNetworkId(int $networkId, int $networkMeta, bool &$isMapped = false) : array{
		if($networkMeta !== 0){
			throw new UnexpectedValueException("Unexpected non-zero network meta on complex item mapping");
		}
		if (isset($this->mappedIdsToNetMap[$networkId][$networkMeta])) {
			$isMapped = true;
			return $this->mappedIdsToNetMap[$networkId];
		}
		if(isset($this->netToCLegacyMap[$networkId])){
			return [$this->netToCLegacyMap[$networkId], 0];
		}
		if (isset($this->netToNonLegacyMap[$networkId])) {
			return [$this->netToNonLegacyMap[$networkId], 0];
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
