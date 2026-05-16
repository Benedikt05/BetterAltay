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

use pocketmine\math\Vector2;
use pocketmine\network\mcpe\NetworkBinaryStream;
//TODO: Translate this for older versions
class ServerWaypointPayload{

	public function __construct(
		private int $updateFlag,
		private ?bool $isVisible,
		private ?WorldPosition $worldPosition,
		private ?string $texturePath,
		private ?Vector2 $iconSize,
		private ?int $color,
		private ?bool $clientPositionAuthority,
		private ?int $entityUniqueId,
	){
	}

	public function getUpdateFlag() : int{
		return $this->updateFlag;
	}

	public function isVisible() : ?bool{
		return $this->isVisible;

	}

	public function getWorldPosition() : ?WorldPosition{
		return $this->worldPosition;
	}

	public function getTexturePath() : ?string{
		return $this->texturePath;
	}

	public function getIconSize() : ?Vector2{
		return $this->iconSize;
	}

	public function getColor() : ?int{
		return $this->color;
	}

	public function hasClientPositionAuthority() : ?bool{
		return $this->clientPositionAuthority;
	}

	public function getEntityUniqueId() : ?int{
		return $this->entityUniqueId;
	}

	public static function read(NetworkBinaryStream $in) : self{
		$updateFlag = $in->getLInt();
		$isVisible = $in->readOptional(fn() => $in->getBool());
		$worldPosition = $in->readOptional(fn() => WorldPosition::read($in));
		$texturePath = $in->readOptional(fn() => $in->getString());
		$iconSize = $in->readOptional(fn() => $in->getVector2());
		$color = $in->readOptional(fn() => $in->getLInt());
		$clientPositionAuthority = $in->readOptional(fn() => $in->getBool());
		$entityUniqueId = $in->readOptional(fn() => $in->getEntityUniqueId());

		return new self(
			$updateFlag,
			$isVisible,
			$worldPosition,
			$texturePath,
			$iconSize,
			$color,
			$clientPositionAuthority,
			$entityUniqueId
		);
	}

	public function write(NetworkBinaryStream $out) : void{
		$out->putLInt($this->updateFlag);
		$out->writeOptional($this->isVisible, fn() => $out->putBool($this->isVisible));
		$out->writeOptional($this->worldPosition, fn() => $this->worldPosition->write($out));
		$out->writeOptional($this->texturePath, fn() => $out->putString($this->texturePath));
		$out->writeOptional($this->iconSize, fn() => $out->putVector2($this->iconSize));
		$out->writeOptional($this->color, fn() => $out->putLInt($this->color));
		$out->writeOptional($this->clientPositionAuthority, fn() => $out->putBool($this->clientPositionAuthority));
		$out->writeOptional($this->entityUniqueId, fn() => $out->putEntityUniqueId($this->entityUniqueId));
	}
}