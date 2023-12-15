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

class AddVolumeEntityPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ADD_VOLUME_ENTITY_PACKET;

	private int $entityNetId;
	private CompoundTag $data;
	private int $minBoundX;
	private int $minBoundY;
	private int $minBoundZ;
	private int $maxBoundX;
	private int $maxBoundY;
	private int $maxBoundZ;
	private int $dimension;
	private string $engineVersion;

	public static function create(int $entityNetId, CompoundTag $data, int $dimension, string $engineVersion) : self{
		$result = new self;
		$result->entityNetId = $entityNetId;
		$result->data = $data;
		$result->dimension = $dimension;
		$result->engineVersion = $engineVersion;
		return $result;
	}

	public function getEntityNetId() : int{ return $this->entityNetId; }

	public function getData() : CompoundTag{ return $this->data; }

	public function getDimension() : int{ return $this->dimension; }

	public function getEngineVersion() : string{ return $this->engineVersion; }

	protected function decodePayload() : void{
		$this->entityNetId = $this->getUnsignedVarInt();
		$this->data = $this->getNbtCompoundRoot();
		$this->getBlockPosition($this->minBoundX, $this->minBoundY, $this->minBoundZ);
		$this->getBlockPosition($this->maxBoundX, $this->maxBoundY, $this->maxBoundZ);
		$this->dimension = $this->getVarInt();
		$this->engineVersion = $this->getString();
	}

	protected function encodePayload() : void{
		$this->putUnsignedVarInt($this->entityNetId);
		$this->put((new NetworkLittleEndianNBTStream())->write($this->data));
		$this->putBlockPosition($this->minBoundX, $this->minBoundY, $this->minBoundZ);
		$this->putBlockPosition($this->maxBoundX, $this->maxBoundY, $this->maxBoundZ);
		$this->putVarInt($this->dimension);
		$this->putString($this->engineVersion);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAddVolumeEntity($this);
	}
}