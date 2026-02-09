<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;

class ClientboundTextureShiftPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_TEXTURE_SHIFT_PACKET;

	public const ACTION_INVALID = 0;
	public const ACTION_INITIALIZE = 1;
	public const ACTION_START = 2;
	public const ACTION_SET_ENABLED = 3;
	public const ACTION_SYNC = 4;


	public int $action;
	public string $collectionName;
	public string $fromStep;
	public string $toStep;
	/** @var string[] */
	public array $allSteps = [];
	public int $currentLengthTicks;
	public int $totalLengthTicks;
	public bool $enabled;

	protected function decodePayload() : void{
		$this->action = $this->getByte();
		$this->collectionName = $this->getString();
		$this->fromStep = $this->getString();
		$this->toStep = $this->getString();

		$count = $this->getUnsignedVarInt();
		for($i = 0; $i < $count; ++$i){
			$this->allSteps[] = $this->getString();
		}

		$this->currentLengthTicks = $this->getUnsignedVarLong();
		$this->totalLengthTicks = $this->getUnsignedVarLong();
		$this->enabled = $this->getBool();
	}

	protected function encodePayload() : void{
		$this->putByte($this->action);
		$this->putString($this->collectionName);
		$this->putString($this->fromStep);
		$this->putString($this->toStep);

		$this->putUnsignedVarInt(count($this->allSteps));
		foreach($this->allSteps as $step){
			$this->putString($step);
		}

		$this->putUnsignedVarLong($this->currentLengthTicks);
		$this->putUnsignedVarLong($this->totalLengthTicks);
		$this->putBool($this->enabled);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleClientboundTextureShift($this);
	}
}