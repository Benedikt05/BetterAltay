<?php

namespace pocketmine\network\mcpe\protocol\types;

use pocketmine\network\mcpe\NetworkBinaryStream;

final class StringDataStoreValue extends DataStoreValue{
	public const ID = DataStoreValueType::STRING;

	public function __construct(
		private string $value
	){}

	public function getValue() : string{ return $this->value; }

	public function getTypeId() : int{
		return self::ID;
	}

	public function write(NetworkBinaryStream $out) : void{
		$out->putString($this->value);
	}

	public static function read(NetworkBinaryStream $in) : self{
		return new self($in->getString());
	}
}