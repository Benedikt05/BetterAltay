<?php


declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;

class SetPlayerInventoryOptionsPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SET_PLAYER_INVENTORY_OPTIONS_PACKET;

	public int $leftTab;
	public int $rightTab;
	public bool $filtering;
	public int $inventoryLayout;
	public int $craftingLayout;

	protected function decodePayload() : void{
		$this->leftTab = $this->getVarInt();
		$this->rightTab = $this->getVarInt();
		$this->filtering = $this->getBool();
		$this->inventoryLayout = $this->getVarInt();
		$this->craftingLayout = $this->getVarInt();
	}

	protected function encodePayload() : void{
		$this->putVarInt($this->leftTab);
		$this->putVarInt($this->rightTab);
		$this->putBool($this->filtering);
		$this->putVarInt($this->inventoryLayout);
		$this->putVarInt($this->craftingLayout);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleSetPlayerInventoryOptions($this);
	}
}
