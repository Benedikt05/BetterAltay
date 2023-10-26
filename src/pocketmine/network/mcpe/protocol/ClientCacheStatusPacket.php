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

class ClientCacheStatusPacket extends DataPacket/* implements ServerboundPacket*/
{
	public const NETWORK_ID = ProtocolInfo::CLIENT_CACHE_STATUS_PACKET;

	/** @var bool */
	private $enabled;

	public static function create(bool $enabled) : self{
		$result = new self;
		$result->enabled = $enabled;
		return $result;
	}

	public function isEnabled() : bool{
		return $this->enabled;
	}

	protected function decodePayload() : void{
		$this->enabled = $this->getBool();
	}

	protected function encodePayload() : void{
		$this->putBool($this->enabled);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleClientCacheStatus($this);
	}
}
