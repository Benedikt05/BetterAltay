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
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\ItemComponentPacketEntry;
use function count;

class ItemComponentPacket extends DataPacket/* implements ClientboundPacket*/
{
	public const NETWORK_ID = ProtocolInfo::ITEM_COMPONENT_PACKET;

	/**
	 * @var ItemComponentPacketEntry[]
	 * @phpstan-var list<ItemComponentPacketEntry>
	 */
	private $entries;

	/**
	 * @param ItemComponentPacketEntry[]             $entries
	 *
	 * @phpstan-param list<ItemComponentPacketEntry> $entries
	 */
	public static function create(array $entries) : self{
		$result = new self;
		$result->entries = $entries;
		return $result;
	}

	/**
	 * @return ItemComponentPacketEntry[]
	 * @phpstan-return list<ItemComponentPacketEntry>
	 */
	public function getEntries() : array{ return $this->entries; }

	protected function decodePayload() : void{
		$this->entries = [];
		for($i = 0, $len = $this->getUnsignedVarInt(); $i < $len; ++$i){
			$name = $this->getString();
			$nbt = $this->getNbtCompoundRoot();
			$this->entries[] = new ItemComponentPacketEntry($name, $nbt);
		}
	}

	protected function encodePayload() : void{
		$this->putUnsignedVarInt(count($this->entries));
		foreach($this->entries as $entry){
			$this->putString($entry->getName());
			$this->put((new NetworkLittleEndianNBTStream())->write($entry->getComponentNbt()));
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleItemComponent($this);
	}
}
