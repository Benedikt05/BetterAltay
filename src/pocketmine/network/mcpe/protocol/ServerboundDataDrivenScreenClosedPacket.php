<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\DataDrivenScreenClosedReason;

class ServerboundDataDrivenScreenClosedPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SERVERBOUND_DATA_DRIVEN_SCREEN_CLOSED_PACKET;

	public int $formId;
	/** @see DataDrivenScreenClosedReason */
	public string $closeReason;

	/**
	 * @generate-create-func
	 */
	public static function create(int $formId, string $closeReason) : self{
		$result = new self;
		$result->formId = $formId;
		$result->closeReason = $closeReason;
		return $result;
	}

	protected function decodePayload() : void{
		$this->formId = $this->readOptional(fn() => $this->getLInt());
		$this->closeReason = $this->getString();
	}

	protected function encodePayload() : void{
		$this->writeOptional($this->formId, fn(int $formId) => $this->putLInt($formId));
		$this->putString($this->closeReason);

	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleServerboundDataDrivenScreenClosed($this);
	}
}