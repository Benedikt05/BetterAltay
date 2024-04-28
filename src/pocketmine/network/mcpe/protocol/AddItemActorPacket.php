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

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;

class AddItemActorPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ADD_ITEM_ACTOR_PACKET;

	public ?int $entityUniqueId = null; //TODO
	public int $entityRuntimeId;
	public ItemStackWrapper $item;
	public Vector3 $position;
	public ?Vector3 $motion;
	/**
	 * @var mixed[][]
	 * @phpstan-var array<int, array{0: int, 1: mixed}>
	 */
	public array $metadata = [];
	public bool $isFromFishing = false;

	protected function decodePayload(){
		$this->entityUniqueId = $this->getEntityUniqueId();
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		$this->item = ItemStackWrapper::read($this);
		$this->position = $this->getVector3();
		$this->motion = $this->getVector3();
		$this->metadata = $this->getEntityMetadata();
		$this->isFromFishing = $this->getBool();
	}

	protected function encodePayload(){
		$this->putEntityUniqueId($this->entityUniqueId ?? $this->entityRuntimeId);
		$this->putEntityRuntimeId($this->entityRuntimeId);
		$this->item->write($this);
		$this->putVector3($this->position);
		$this->putVector3Nullable($this->motion);
		$this->putEntityMetadata($this->metadata);
		$this->putBool($this->isFromFishing);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAddItemActor($this);
	}
}
