<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;

class UpdateAdventureSettingsPacket extends DataPacket{

	public const NETWORK_ID = ProtocolInfo::UPDATE_ADVENTURE_SETTINGS_PACKET;

	public bool $noAttackingMobs;
	public bool $noAttackingPlayers;
	public bool $worldImmutable;
	public bool $showNameTags;
	public bool $autoJump;

	protected function decodePayload(){
		$this->noAttackingMobs = $this->getBool();
		$this->noAttackingPlayers = $this->getBool();
		$this->worldImmutable = $this->getBool();
		$this->showNameTags = $this->getBool();
		$this->autoJump = $this->getBool();
	}

	protected function encodePayload(){
		$this->putBool($this->noAttackingMobs);
		$this->putBool($this->noAttackingPlayers);
		$this->putBool($this->worldImmutable);
		$this->putBool($this->showNameTags);
		$this->putBool($this->autoJump);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleUpdateAdventureSettings($this);
	}
}