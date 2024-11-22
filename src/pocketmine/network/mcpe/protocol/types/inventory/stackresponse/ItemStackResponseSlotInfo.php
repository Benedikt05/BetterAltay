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

namespace pocketmine\network\mcpe\protocol\types\inventory\stackresponse;

use pocketmine\network\mcpe\NetworkBinaryStream;

final class ItemStackResponseSlotInfo{

	private int $slot;
	private int $hotbarSlot;
	private int $count;
	private int $itemStackId;
	private string $customName;
	private string $filteredCustomName;
	private int $durabilityCorrection;

	public function __construct(int $slot, int $hotbarSlot, int $count, int $itemStackId, string $customName, string $filteredCustomName, int $durabilityCorrection){
		$this->slot = $slot;
		$this->hotbarSlot = $hotbarSlot;
		$this->count = $count;
		$this->itemStackId = $itemStackId;
		$this->customName = $customName;
		$this->filteredCustomName = $filteredCustomName;
		$this->durabilityCorrection = $durabilityCorrection;
	}

	public function getSlot() : int{ return $this->slot; }

	public function getHotbarSlot() : int{ return $this->hotbarSlot; }

	public function getCount() : int{ return $this->count; }

	public function getItemStackId() : int{ return $this->itemStackId; }

	public function getCustomName() : string{ return $this->customName; }

	public function getDurabilityCorrection() : int{ return $this->durabilityCorrection; }

	public static function read(NetworkBinaryStream $in) : self{
		$slot = $in->getByte();
		$hotbarSlot = $in->getByte();
		$count = $in->getByte();
		$itemStackId = $in->readGenericTypeNetworkId();
		$customName = $in->getString();
		$filteredCustomName = $in->getString();
		$durabilityCorrection = $in->getVarInt();
		return new self($slot, $hotbarSlot, $count, $itemStackId, $customName, $filteredCustomName, $durabilityCorrection);
	}

	public function write(NetworkBinaryStream $out) : void{
		$out->putByte($this->slot);
		$out->putByte($this->hotbarSlot);
		$out->putByte($this->count);
		$out->writeGenericTypeNetworkId($this->itemStackId);
		$out->putString($this->customName);
		$out->putString($this->filteredCustomName);
		$out->putVarInt($this->durabilityCorrection);
	}
}
