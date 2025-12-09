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
use pocketmine\network\mcpe\protocol\types\DebugMarkerData;

class ClientboundDebugRendererPacket extends DataPacket/* implements ClientboundPacket*/
{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_DEBUG_RENDERER_PACKET;

	public const TYPE_CLEAR = "cleardebugmarkers";
	public const TYPE_ADD_CUBE = "adddebugmarkercube";
	private string $type;
	private ?DebugMarkerData $data = null;

	private static function base(string $type) : self{
		$result = new self;
		$result->type = $type;
		return $result;
	}

	public static function clear() : self{ return self::base(self::TYPE_CLEAR); }

	public static function addCube(DebugMarkerData $data) : self{
		$result = self::base(self::TYPE_ADD_CUBE);
		$result->data = $data;
		return $result;
	}

	public function getType() : string{ return $this->type; }

	protected function decodePayload() : void{
		$this->type = $this->getString();
		$this->data = $this->readOptional(fn() => DebugMarkerData::read($this));
	}

	protected function encodePayload() : void{
		$this->putString($this->type);
		$this->writeOptional($this->data, fn(DebugMarkerData $data) => $data->write($this));
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleClientboundDebugRenderer($this);
	}
}
