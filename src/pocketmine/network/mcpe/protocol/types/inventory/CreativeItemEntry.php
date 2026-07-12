<?php

namespace pocketmine\network\mcpe\protocol\types\inventory;

use pocketmine\item\Item;
use pocketmine\network\mcpe\NetworkBinaryStream;

final class CreativeItemEntry{
	public function __construct(
		private int $entryId,
		private Item $item,
		private int $groupId
	){
	}

	public function getEntryId() : int{ return $this->entryId; }

	public function getItem() : Item{ return $this->item; }

	public function getGroupId() : int{ return $this->groupId; }

	public static function read(NetworkBinaryStream $in) : self{
		$entryId = $in->getUnsignedVarInt();
		$item = $in->getItemStackWithoutStackId();
		$groupId = $in->getUnsignedVarInt();
		return new self($entryId, $item, $groupId);
	}

	public function write(NetworkBinaryStream $out) : void{
		$out->putUnsignedVarInt($this->entryId);
		$out->putItemStackWithoutStackId($this->item);
		$out->putUnsignedVarInt($this->groupId);
	}
}