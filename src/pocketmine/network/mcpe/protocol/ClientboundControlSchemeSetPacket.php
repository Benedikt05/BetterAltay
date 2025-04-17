<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;

class ClientboundControlSchemeSetPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_CONTROL_SCHEME_SET_PACKET;

	public const MOVEMENT_MODE_LOCKED_PLAYER_RELATIVE_STRAFE = 0;
	public const MOVEMENT_MODE_CAMERA_RELATIVE = 1;
	public const MOVEMENT_MODE_CAMERA_RELATIVE_STRAFE = 2;
	public const MOVEMENT_MODE_PLAYER_RELATIVE = 3;
	public const MOVEMENT_MODE_PLAYER_RELATIVE_STRAFE = 4;

	public int $controlScheme;

	protected function decodePayload() : void{
		$this->controlScheme = $this->getByte();
	}

	protected function encodePayload() : void{
		$this->putByte($this->controlScheme);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleClientboundControlSchemeSet($this);
	}
}