<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkSession;

class PlayerLocationPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_LOCATION_PACKET;

	public const PLAYER_LOCATION_COORDINATES = 0;
	public const PLAYER_LOCATION_HIDE = 1;

	public int $packetType;
	public int $entityUniqueId;
	public Vector3 $position;

	protected function decodePayload() : void{
		$this->packetType = $this->getInt();
		$this->entityUniqueId = $this->getEntityUniqueId();
		if($this->packetType === PlayerLocationPacket::PLAYER_LOCATION_COORDINATES){
			$this->position = $this->getVector3();
		}
	}

	protected function encodePayload() : void{
		$this->putInt($this->packetType);
		$this->putEntityUniqueId($this->entityUniqueId);
		if($this->packetType === PlayerLocationPacket::PLAYER_LOCATION_COORDINATES){
			$this->putVector3($this->position);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePlayerLocation($this);
	}
}