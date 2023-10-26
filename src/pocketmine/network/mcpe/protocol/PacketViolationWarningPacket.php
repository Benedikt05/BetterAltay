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

class PacketViolationWarningPacket extends DataPacket/* implements ServerboundPacket*/
{
	public const NETWORK_ID = ProtocolInfo::PACKET_VIOLATION_WARNING_PACKET;

	public const TYPE_MALFORMED = 0;

	public const SEVERITY_WARNING = 0;
	public const SEVERITY_FINAL_WARNING = 1;
	public const SEVERITY_TERMINATING_CONNECTION = 2;

	/** @var int */
	private $type;
	/** @var int */
	private $severity;
	/** @var int */
	private $packetId;
	/** @var string */
	private $message;

	public static function create(int $type, int $severity, int $packetId, string $message) : self{
		$result = new self;

		$result->type = $type;
		$result->severity = $severity;
		$result->packetId = $packetId;
		$result->message = $message;

		return $result;
	}

	public function getType() : int{ return $this->type; }

	public function getSeverity() : int{ return $this->severity; }

	public function getPacketId() : int{ return $this->packetId; }

	public function getMessage() : string{ return $this->message; }

	protected function decodePayload() : void{
		$this->type = $this->getVarInt();
		$this->severity = $this->getVarInt();
		$this->packetId = $this->getVarInt();
		$this->message = $this->getString();
	}

	protected function encodePayload() : void{
		$this->putVarInt($this->type);
		$this->putVarInt($this->severity);
		$this->putVarInt($this->packetId);
		$this->putString($this->message);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePacketViolationWarning($this);
	}
}
