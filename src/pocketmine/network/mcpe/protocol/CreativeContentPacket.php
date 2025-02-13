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
use pocketmine\network\mcpe\protocol\types\inventory\CreativeGroupEntry;
use pocketmine\network\mcpe\protocol\types\inventory\CreativeItemEntry;
use RuntimeException;
use function count;

class CreativeContentPacket extends DataPacket/* implements ClientboundPacket*/
{
	public const NETWORK_ID = ProtocolInfo::CREATIVE_CONTENT_PACKET;

	public const CATEGORY_CONSTRUCTION = 1;
	public const CATEGORY_NATURE = 2;
	public const CATEGORY_EQUIPMENT = 3;
	public const CATEGORY_ITEMS = 4;

	/** @var CreativeGroupEntry[] */
	public array $groups = [];
	/** @var CreativeItemEntry[] */
	public array $items = [];

	public static function create(array $groups, array $items) : self{
		$result = new self;
		$result->groups = $groups;
		$result->items = $items;
		return $result;
	}

	protected function decodePayload() : void{
		throw new RuntimeException("this should never happen");
	}

	protected function encodePayload() : void{
		$this->putUnsignedVarInt(count($this->groups));
		foreach($this->groups as $entry){
			$entry->write($this);
		}

		$this->putUnsignedVarInt(count($this->items));
		foreach($this->items as $entry){
			$entry->write($this);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCreativeContent($this);
	}
}
