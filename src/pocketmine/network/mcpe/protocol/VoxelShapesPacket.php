<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;

class VoxelShapesPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::VOXEL_SHAPES_PACKET;

	/*
	 * public array $shapes = [];
	 * public array $names = [];
	*/
	public int $customShapeCount = 0;

	protected function decodePayload() : void{
		//TODO
	}

	protected function encodePayload() : void{
		$this->putUnsignedVarInt(0); //shapes
		$this->putUnsignedVarInt(0); //names
		$this->putLShort($this->customShapeCount);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleVoxelShapes($this);
	}
}