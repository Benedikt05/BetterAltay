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
use pocketmine\nbt\LittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\SingletonTrait;
use function array_key_exists;
use function file_get_contents;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function json_decode;
use const pocketmine\RESOURCE_PATH;

final class ItemTypeDictionary{
	use SingletonTrait;

	/**
	 * @var ItemTypeEntry[]
	 * @phpstan-var list<ItemTypeEntry>
	 */
	private $itemTypes;
	/**
	 * @var string[]
	 * @phpstan-var array<int, string>
	 */
	private $intToStringIdMap = [];
	/**
	 * @var int[]
	 * @phpstan-var array<string, int>
	 */
	private $stringToIntMap = [];

	private static function make() : self{
		$table = json_decode(file_get_contents(RESOURCE_PATH . '/vanilla/runtime_item_states.json'), true);
		$componentData = json_decode(file_get_contents(RESOURCE_PATH . '/vanilla/item_components.json'), true);

		if(!is_array($table) || !is_array($componentData)){
			throw new AssumptionFailedError("Invalid resource file format");
		}

		$params = [];
		foreach($table as $entry){
			if(!is_array($entry) || !is_string($entry["name"]) || !isset($entry["componentBased"], $entry["id"], $entry["version"]) || !is_bool($entry["componentBased"]) || !is_int($entry["id"]) || !is_int($entry["version"])){
				throw new AssumptionFailedError("Invalid item list format");
			}

			$nbt = (isset($componentData[$entry["name"]]) && is_string($componentData[$entry["name"]]))
				? (new LittleEndianNBTStream())->read(base64_decode($componentData[$entry["name"]], true))
				: new CompoundTag();

			$params[] = new ItemTypeEntry(
				$entry["name"],
				$entry["id"],
				$entry["componentBased"],
				$entry["version"],
				$nbt
			);
		}
		return new self($params);
	}

	/**
	 * @param ItemTypeEntry[] $itemTypes
	 */
	public function __construct(array $itemTypes){
		$this->itemTypes = $itemTypes;
		foreach($this->itemTypes as $type){
			$this->stringToIntMap[$type->getStringId()] = $type->getNumericId();
			$this->intToStringIdMap[$type->getNumericId()] = $type->getStringId();
		}
	}

	/**
	 * @return ItemTypeEntry[]
	 * @phpstan-return list<ItemTypeEntry>
	 */
	public function getEntries() : array{
		return $this->itemTypes;
	}

	public function fromStringId(string $stringId) : int{
		if(!array_key_exists($stringId, $this->stringToIntMap)){
			throw new InvalidArgumentException("Unmapped string ID \"$stringId\"");
		}
		return $this->stringToIntMap[$stringId];
	}

	public function fromIntId(int $intId) : string{
		if(!array_key_exists($intId, $this->intToStringIdMap)){
			throw new InvalidArgumentException("Unmapped int ID $intId");
		}
		return $this->intToStringIdMap[$intId];
	}
}
