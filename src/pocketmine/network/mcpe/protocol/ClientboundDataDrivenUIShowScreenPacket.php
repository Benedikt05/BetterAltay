<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;

class ClientboundDataDrivenUIShowScreenPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_DATA_DRIVEN_UI_SHOW_SCREEN_PACKET;

	public string $screenId;

	protected function decodePayload() : void{
		$this->screenId = $this->getString();
	}

	protected function encodePayload() : void{
		$this->putString($this->screenId);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleClientboundDataDrivenUIShowScreen($this);
	}
}