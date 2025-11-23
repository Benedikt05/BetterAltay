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

namespace pocketmine\build\generate_item_serializer_ids;

use pocketmine\data\bedrock\item\BlockItemIdMap;
use pocketmine\errorhandler\ErrorToExceptionHandler;
use pocketmine\network\mcpe\convert\ItemTranslator;
use pocketmine\network\mcpe\convert\ItemTypeDictionary;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Utils;
use function asort;
use function count;
use function dirname;
use function explode;
use function fclose;
use function file_get_contents;
use function fopen;
use function fwrite;
use function strtoupper;
use const pocketmine\RESOURCE_PATH;
use const SORT_STRING;
use const STDERR;

require dirname(__DIR__) . '/vendor/autoload.php';

function trapErrors(callable $fn){
	set_error_handler(function($severity, $message, $file, $line){
		throw new \ErrorException($message, 0, $severity, $file, $line);
	});

	try{
		$result = $fn();
	}finally{
		restore_error_handler();
	}

	return $result;
}

function constifyMcId(string $id) : string{
	return strtoupper(explode(":", $id, 2)[1]);
}

function generateItemIds(ItemTypeDictionary $dictionary, ItemTranslator $itemTranslator) : void{
	$ids = [];
	foreach($dictionary->getEntries() as $entry){
		if($entry->getStringId() === "minecraft:air" || $itemTranslator->toBlockId($entry->getStringId()) !== null){
			continue;
		}
		$ids[$entry->getStringId()] = $entry->getStringId();
	}

	$data = file_get_contents(RESOURCE_PATH . '/vanilla/r16_to_current_item_map.json');
	if($data === false) throw new AssumptionFailedError("Missing required resource file");
	$json = json_decode($data, true);
	if(!is_array($json) or !isset($json["simple"]) || !is_array($json["simple"])){
		throw new AssumptionFailedError("Invalid item table format");
	}

	$oldIds = [];

	foreach($json["simple"] as $oldId => $newId){
		if(!is_string($oldId) || !is_string($newId)){
			throw new AssumptionFailedError("Invalid item table format");
		}

		$parsedId = str_replace(["."], "_", $oldId);

		if(isset($ids[$parsedId])){
			continue;
		}

		$oldIds[$parsedId] = $newId;
	}

	asort($ids, SORT_STRING);

	$file = trapErrors(fn() => fopen(dirname(__DIR__) . '/src/pocketmine/item/ItemIds.php', 'wb'));

	fwrite($file, <<<'HEADER'
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

namespace pocketmine\item;

/**
 * This class is generated automatically from the item type dictionary for the current version. Do not edit it manually.
 */
interface ItemIds{

HEADER
	);

	foreach(Utils::stringifyKeys($ids) as $id){
		fwrite($file, "\tpublic const " . constifyMcId($id) . " = \"" . $id . "\";\n");
	}
	foreach(Utils::stringifyKeys($oldIds) as $oldId => $newId){
		fwrite($file, "\n\t/** @deprecated Use self::" . constifyMcId($newId) . " instead. */\n");
		fwrite($file, "\tpublic const " . constifyMcId($oldId) . " = \"" . $newId . "\";\n");
	}
	fwrite($file, "}\n");
	fclose($file);
}

if(count($argv) !== 2){
	fwrite(STDERR, "This script regenerates ItemTypeNames from a given item dictionary file\n");
	fwrite(STDERR, "Required argument: path to item type dictionary file\n");
	exit(1);
}

$raw = file_get_contents($argv[1]);
if($raw === false){
	fwrite(STDERR, "Failed to read item type dictionary file\n");
	exit(1);
}

$dictionary = ItemTypeDictionary::getInstance();
$itemTranslator = ItemTranslator::getInstance();
generateItemIds($dictionary, $itemTranslator);

echo "Done. Don't forget to run CS fixup after generating code.\n";