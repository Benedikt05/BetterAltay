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

class LevelEventGenericPacket extends DataPacket/* implements ClientboundPacket*/
{
	public const NETWORK_ID = ProtocolInfo::LEVEL_EVENT_GENERIC_PACKET;

	/** @var int */
	private $eventId;
	/** @var string network-format NBT */
	private $eventData;

	public static function create(int $eventId, CompoundTag $data) : self{
		$result = new self;
		$result->eventId = $eventId;
		$result->eventData = (new NetworkLittleEndianNBTStream())->write($data);
		return $result;
	}

	public function getEventId() : int{
		return $this->eventId;
	}

	public function getEventData() : string{
		return $this->eventData;
	}

	protected function decodePayload() : void{
		$this->eventId = $this->getVarInt();
		$this->eventData = $this->getRemaining();
	}

	protected function encodePayload() : void{
		$this->putVarInt($this->eventId);
		$this->put($this->eventData);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleLevelEventGeneric($this);
	}
}
