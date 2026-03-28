<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;

class PartyChangedPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::PARTY_CHANGED_PACKET;

	public ?string $partyId = null;

	protected function decodePayload() : void{
		$this->partyId = $this->readOptional(fn() => $this->getString());
	}

	protected function encodePayload() : void{
		$this->writeOptional($this->partyId, fn(string $partyId) => $this->putString($partyId));
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePartyChanged($this);
	}
}