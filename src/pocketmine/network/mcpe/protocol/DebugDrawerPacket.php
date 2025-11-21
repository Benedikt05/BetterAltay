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
use pocketmine\network\mcpe\protocol\types\PacketShapeData;
use function count;

class DebugDrawerPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::DEBUG_DRAWER_PACKET;

	/**
	 * @var PacketShapeData[]
	 * @phpstan-var list<PacketShapeData>
	 */
	private array $shapes;

	/**
	 * @generate-create-func
	 * @param PacketShapeData[] $shapes
	 * @phpstan-param list<PacketShapeData> $shapes
	 */
	public static function create(array $shapes) : self{
		$result = new self;
		$result->shapes = $shapes;
		return $result;
	}

	/**
	 * @return PacketShapeData[]
	 * @phpstan-return list<PacketShapeData>
	 */
	public function getShapes() : array{ return $this->shapes; }

	protected function decodePayload() : void{
		$this->shapes = [];
		for($i = 0, $len = $this->getUnsignedVarInt(); $i < $len; ++$i){
			$this->shapes[] = PacketShapeData::read($this);
		}
	}

	protected function encodePayload() : void{
		$this->putUnsignedVarInt(count($this->shapes));
		foreach($this->shapes as $shape){
			$shape->write($this);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleDebugDrawer($this);
	}
}
