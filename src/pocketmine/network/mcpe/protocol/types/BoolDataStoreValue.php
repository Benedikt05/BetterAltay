<?php

namespace pocketmine\network\mcpe\protocol\types;

use pocketmine\network\mcpe\NetworkBinaryStream;

final class BoolDataStoreValue extends DataStoreValue{
	public const ID = DataStoreValueType::BOOL;

	public function __construct(
		private bool $value
	){}

	public function getValue() : bool{ return $this->value; }

	public function getTypeId() : int{
		return self::ID;
	}

	public function write(NetworkBinaryStream $out) : void{
		$out->putBool($this->value);
	}

	public static function read(NetworkBinaryStream $in) : self{
		return new self($in->getBool());
	}
}