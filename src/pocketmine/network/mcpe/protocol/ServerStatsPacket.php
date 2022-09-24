<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;

class ServerStatsPacket extends DataPacket{

	public const NETWORK_ID = ProtocolInfo::SERVER_STATS_PACKET;

	public float $serverTime;
	public float $networkTime;

	protected function decodePayload(){
		$this->serverTime = $this->getLFloat();
		$this->networkTime = $this->getLFloat();
	}


	protected function encodePayload(){
		$this->putLFloat($this->serverTime);
		$this->putLFloat($this->networkTime);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleServerStats($this);
	}
}