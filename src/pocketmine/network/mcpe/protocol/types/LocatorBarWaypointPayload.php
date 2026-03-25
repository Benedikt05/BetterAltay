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

namespace pocketmine\network\mcpe\protocol\types;

use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\utils\UUID;

class LocatorBarWaypointPayload{

	public const ACTION_NONE = 0;
	public const ACTION_ADD = 1;
	public const ACTION_REMOVE = 2;
	public const ACTION_UPDATE = 3;

	public function __construct(
		private UUID $group,
		private ServerWaypointPayload $waypoint,
		private int $actionFlag
	){
	}

	public function getGroup() : UUID{
		return $this->group;
	}

	public function getWaypoint() : ServerWaypointPayload{
		return $this->waypoint;
	}

	public function getActionFlag() : int{
		return $this->actionFlag;
	}

	public static function read(NetworkBinaryStream $in) : self{
		$group = $in->getUUID();
		$waypoint = ServerWaypointPayload::read($in);
		$actionFlag = $in->getUnsignedVarInt();

		return new self($group, $waypoint, $actionFlag);
	}

	public function write(NetworkBinaryStream $out) : void{
		$out->putUUID($this->group);
		$this->waypoint->write($out);
		$out->putUnsignedVarInt($this->actionFlag);
	}
}