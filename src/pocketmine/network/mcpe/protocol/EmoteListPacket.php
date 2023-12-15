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
use pocketmine\utils\UUID;
use function count;

class EmoteListPacket extends DataPacket/* implements ClientboundPacket*/
{
	public const NETWORK_ID = ProtocolInfo::EMOTE_LIST_PACKET;

	/** @var int */
	private $playerEntityRuntimeId;
	/** @var UUID[] */
	private $emoteIds;

	/**
	 * @param UUID[] $emoteIds
	 */
	public static function create(int $playerEntityRuntimeId, array $emoteIds) : self{
		$result = new self;
		$result->playerEntityRuntimeId = $playerEntityRuntimeId;
		$result->emoteIds = $emoteIds;
		return $result;
	}

	public function getPlayerEntityRuntimeId() : int{ return $this->playerEntityRuntimeId; }

	/** @return UUID[] */
	public function getEmoteIds() : array{ return $this->emoteIds; }

	protected function decodePayload() : void{
		$this->playerEntityRuntimeId = $this->getEntityRuntimeId();
		$this->emoteIds = [];
		for($i = 0, $len = $this->getUnsignedVarInt(); $i < $len; ++$i){
			$this->emoteIds[] = $this->getUUID();
		}
	}

	protected function encodePayload() : void{
		$this->putEntityRuntimeId($this->playerEntityRuntimeId);
		$this->putUnsignedVarInt(count($this->emoteIds));
		foreach($this->emoteIds as $emoteId){
			$this->putUUID($emoteId);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleEmoteList($this);
	}
}
