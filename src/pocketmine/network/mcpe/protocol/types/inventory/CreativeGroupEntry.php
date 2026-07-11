<?php


declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types\inventory;

use pocketmine\item\Item;
use pocketmine\network\mcpe\NetworkBinaryStream;

final class CreativeGroupEntry{
	public function __construct(
		private int $categoryId,
		private string $categoryName,
		private ItemStackWrapper $icon
	){
	}

	public function getCategoryId() : int{ return $this->categoryId; }

	public function getCategoryName() : string{ return $this->categoryName; }

	public function getIcon() : Item{ return $this->icon->getItemStack(); }

	public static function read(NetworkBinaryStream $in) : self{
		$categoryId = $in->getByte();
		$categoryName = $in->getString();
		$icon = ItemStackWrapper::read($in, true);
		return new self($categoryId, $categoryName, $icon);
	}

	public function write(NetworkBinaryStream $out) : void{
		$out->putByte($this->categoryId);
		$out->putString($this->categoryName);
		$this->icon->write($out, true);
	}
}