<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\resourcepacks\ResourcePack;
use pocketmine\utils\UUID;
use function count;

class ResourcePacksInfoPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::RESOURCE_PACKS_INFO_PACKET;

	public bool $mustAccept = false; //if true, forces client to choose between accepting packs or being disconnected
	public bool $hasAddonPacks = false;
	public bool $hasScripts = false; //if true, causes disconnect for any platform that doesn't support scripts yet
	public bool $forceDisableVibrantVisuals = false;
	public ?UUID $worldTemplateUUID = null;
	public string $worldTemplateVersion = "";
	/** @var ResourcePack[] */
	public array $resourcePackEntries = [];

	protected function decodePayload() : void{
		$this->mustAccept = $this->getBool();
		$this->hasAddonPacks = $this->getBool();
		$this->hasScripts = $this->getBool();
		$this->forceDisableVibrantVisuals = $this->getBool();
		$this->worldTemplateUUID = $this->getUUID();
		$this->worldTemplateVersion = $this->getString();

		$resourcePackCount = $this->getLShort();
		while($resourcePackCount-- > 0){
			$this->getUUID();
			$this->getString();
			$this->getLLong();
			$this->getString();
			$this->getString();
			$this->getString();
			$this->getBool();
			$this->getBool();
			$this->getBool();
			$this->getString();
		}
	}

	protected function encodePayload() : void{
		$this->putBool($this->mustAccept);
		$this->putBool($this->hasAddonPacks);
		$this->putBool($this->hasScripts);
		$this->putBool($this->forceDisableVibrantVisuals);
		$this->putUUID($this->worldTemplateUUID ??= new UUID());
		$this->putString($this->worldTemplateVersion);

		$this->putLShort(count($this->resourcePackEntries));
		foreach($this->resourcePackEntries as $entry){
			$this->putUUID(UUID::fromString($entry->getPackId()));
			$this->putString($entry->getPackVersion());
			$this->putLLong($entry->getPackSize());
			$this->putString(""); //TODO: encryption key
			$this->putString(""); //TODO: subpack name
			$this->putString(""); //TODO: content identity
			$this->putBool(false); //TODO: has scripts (seems useless for resource packs)
			$this->putBool(false); //TODO: is addon pack
			$this->putBool(false); //TODO: supports RTX
			$this->putString(""); //TODO: CDNUrl
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleResourcePacksInfo($this);
	}
}
