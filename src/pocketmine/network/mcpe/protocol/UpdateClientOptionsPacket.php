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

	private ?int $graphicsMode = null;
	private ?bool $filterProfanityChange = null;

	/**
	 * @return int|null
	 */
	public function getGraphicsMode() : ?int{
		return $this->graphicsMode;
	}

	public function getFilterProfanityChange() : ?bool{
		return $this->filterProfanityChange;
	}

	protected function decodePayload() : void{
		$this->graphicsMode = $this->getBool() ? $this->getByte() : null;
		$this->filterProfanityChange = $this->readOptional(fn() => $this->getBool());
	}

	protected function encodePayload() : void{
		$this->putBool($this->graphicsMode !== null);
		if($this->graphicsMode !== null){
			$this->putByte($this->graphicsMode);
		}
		$this->writeOptional($this->filterProfanityChange, fn(bool $filterProfanityChange) => $this->putBool($filterProfanityChange));
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleUpdateClientOptions($this);
	}
}