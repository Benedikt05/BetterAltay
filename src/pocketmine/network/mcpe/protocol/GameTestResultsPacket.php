<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;

class GameTestResultsPacket extends DataPacket{

	public const NETWORK_ID = ProtocolInfo::GAME_TEST_RESULTS_PACKET;

	public bool $success;
	public string $error;
	public string $testName;

	protected function decodePayload(){
		$this->success = $this->getBool();
		$this->error = $this->getString();
		$this->testName = $this->getString();
	}

	protected function encodePayload(){
		$this->putBool($this->success);
		$this->putString($this->error);
		$this->putString($this->testName);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleGameTestResult($this);
	}
}