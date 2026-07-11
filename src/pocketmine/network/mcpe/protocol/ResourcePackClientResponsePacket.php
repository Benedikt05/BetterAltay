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
use function count;

class ResourcePackClientResponsePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::RESOURCE_PACK_CLIENT_RESPONSE_PACKET;

	public const STATUS_REFUSED = 1;
	public const STATUS_SEND_PACKS = 2;
	public const STATUS_HAVE_ALL_PACKS = 3;
	public const STATUS_COMPLETED = 4;

	public int $status;
	/** @var string[] */
	public array $packIds = [];

	protected function decodePayload() : void{
		$this->status = $this->getUnsignedVarInt();
		$this->getString(); //status/response string
		if($this->status === self::STATUS_SEND_PACKS){
			$entryCount = $this->getUnsignedVarInt();
			$this->packIds = [];
			while($entryCount-- > 0){
				$this->packIds[] = $this->getString();
			}
		}
	}

	protected function encodePayload() : void{
		$this->putUnsignedVarInt($this->status);
		$this->putString("");
		if($this->status === self::STATUS_SEND_PACKS){
			$this->putUnsignedVarInt(count($this->packIds));
			foreach($this->packIds as $id){
				$this->putString($id);
			}
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleResourcePackClientResponse($this);
	}
}
