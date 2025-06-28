<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;

class RequestNetworkSettingsPacket extends DataPacket{

	public const NETWORK_ID = ProtocolInfo::REQUEST_NETWORK_SETTINGS_PACKET;

	public int $protocol;

	protected function decodePayload(){
		$this->protocol = $this->getInt();
		$this->setProtocol($this->protocol);
	}

	protected function encodePayload(){
		$this->putInt($this->protocol);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleRequestNetworkSettings($this);
	}
}