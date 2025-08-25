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

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Color;
use function file_get_contents;
use function json_decode;
use const pocketmine\RESOURCE_PATH;

class BiomeDefinitionListPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::BIOME_DEFINITION_LIST_PACKET;

	/** @var array<string, array> */
	private array $biomeDefinitions = [];
	private static ?array $DEFAULT_BIOME_CACHE = null;

	protected function decodePayload() : void{
		//TODO: not implemented
	}

	protected function encodePayload() : void{
		$stringPool = [];
		$stringIndices = [];

		$this->putUnsignedVarInt(count($this->biomeDefinitions));

		foreach($this->biomeDefinitions as $name => $def){
			$index = $stringIndices[$name] ??= count($stringPool);
			if(!in_array($name, $stringPool, true)){
				$stringPool[] = $name;
			}
			$this->putLShort($index);
			$this->putLShort((int) ($def["id"] ?? -1));
			$this->putLFloat((float) $def["temperature"]);
			$this->putLFloat((float) $def["downfall"]);
			$this->putLFloat(1); //TODO: foliage snow
			$this->putLFloat((float) $def["depth"]);
			$this->putLFloat((float) $def["scale"]);
			$this->putLInt($def["mapWaterColor"] instanceof Color ? $def["mapWaterColor"]->toARGB() : 0);
			$this->putBool((bool) $def["rain"]);
			$this->putBool(false); //optional tags
			$this->putBool(false); //optional chunk gen
		}

		$this->putUnsignedVarInt(count($stringPool));
		foreach($stringPool as $str){
			$this->putString($str);
		}
	}

	public static function create() : self{
		if(self::$DEFAULT_BIOME_CACHE === null){
			$data = json_decode(file_get_contents(RESOURCE_PATH . '/vanilla/stripped_biome_definitions.json'), true);

			if(!is_array($data)){
				throw new AssumptionFailedError("Invalid resource file format");
			}

			self::processBiomeData($data);
			self::$DEFAULT_BIOME_CACHE = $data;
		}

		$pk = new self;
		$pk->biomeDefinitions = self::$DEFAULT_BIOME_CACHE;
		return $pk;
	}

	/**
	 * Helper method to process raw biome data, converting mapWaterColor to Color objects.
	 *
	 * @param array<string, array> $data
	 */
	private static function processBiomeData(array &$data) : void{
		foreach($data as &$def){
			if(isset($def["mapWaterColor"])){
				$def["mapWaterColor"] = new Color(
					$def["mapWaterColor"]["r"] ?? 0,
					$def["mapWaterColor"]["g"] ?? 0,
					$def["mapWaterColor"]["b"] ?? 0,
					$def["mapWaterColor"]["a"] ?? 255
				);
			}
		}
	}

	public function getBiomeDefinitions() : array{
		return $this->biomeDefinitions;
	}

	public function setBiomeDefinitions(array $biomeDefinitions) : void{
		$this->biomeDefinitions = $biomeDefinitions;
	}


	public function handle(NetworkSession $session) : bool{
		return $session->handleBiomeDefinitionList($this);
	}
}
