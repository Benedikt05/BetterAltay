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
use pocketmine\network\mcpe\protocol\types\DataStoreUpdate;

class ServerboundDataStorePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SERVERBOUND_DATA_STORE_PACKET;

	private DataStoreUpdate $update;

	/**
	 * @generate-create-func
	 */
	public static function create(DataStoreUpdate $update) : self{
		$result = new self;
		$result->update = $update;
		return $result;
	}

	public function getUpdate() : DataStoreUpdate{ return $this->update; }

	protected function decodePayload() : void{
		$this->update = DataStoreUpdate::read($this);
	}

	protected function encodePayload() : void{
		$this->update->write($this);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleServerboundDataStore($this);
	}
}