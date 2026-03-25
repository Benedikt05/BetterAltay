<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;

class ClientboundDataDrivenUICloseScreenPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_DATA_DRIVEN_UI_CLOSE_SCREEN_PACKET;

	public ?int $formId = null;

	/**
	 * @generate-create-func
	 */
	public static function create(?int $formId) : self{
		$result = new self;
		$result->formId = $formId;
		return $result;
	}

	protected function decodePayload() : void{
		$this->formId = $this->readOptional(fn() => $this->getLInt());
	}

	protected function encodePayload() : void{
		$this->writeOptional($this->formId, fn(int $formId) => $this->putLInt($formId));
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleClientboundDataDrivenUICloseScreen($this);
	}
}