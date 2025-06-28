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
use pocketmine\utils\Color;
use function file_get_contents;

class BiomeDefinitionListPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::BIOME_DEFINITION_LIST_PACKET;

	/** @var array<string, array> */
	public array $biomeDefinitions = [];

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
			$this->putBool(false); //optional ID
			$this->putLFloat((float) $def["temperature"]);
			$this->putLFloat((float) $def["downfall"]);
			$this->putLFloat((float) $def["redSporeDensity"]);
			$this->putLFloat((float) $def["blueSporeDensity"]);
			$this->putLFloat((float) $def["ashDensity"]);
			$this->putLFloat((float) $def["whiteAshDensity"]);
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

	public static function fromJsonFile(string $path) : self{
		$data = json_decode(file_get_contents($path), true);
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
		$pk = new self;
		$pk->biomeDefinitions = $data;
		return $pk;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleBiomeDefinitionList($this);
	}
}
