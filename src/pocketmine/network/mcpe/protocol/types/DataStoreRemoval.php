<?php

/*
 * This file is part of BedrockProtocol.
 * Copyright (C) 2014-2022 PocketMine Team <https://github.com/pmmp/BedrockProtocol>
 *
 * BedrockProtocol is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

use pocketmine\network\mcpe\NetworkBinaryStream;

/**
 * @see ClientboundDataStorePacket
 */
final class DataStoreRemoval extends DataStore{
	public const ID = DataStoreType::REMOVAL;

	public function __construct(
		private string $name,
	){}

	public function getTypeId() : int{
		return self::ID;
	}

	public function getName() : string{ return $this->name; }

	public static function read(NetworkBinaryStream $in) : self{
		$name = $in->getString();

		return new self(
			$name,
		);
	}

	public function write(NetworkBinaryStream $out) : void{
		$out->putString($this->name);
	}
}