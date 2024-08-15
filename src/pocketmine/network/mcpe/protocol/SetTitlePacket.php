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

class SetTitlePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SET_TITLE_PACKET;

	public const TYPE_CLEAR_TITLE = 0;
	public const TYPE_RESET_TITLE = 1;
	public const TYPE_SET_TITLE = 2;
	public const TYPE_SET_SUBTITLE = 3;
	public const TYPE_SET_ACTIONBAR_MESSAGE = 4;
	public const TYPE_SET_ANIMATION_TIMES = 5;
	public const TYPE_SET_TITLE_JSON = 6;
	public const TYPE_SET_SUBTITLE_JSON = 7;
	public const TYPE_SET_ACTIONBAR_MESSAGE_JSON = 8;

	public int $type;
	public string $text = "";
	public int $fadeInTime = 0;
	public int $stayTime = 0;
	public int $fadeOutTime = 0;
	public string $xuid = "";
	public string $platformOnlineId = "";
	public string $filteredTitleText = "";

	protected function decodePayload() : void{
		$this->type = $this->getVarInt();
		$this->text = $this->getString();
		$this->fadeInTime = $this->getVarInt();
		$this->stayTime = $this->getVarInt();
		$this->fadeOutTime = $this->getVarInt();
		$this->xuid = $this->getString();
		$this->platformOnlineId = $this->getString();
		$this->filteredTitleText = $this->getString();
	}

	protected function encodePayload() : void{
		$this->putVarInt($this->type);
		$this->putString($this->text);
		$this->putVarInt($this->fadeInTime);
		$this->putVarInt($this->stayTime);
		$this->putVarInt($this->fadeOutTime);
		$this->putString($this->xuid);
		$this->putString($this->platformOnlineId);
		$this->putString($this->filteredTitleText);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleSetTitle($this);
	}
}
