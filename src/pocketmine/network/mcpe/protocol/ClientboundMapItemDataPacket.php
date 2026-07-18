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
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\network\mcpe\protocol\types\MapDecoration;
use pocketmine\network\mcpe\protocol\types\MapTrackedObject;
use pocketmine\utils\Color;
use function count;

class ClientboundMapItemDataPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_MAP_ITEM_DATA_PACKET;

	public int $mapId;
	public int $dimensionId = DimensionIds::OVERWORLD;
	public bool $isLocked = false;
	public int $x = 0;
	public int $y = 0;
	public int $z = 0;

	/** @var int[]|null */
	public ?array $eids = null;
	public ?int $scale = null;

	/** @var MapTrackedObject[]|null */
	public ?array $trackedEntities = null;
	/** @var MapDecoration[]|null */
	public ?array $decorations = null;

	public ?int $width = null;
	public ?int $height = null;
	public ?int $xOffset = null;
	public ?int $yOffset = null;
	/** @var Color[][]|null */
	public ?array $colors = null;

	protected function decodePayload() : void{
		$this->mapId = $this->getEntityUniqueId();
		$this->dimensionId = $this->getByte();
		$this->isLocked = $this->getBool();
		$this->getBlockPosition($this->x, $this->y, $this->z);
		$this->eids = $this->readOptional(fn() => $this->readCreationMapIds());
		$this->scale = $this->readOptional(fn() => $this->getByte());
		$this->trackedEntities = $this->readOptional(fn() => $this->readTrackedEntities());
		$this->decorations = $this->readOptional(fn() => $this->readDecorations());
		$this->width = $this->readOptional(fn() => $this->getVarInt());
		$this->height = $this->readOptional(fn() => $this->getVarInt());
		$this->xOffset = $this->readOptional(fn() => $this->getVarInt());
		$this->yOffset = $this->readOptional(fn() => $this->getVarInt());
		$this->colors = $this->readOptional(fn() => $this->readPixels());
	}

	/**
	 * @return int[]
	 */
	private function readCreationMapIds() : array{
		$count = $this->getUnsignedVarInt();
		$creationMapIds = [];
		for($i = 0; $i < $count; ++$i){
			$creationMapIds[] = $this->getEntityUniqueId();
		}
		return $creationMapIds;
	}

	/**
	 * @return MapTrackedObject[]
	 */
	private function readTrackedEntities() : array{
		$count = $this->getUnsignedVarInt();
		$entities = [];
		for($i = 0; $i < $count; ++$i){
			$entities[] = MapTrackedObject::read($this);
		}
		return $entities;
	}

	/**
	 * @return MapDecoration[]
	 */
	private function readDecorations() : array{
		$count = $this->getUnsignedVarInt();
		$decorations = [];
		for($i = 0; $i < $count; ++$i){
			$icon = $this->getByte();
			$rotation = $this->getByte();
			$xOffset = $this->getByte();
			$yOffset = $this->getByte();
			$label = $this->getString();
			$color = Color::fromABGR($this->getLInt());

			$decorations[] = new MapDecoration($icon, $rotation, $xOffset, $yOffset, $label, $color);
		}
		return $decorations;
	}

	/**
	 * @return Color[][]
	 */
	private function readPixels() : array{
		$this->getUnsignedVarInt();

		$colors = [];
		for($y = 0; $y < $this->height; ++$y){
			for($x = 0; $x < $this->width; ++$x){
				$colors[$y][$x] = Color::fromABGR($this->getLInt());
			}
		}
		return $colors;
	}

	private function writeCreationMapIds(array $creationMapIds): void{
		$this->putUnsignedVarInt(count($creationMapIds));
		foreach($creationMapIds as $creationMapId){
			$this->putEntityUniqueId($creationMapId);
		}
	}

	private function writeTrackedEntities(array $entities): void{
		$this->putUnsignedVarInt(count($entities));
		/** @var MapTrackedObject[] $entities */
		foreach($entities as $trackedEntity){
			$trackedEntity->write($this);
		}
	}

	private function writeDecorations(array $decorations): void{
		$this->putUnsignedVarInt(count($decorations));
		/** @var MapDecoration[] $decorations */
		foreach($decorations as $decoration){
			$this->putByte($decoration->getIcon());
			$this->putByte($decoration->getRotation());
			$this->putByte($decoration->getXOffset());
			$this->putByte($decoration->getYOffset());
			$this->putString($decoration->getLabel());
			$this->putLInt($decoration->getColor()->toABGR());
		}
	}

	private function writePixels(): void{
		$this->putUnsignedVarInt($this->width * $this->height); //list count, but we handle it as a 2D array... thanks for the confusion mojang

		for($y = 0; $y < $this->height; ++$y){
			for($x = 0; $x < $this->width; ++$x){
				$this->putLInt($this->colors[$y][$x]->toABGR());
			}
		}
	}

	protected function encodePayload() : void{
		$this->putEntityUniqueId($this->mapId);
		$this->putByte($this->dimensionId);
		$this->putBool($this->isLocked);
		$this->putBlockPosition($this->x, $this->y, $this->z); //Origin
		$this->writeOptional($this->eids, fn($creationMapIds) => $this->writeCreationMapIds($creationMapIds));
		$this->writeOptional($this->scale, fn($scale) => $this->putByte($scale));
		$this->writeOptional($this->trackedEntities, fn($entities) => $this->writeTrackedEntities($entities));
		$this->writeOptional($this->decorations, fn($decorations) => $this->writeDecorations($decorations));
		$this->writeOptional($this->width, fn($val) => $this->putVarInt($val));
		$this->writeOptional($this->height, fn($val) => $this->putVarInt($val));
		$this->writeOptional($this->xOffset, fn($val) => $this->putVarInt($val));
		$this->writeOptional($this->yOffset, fn($val) => $this->putVarInt($val));
		$this->writeOptional($this->colors, fn($pixels) => $this->writePixels());
	}

	/**
	 * Crops the texture to wanted size
	 *
	 * @param int $minX
	 * @param int $minY
	 * @param int $maxX
	 * @param int $maxY
	 */
	public function cropTexture(int $minX, int $minY, int $maxX, int $maxY) : void{
		$this->height = $maxY;
		$this->width = $maxX;
		$this->xOffset = $minX;
		$this->yOffset = $minY;
		$newColors = [];
		for($y = 0; $y < $maxY; $y++){
			for($x = 0; $x < $maxX; $x++){
				$newColors[$y][$x] = $this->colors[$minY + $y][$minX + $x];
			}
		}
		$this->colors = $newColors;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleClientboundMapItemData($this);
	}
}
