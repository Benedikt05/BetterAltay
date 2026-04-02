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
use pocketmine\network\mcpe\protocol\types\LocatorBarWaypointPayload;

class LocatorBarPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::LOCATOR_BAR_PACKET;

	/** @var LocatorBarWaypointPayload[] */
	protected array $waypoints = [];


	public function getWaypoints() : array{
		return $this->waypoints;
	}

	/**
	 * @param LocatorBarWaypointPayload[] $waypoints
	 */
	public static function create(array $waypoints) : self{
		$result = new self();
		$result->waypoints = $waypoints;
		return $result;
	}

	protected function decodePayload() : void{
		$length = $this->getUnsignedVarInt();
		for($i = 0; $i < $length; ++$i){
			$this->waypoints[] = LocatorBarWaypointPayload::read($this);
		}
	}

	protected function encodePayload() : void{
		$this->putUnsignedVarInt(count($this->waypoints));
		foreach($this->waypoints as $waypoint){
			$waypoint->write($this);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleLocatorBar($this);
	}
}