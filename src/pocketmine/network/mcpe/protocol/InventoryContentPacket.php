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

use pocketmine\block\BlockNames;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\inventory\FullContainerName;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use function count;

class InventoryContentPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::INVENTORY_CONTENT_PACKET;

	public int $windowId;
	/** @var ItemStackWrapper[] */
	public array $items = [];
	public ?FullContainerName $containerName = null;
	public ?ItemStackWrapper $storageItem = null;

	protected function decodePayload() : void{
		$this->windowId = $this->getUnsignedVarInt();
		$count = $this->getUnsignedVarInt();
		for($i = 0; $i < $count; ++$i){
			$this->items[] = ItemStackWrapper::read($this);
		}
		$this->containerName = FullContainerName::read($this);
		$this->storageItem = ItemStackWrapper::read($this);
	}

	protected function encodePayload() : void{
		$this->putUnsignedVarInt($this->windowId);
		$this->putUnsignedVarInt(count($this->items));
		foreach($this->items as $item){
			$item->write($this);
		}
		if($this->containerName === null){
			$this->containerName = new FullContainerName(0);
		}
		$this->containerName->write($this);

		if($this->storageItem === null){
			$this->storageItem = ItemStackWrapper::legacy(ItemFactory::get(BlockNames::AIR));
		}
		$this->storageItem->write($this);

	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleInventoryContent($this);
	}
}
