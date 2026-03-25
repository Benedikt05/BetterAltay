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

namespace pocketmine\network\mcpe\protocol;

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\DataStore;
use pocketmine\network\mcpe\protocol\types\DataStoreChange;
use pocketmine\network\mcpe\protocol\types\DataStoreRemoval;
use pocketmine\network\mcpe\protocol\types\DataStoreType;
use pocketmine\network\mcpe\protocol\types\DataStoreUpdate;
use function count;

class ClientboundDataStorePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_DATA_STORE_PACKET;

	/**
	 * @var DataStore[]
	 * @phpstan-var list<DataStore>
	 */
	public array $values = [];

	/**
	 * @generate-create-func
	 * @param DataStore[] $values
	 * @phpstan-param list<DataStore> $values
	 */
	public static function create(array $values) : self{
		$result = new self;
		$result->values = $values;
		return $result;
	}

	protected function decodePayload() : void{
		$this->values = [];
		for($i = 0, $len = $this->getUnsignedVarInt(); $i < $len; ++$i){
			$this->values[] = match($this->getUnsignedVarInt()){
				DataStoreType::UPDATE => DataStoreUpdate::read($this),
				DataStoreType::CHANGE => DataStoreChange::read($this),
				DataStoreType::REMOVAL => DataStoreRemoval::read($this),
				default => throw new \UnexpectedValueException("Unknown DataStore type"),
			};
		}
	}

	protected function encodePayload() : void{
		$this->putUnsignedVarInt(count($this->values));
		foreach($this->values as $value){
			$this->putUnsignedVarInt($value->getTypeId());
			$value->write($this);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleClientboundDataStore($this);
	}
}