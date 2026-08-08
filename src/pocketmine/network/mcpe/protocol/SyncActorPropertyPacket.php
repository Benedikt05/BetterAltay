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

use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkSession;
use function base64_decode;
use function file_exists;
use function file_get_contents;
use function is_array;
use function json_decode;
use const pocketmine\RESOURCE_PATH;

class SyncActorPropertyPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SYNC_ACTOR_PROPERTY_PACKET;

	private CompoundTag $data;
	/** @var SyncActorPropertyPacket[]|null */
	private static ?array $cachedPackets = null;

	public static function create(CompoundTag $data) : self{
		$result = new self;
		$result->data = $data;
		return $result;
	}

	/**
	 * @return SyncActorPropertyPacket[]
	 */
	public static function fromJson() : array{
		if(self::$cachedPackets !== null){
			return self::$cachedPackets;
		}

		$filePath = RESOURCE_PATH . '/vanilla/entity_properties.json';
		if(!file_exists($filePath)){
			return self::$cachedPackets = [];
		}

		$content = file_get_contents($filePath);
		if($content === false){
			return self::$cachedPackets = [];
		}

		$jsonData = json_decode($content, true);
		if(!is_array($jsonData) || !isset($jsonData['entries']) || !is_array($jsonData['entries'])){
			return self::$cachedPackets = [];
		}

		$nbtStream = new NetworkLittleEndianNBTStream();
		$packets = [];

		foreach($jsonData['entries'] as $entry){
			if(!isset($entry['nbtB64'])){
				continue;
			}

			$rawNbt = base64_decode($entry['nbtB64'], true);
			if($rawNbt === false){
				continue;
			}

			$compound = $nbtStream->read($rawNbt);
			if($compound instanceof CompoundTag){
				$packets[] = self::create($compound);
			}
		}

		return self::$cachedPackets = $packets;
	}

	public function getData() : CompoundTag{ return $this->data; }

	protected function decodePayload() : void{
		$this->data = $this->getNbtCompoundRoot();
	}

	protected function encodePayload() : void{
		$this->put((new NetworkLittleEndianNBTStream())->write($this->data));
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleSyncActorProperty($this);
	}
}
