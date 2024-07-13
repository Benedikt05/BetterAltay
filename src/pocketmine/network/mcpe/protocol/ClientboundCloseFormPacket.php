<?php


declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;

class ClientboundCloseFormPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_CLOSE_FORM_PACKET;

	/**
	 * @generate-create-func
	 */
	public static function create() : self{
		return new self;
	}

	protected function decodePayload() : void{
		//No payload
	}

	protected function encodePayload() : void{
		//No payload
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleClientboundCloseForm($this);
	}
}