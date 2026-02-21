<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pocketmine\network\mcpe\NetworkSession;

class VoxelShapesPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::VOXEL_SHAPES_PACKET;

	protected function decodePayload() : void{
		//TODO
	}

	protected function encodePayload() : void{
		//TODO
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleVoxelShapes($this);
	}
}