<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pocketmine\network\mcpe\NetworkSession;

class UpdateClientOptionsPacket extends DataPacket{

	public const NETWORK_ID = ProtocolInfo::UPDATE_CLIENT_OPTIONS_PACKET;

	public const GRAPHICS_MODE_SIMPLE = 0;
	public const GRAPHICS_MODE_FANCY = 1;
	public const GRAPHICS_MODE_ADVANCED = 2;
	public const GRAPHICS_MODE_RAY_TRACED = 3;

	private int $graphicsMode;

	/**
	 * @return int
	 */
	public function getGraphicsMode() : int{
		return $this->graphicsMode;
	}

	protected function decodePayload(){
		$this->graphicsMode = $this->getByte();
	}

	protected function encodePayload(){
		$this->putByte($this->graphicsMode);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleUpdateClientOptions($this);
	}
}