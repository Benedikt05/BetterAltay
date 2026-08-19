<?php


declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;

class RecordStartedPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::RECORD_STARTED_PACKET;

	public int $x;
	public int $y;
	public int $z;
	public int $serverSoundHandle;

	protected function decodePayload() : void{
		$this->getBlockPosition($this->x, $this->y, $this->z);
		$this->serverSoundHandle = $this->getLLong();
	}

	protected function encodePayload() : void{
		$this->putBlockPosition($this->x, $this->y, $this->z);
		$this->putLLong($this->serverSoundHandle);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleRecordStarted($this);
	}
}
