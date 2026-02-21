<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;

class PartyChangedPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::PARTY_CHANGED_PACKET;

	public string $partyId;

	protected function decodePayload() : void{
		$this->partyId = $this->getString();
	}

	protected function encodePayload() : void{
		$this->putString($this->partyId);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePartyChanged($this);
	}
}