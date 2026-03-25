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
use UnexpectedValueException;

/**
 * @see ServerboundDataStorePacket&ClientboundDataStorePacket
 */
final class DataStoreUpdate extends DataStore{
	public const ID = DataStoreType::UPDATE;

	public function __construct(
		private string $name,
		private string $property,
		private string $path,
		private DataStoreValue $data,
		private int $updateCount,
		private int $pathUpdateCount,
	){
	}

	public function getTypeId() : int{
		return self::ID;
	}

	public function getName() : string{ return $this->name; }

	public function getProperty() : string{ return $this->property; }

	public function getPath() : string{ return $this->path; }

	public function getData() : DataStoreValue{ return $this->data; }

	public function getUpdateCount() : int{ return $this->updateCount; }

	public function getPathUpdateCount() : int{ return $this->pathUpdateCount; }

	public static function read(NetworkBinaryStream $in) : self{
		$name = $in->getString();
		$property = $in->getString();
		$path = $in->getString();

		$data = match ($in->getUnsignedVarInt()) {
			DataStoreValueType::DOUBLE => DoubleDataStoreValue::read($in),
			DataStoreValueType::BOOL => BoolDataStoreValue::read($in),
			DataStoreValueType::STRING => StringDataStoreValue::read($in),
			default => throw new UnexpectedValueException("Unknown DataStoreValueType"),
		};

		$updateCount = $in->getLInt();
		$pathUpdateCount = $in->getLInt();

		return new self(
			$name,
			$property,
			$path,
			$data,
			$updateCount,
			$pathUpdateCount,
		);
	}

	public function write(NetworkBinaryStream $out) : void{
		$out->putString($this->name);
		$out->putString($this->property);
		$out->putString($this->path);
		$out->putUnsignedVarInt($this->data->getTypeId());
		$this->data->write($out);
		$out->putLInt($this->updateCount);
		$out->putLInt($this->pathUpdateCount);
	}
}