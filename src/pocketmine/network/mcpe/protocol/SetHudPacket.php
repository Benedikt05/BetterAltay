<?php


declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;

class SetHudPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SET_HUD_PACKET;

	/** @var int[] */
	public array $hudElements;
	public int $visibility;

	protected function decodePayload() : void{
		for($index = 0; $index < $this->getUnsignedVarInt(); $index++){
			$this->hudElements[] = $this->getUnsignedVarInt();
		}

		$this->visibility = $this->getUnsignedVarInt();
	}

	protected function encodePayload() : void{
		$this->putUnsignedVarInt(count($this->hudElements));
		foreach($this->hudElements as $hudElement){
			$this->putUnsignedVarInt($hudElement);
		}
		$this->putUnsignedVarInt($this->visibility);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleSetHud($this);
	}
}