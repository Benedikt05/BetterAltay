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

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkBinaryStream;

class WorldPosition {

	public const OVERWORLD = 0;
	public const NETHER = 1;
	public const THE_END = 2;
	public const UNDEFINED = 3;

	public function __construct(
		private Vector3 $position,
		private int $dimensionType
	) {}

	public function getPosition() : Vector3 {
		return $this->position;
	}

	public function getDimensionType() : int {
		return $this->dimensionType;
	}

	public static function read(NetworkBinaryStream $in) : self{
		$position = $in->getVector3();
		$dimensionType = $in->getVarInt();

		return new self($position, $dimensionType);
	}

	public function write(NetworkBinaryStream $out) : void {
		$out->putVector3($this->position);
		$out->putVarInt($this->dimensionType);
	}
}