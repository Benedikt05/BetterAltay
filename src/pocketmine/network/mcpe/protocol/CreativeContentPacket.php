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
use RuntimeException;
use function count;

class CreativeContentPacket extends DataPacket/* implements ClientboundPacket*/
{
	public const NETWORK_ID = ProtocolInfo::CREATIVE_CONTENT_PACKET;


	public array $groups = [];
	public array $items = [];

	public static function create(/*array $groups, array $items*/) : self{
		//$result->groups = $groups;
		//$result->items = $items;
		return new self;
	}


	protected function decodePayload() : void{
		throw new RuntimeException("this should never happen");
	}

	protected function encodePayload() : void{
		$this->putUnsignedVarInt(0); //groups list size
		$this->putUnsignedVarInt(0); //entries list size
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCreativeContent($this);
	}
}
