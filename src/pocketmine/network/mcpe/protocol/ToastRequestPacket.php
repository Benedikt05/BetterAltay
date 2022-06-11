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

class ToastRequestPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::TOAST_REQUEST_PACKET;
	
	/** @var string  */
	public string $title = "";
	/** @var string  */
	public string $content = "";
	
	protected function decodePayload(){
		$this->title = $this->getString();
		$this->content = $this->getString();
	}
	
	protected function encodePayload(){
		$this->putString($this->title);
		$this->putString($this->content);
	}
	
	public function handle(NetworkSession $session) : bool{
		return $session->handleToastRequest($this);
	}
}