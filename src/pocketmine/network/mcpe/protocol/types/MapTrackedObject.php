<?php

/*
 *               _ _
 *         /\   | | |
 *        /  \  | | |_ __ _ _   _
 *       / /\ \ | | __/ _` | | | |
 *      / ____ \| | || (_| | |_| |
 *     /_/    \_|_|\__\__,_|\__, |
 *                           __/ |
 *                          |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author TuranicTeam
 * @link https://github.com/TuranicTeam/Altay
 *
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

use pocketmine\level\Position;
use pocketmine\network\mcpe\NetworkBinaryStream;

class MapTrackedObject{
	public const TYPE_ENTITY = 0;
	public const TYPE_BLOCK_ENTITY = 1;
	public const TYPE_OTHER = 2;

	public int $type;
	public ?int $entityUniqueId = null;
	public ?int $x = null;
	public ?int $y = null;
	public ?int $z = null;

	public static function read(NetworkBinaryStream $in) : self{
		$result = new self;
		$result->type = $in->getLInt();
		$result->entityUniqueId = $in->readOptional(fn() => $in->getEntityUniqueId());
		$in->readOptional(function() use ($in, $result) : void{
			$in->getBlockPosition($result->x, $result->y, $result->z);
		});

		return $result;
	}

	public function write(NetworkBinaryStream $out) : void{
		$out->putLInt($this->type);
		$out->writeOptional($this->entityUniqueId, fn($entityUniqueId) => $out->putEntityUniqueId($entityUniqueId));
		$position = ($this->x !== null && $this->y !== null && $this->z !== null) ? new Position($this->x, $this->y, $this->z) : null;
		$out->writeOptional($position, fn($pos) => $out->putBlockPosition($pos->x, $pos->y, $pos->z));
	}

}