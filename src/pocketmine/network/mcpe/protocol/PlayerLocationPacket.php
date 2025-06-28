<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use LogicException;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkSession;

class PlayerLocationPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_LOCATION_PACKET;

	public const PLAYER_LOCATION_COORDINATES = 0;
	public const PLAYER_LOCATION_HIDE = 1;

	public int $packetType;
	public int $entityUniqueId;
	public ?Vector3 $position;

	protected function decodePayload() : void{
		$this->packetType = $this->getLInt();
		$this->entityUniqueId = $this->getEntityUniqueId();
		if($this->packetType === PlayerLocationPacket::PLAYER_LOCATION_COORDINATES){
			$this->position = $this->getVector3();
		}
	}

	protected function encodePayload() : void{
		$this->putLInt($this->packetType);
		$this->putEntityUniqueId($this->entityUniqueId);
		if($this->packetType === PlayerLocationPacket::PLAYER_LOCATION_COORDINATES){
			if($this->position === null){
				throw new LogicException("PlayerLocationPacket with type PLAYER_LOCATION_COORDINATES requires a position to be set");
			}
			$this->putVector3($this->position);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePlayerLocation($this);
	}
}