<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;

class ClientboundDataDrivenUIShowScreenPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_DATA_DRIVEN_UI_SHOW_SCREEN_PACKET;

	public string $screenId;
	public int $formId;
	public ?int $dataInstanceId = null;

	/**
	 * @generate-create-func
	 */
	public static function create(string $screenId, int $formId, ?int $dataInstanceId = null) : self{
		$result = new self;
		$result->screenId = $screenId;
		$result->formId = $formId;
		$result->dataInstanceId = $dataInstanceId;
		return $result;
	}

	protected function decodePayload() : void{
		$this->screenId = $this->getString();
		$this->formId = $this->getLInt();
		$this->dataInstanceId = $this->readOptional(fn() => $this->getLInt());
	}

	protected function encodePayload() : void{
		$this->putString($this->screenId);
		$this->putLInt($this->formId);
		$this->writeOptional($this->dataInstanceId, fn(int $dataInstanceId) => $this->putLInt($dataInstanceId));
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleClientboundDataDrivenUIShowScreen($this);
	}
}