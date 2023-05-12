<?php


declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;

class OpenSignPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::OPEN_SIGN_PACKET;

	public int $x;
	public int $y;
	public int $z;
	public bool $frontSide;

	protected function decodePayload(){
		$this->getBlockPosition($this->x, $this->y, $this->z);
		$this->frontSide = $this->getBool();
	}

	protected function encodePayload(){
		$this->putBlockPosition($this->x, $this->y, $this->z);
		$this->putBool($this->frontSide);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleOpenSign($this);
	}
}