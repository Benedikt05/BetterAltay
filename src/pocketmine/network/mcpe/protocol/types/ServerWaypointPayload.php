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

class ServerWaypointPayload {

	public function __construct(
		private int $updateFlag,
		private ?bool $isVisible,
		private ?WorldPosition $worldPosition,
		private ?int $textureId,
		private ?int $color,
		private ?bool $clientPositionAuthority
	){}

	public function getUpdateFlag() : int{
		return $this->updateFlag;
	}

	public function isVisible() : ?bool{
		return $this->isVisible;

	}
	public function getWorldPosition() : ?WorldPosition{
		return $this->worldPosition;
	}

	public function getTextureId() : ?int{
		return $this->textureId;
	}
	public function getColor() : ?int{
		return $this->color;
	}

	public function hasClientPositionAuthority() : ?bool{
		return $this->clientPositionAuthority;
	}

	public static function read(NetworkBinaryStream $in) : self{
		$updateFlag = $in->getLInt();
		$isVisible = $in->readOptional(fn() => $in->getBool());
		$worldPosition = $in->readOptional(fn() => WorldPosition::read($in));
		$textureId = $in->readOptional(fn() => $in->getLInt());
		$color = $in->readOptional(fn() => $in->getLInt());
		$clientPositionAuthority = $in->readOptional(fn() => $in->getBool());

		return new self(
			$updateFlag,
			$isVisible,
			$worldPosition,
			$textureId,
			$color,
			$clientPositionAuthority
		);
	}

	public function write(NetworkBinaryStream $out) : void{
		$out->putLInt($this->updateFlag);
		$out->writeOptional($this->isVisible, fn() => $out->putBool($this->isVisible));
		$out->writeOptional($this->worldPosition, fn() => $this->worldPosition->write($out));
		$out->writeOptional($this->textureId, fn() => $out->putLInt($this->textureId));
		$out->writeOptional($this->color, fn() => $out->putLInt($this->color));
		$out->writeOptional($this->clientPositionAuthority, fn() => $out->putBool($this->clientPositionAuthority));
	}
}