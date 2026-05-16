<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;

class PartyChangedPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::PARTY_CHANGED_PACKET;

	public ?string $partyId = null;
	public bool $partyLeader;

	protected function decodePayload() : void{
		$this->partyId = $this->readOptional(fn() => $this->getString());
		if($this->protocol >= ProtocolInfo::P_1_26_20){
			$this->partyLeader = $this->getBool();
		}
	}

	protected function encodePayload() : void{
		$this->writeOptional($this->partyId, fn(string $partyId) => $this->putString($partyId));
		if($this->protocol >= ProtocolInfo::P_1_26_20){
			$this->putBool($this->partyLeader);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePartyChanged($this);
	}
}