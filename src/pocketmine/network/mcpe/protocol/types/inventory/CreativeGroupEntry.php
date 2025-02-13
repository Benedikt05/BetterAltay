<?php


declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types\inventory;

use pocketmine\item\Item;
use pocketmine\network\mcpe\NetworkBinaryStream;

final class CreativeGroupEntry{
	public function __construct(
		private int $categoryId,
		private string $categoryName,
		private Item $icon
	){
	}

	public function getCategoryId() : int{ return $this->categoryId; }

	public function getCategoryName() : string{ return $this->categoryName; }

	public function getIcon() : Item{ return $this->icon; }

	public static function read(NetworkBinaryStream $in) : self{
		$categoryId = $in->getLInt();
		$categoryName = $in->getString();
		$icon = $in->getItemStackWithoutStackId();
		return new self($categoryId, $categoryName, $icon);
	}

	public function write(NetworkBinaryStream $out) : void{
		$out->putLInt($this->categoryId);
		$out->putString($this->categoryName);
		$out->putItemStackWithoutStackId($this->icon);
	}
}