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

namespace pocketmine\network\mcpe\protocol\types\inventory;

use pocketmine\network\mcpe\NetworkBinaryStream;

final class FullContainerName{
	public function __construct(
		private int $containerId,
		private int $dynamicId = 0
	){}

	public function getContainerId() : int{ return $this->containerId; }

	public function getDynamicId() : int{ return $this->dynamicId; }

	public static function read(NetworkBinaryStream $in) : self{
		$containerId = $in->getByte();
		$dynamicId = $in->getLInt();
		return new self($containerId, $dynamicId);
	}

	public function write(NetworkBinaryStream $out) : void{
		$out->putByte($this->containerId);
		$out->putLInt($this->dynamicId);
	}
}
