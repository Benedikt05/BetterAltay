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

class EmotePacket extends DataPacket/* implements ClientboundPacket, ServerboundPacket*/
{
	public const NETWORK_ID = ProtocolInfo::EMOTE_PACKET;

	public const FLAG_SERVER = 1 << 0;
	public const FLAG_MUTE_EMOTE_CHAT = 1 << 1;

	private int $entityRuntimeId;
	private string $emoteId;
	private string $xuid;
	private string $platformId;
	private int $flags;


	public static function create(int $entityRuntimeId, string $emoteId, string $xuid, string $platformId, int $flags) : self{
		$result = new self;
		$result->entityRuntimeId = $entityRuntimeId;
		$result->emoteId = $emoteId;
		$result->xuid = $xuid;
		$result->platformId = $platformId;
		$result->flags = $flags;
		return $result;
	}

	/**
	 * TODO: we can't call this getEntityRuntimeId() because of base class collision (crap architecture, thanks Shoghi)
	 */
	public function getEntityRuntimeIdField() : int{
		return $this->entityRuntimeId;
	}

	public function getEmoteId() : string{
		return $this->emoteId;
	}

	public function getXuid() : string{
		return $this->xuid;
	}

	public function getPlatformId() : string{
		return $this->platformId;
	}

	public function getFlags() : int{
		return $this->flags;
	}

	protected function decodePayload() : void{
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		$this->emoteId = $this->getString();
		$this->xuid = $this->getString();
		$this->platformId = $this->getString();
		$this->flags = $this->getByte();
	}

	protected function encodePayload() : void{
		$this->putEntityRuntimeId($this->entityRuntimeId);
		$this->putString($this->emoteId);
		$this->putString($this->xuid);
		$this->putString($this->platformId);
		$this->putByte($this->flags);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleEmote($this);
	}
}
