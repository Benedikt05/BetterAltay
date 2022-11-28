<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkSession;

class UpdateClientInputLocksPacket extends DataPacket{

	public const NETWORK_ID = ProtocolInfo::UPDATE_CLIENT_INPUT_LOCKS_PACKET;

	public int $lockComponentData;
	private Vector3 $serverPosition;

	protected function decodePayload(){
		$this->lockComponentData = $this->getUnsignedVarInt();
		$this->serverPosition = $this->getVector3();
	}

	protected function encodePayload(){
		$this->putUnsignedVarInt($this->lockComponentData);
		$this->putVector3($this->serverPosition);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleUpdateClientInputLocks($this);
	}
}