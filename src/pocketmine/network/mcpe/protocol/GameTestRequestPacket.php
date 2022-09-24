<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;

class GameTestRequestPacket extends DataPacket{

	public const NETWORK_ID = ProtocolInfo::GAME_TEST_REQUEST_PACKET;

	public int $maxTestsPerBatch;
	public int $repeatCount;
	public int $rotation;
	public bool $stopOnFailure;
	public int $x;
	public int $y;
	public int $z;
	public int $testsPerRow;
	public string $testName;

	protected function decodePayload(){
		$this->maxTestsPerBatch = $this->getVarInt();
		$this->repeatCount = $this->getVarInt();
		$this->rotation = $this->getByte();
		$this->stopOnFailure = $this->getBool();
		$this->getSignedBlockPosition($this->x, $this->y, $this->z);
		$this->testsPerRow = $this->getVarInt();
		$this->testName = $this->getString();
	}

	protected function encodePayload(){
		$this->putVarInt($this->maxTestsPerBatch);
		$this->putVarInt($this->repeatCount);
		$this->putByte($this->rotation);
		$this->putBool($this->stopOnFailure);
		$this->putSignedBlockPosition($this->x, $this->y, $this->z);
		$this->putVarInt($this->testsPerRow);
		$this->putString($this->testName);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleGameTestRequest($this);
	}
}