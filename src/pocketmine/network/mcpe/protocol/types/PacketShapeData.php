<?php

/*
 * This file is part of BedrockProtocol.
 * Copyright (C) 2014-2022 PocketMine Team <https://github.com/pmmp/BedrockProtocol>
 *
 * BedrockProtocol is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\utils\Color;

/**
 * @see DebugDrawerPacket
 */
final class PacketShapeData{

	public function __construct(
		private int $networkId,
		private ?int $type,
		private ?Vector3 $location,
		private ?float $scale,
		private ?Vector3 $rotation,
		private ?float $totalTimeLeft,
		private ?Color $color,
		private ?string $text,
		private ?Vector3 $boxBound,
		private ?Vector3 $lineEndLocation,
		private ?float $arrowHeadLength,
		private ?float $arrowHeadRadius,
		private ?int $segments,
	){}

	public static function remove(int $networkId) : self{
		return new self($networkId, null, null, null, null, null, null, null, null, null, null, null, null);
	}

	public static function line(int $networkId, Vector3 $location, Vector3 $lineEndLocation, ?Color $color = null) : self{
		return new self(
			networkId: $networkId,
			type: ScriptDebugShapeType::LINE,
			location: $location,
			scale: null,
			rotation: null,
			totalTimeLeft: null,
			color: $color,
			text: null,
			boxBound: null,
			lineEndLocation: $lineEndLocation,
			arrowHeadLength: null,
			arrowHeadRadius: null,
			segments: null
		);
	}

	public static function box(int $networkId, Vector3 $location, Vector3 $boxBound, ?float $scale = null, ?Color $color = null) : self{
		return new self(
			networkId: $networkId,
			type: ScriptDebugShapeType::BOX,
			location: $location,
			scale: $scale,
			rotation: null,
			totalTimeLeft: null,
			color: $color,
			text: null,
			boxBound: $boxBound,
			lineEndLocation: null,
			arrowHeadLength: null,
			arrowHeadRadius: null,
			segments: null
		);
	}

	public static function sphere(int $networkId, Vector3 $location, ?float $scale = null, ?Color $color = null, ?int $segments = null) : self{
		return new self(
			networkId: $networkId,
			type: ScriptDebugShapeType::SPHERE,
			location: $location,
			scale: $scale,
			rotation: null,
			totalTimeLeft: null,
			color: $color,
			text: null,
			boxBound: null,
			lineEndLocation: null,
			arrowHeadLength: null,
			arrowHeadRadius: null,
			segments: $segments
		);
	}

	public static function circle(int $networkId, Vector3 $location, ?float $scale = null, ?Color $color = null, ?int $segments = null) : self{
		return new self(
			networkId: $networkId,
			type: ScriptDebugShapeType::CIRCLE,
			location: $location,
			scale: $scale,
			rotation: null,
			totalTimeLeft: null,
			color: $color,
			text: null,
			boxBound: null,
			lineEndLocation: null,
			arrowHeadLength: null,
			arrowHeadRadius: null,
			segments: $segments
		);
	}

	public static function text(int $networkId, Vector3 $location, string $text, ?Color $color = null) : self{
		return new self(
			networkId: $networkId,
			type: ScriptDebugShapeType::TEXT,
			location: $location,
			scale: null,
			rotation: null,
			totalTimeLeft: null,
			color: $color,
			text: $text,
			boxBound: null,
			lineEndLocation: null,
			arrowHeadLength: null,
			arrowHeadRadius: null,
			segments: null
		);
	}

	public static function arrow(int $networkId, Vector3 $location, Vector3 $lineEndLocation, ?float $scale = null, ?Color $color = null, ?float $arrowHeadLength = null, ?float $arrowHeadRadius = null, ?int $segments = null) : self{
		return new self(
			networkId: $networkId,
			type: ScriptDebugShapeType::ARROW,
			location: $location,
			scale: $scale,
			rotation: null,
			totalTimeLeft: null,
			color: $color,
			text: null,
			boxBound: null,
			lineEndLocation: $lineEndLocation,
			arrowHeadLength: $arrowHeadLength,
			arrowHeadRadius: $arrowHeadRadius,
			segments: $segments
		);
	}

	public function getNetworkId() : int{ return $this->networkId; }

	public function getType() : ?int{ return $this->type; }

	public function getLocation() : ?Vector3{ return $this->location; }

	public function getScale() : ?float{ return $this->scale; }

	public function getRotation() : ?Vector3{ return $this->rotation; }

	public function getTotalTimeLeft() : ?float{ return $this->totalTimeLeft; }

	public function getColor() : ?Color{ return $this->color; }

	public function getText() : ?string{ return $this->text; }

	public function getBoxBound() : ?Vector3{ return $this->boxBound; }

	public function getLineEndLocation() : ?Vector3{ return $this->lineEndLocation; }

	public function getArrowHeadLength() : ?float{ return $this->arrowHeadLength; }

	public function getArrowHeadRadius() : ?float{ return $this->arrowHeadRadius; }

	public function getSegments() : ?int{ return $this->segments; }

	public static function read(NetworkBinaryStream $in) : self{
		$networkId = $in->getUnsignedVarLong();
		$type = $in->readOptional(fn() => $in->getByte());
		$location = $in->readOptional(fn() => $in->getVector3());
		$scale = $in->readOptional(fn() => $in->getLFloat());
		$rotation = $in->readOptional(fn() => $in->getVector3());
		$totalTimeLeft = $in->readOptional(fn() => $in->getLFloat());
		$color = $in->readOptional(fn() => Color::fromARGB($in->getLInt()));
		$text = $in->readOptional(fn() => $in->getString());
		$boxBound = $in->readOptional(fn() => $in->getVector3());
		$lineEndLocation = $in->readOptional(fn() => $in->getVector3());
		$arrowHeadLength = $in->readOptional(fn() => $in->getLFloat());
		$arrowHeadRadius = $in->readOptional(fn() => $in->getLFloat());
		$segments = $in->readOptional(fn() => $in->getByte());

		return new self(
			$networkId,
			$type,
			$location,
			$scale,
			$rotation,
			$totalTimeLeft,
			$color,
			$text,
			$boxBound,
			$lineEndLocation,
			$arrowHeadLength,
			$arrowHeadRadius,
			$segments
		);
	}

	public function write(NetworkBinaryStream $out) : void{
		$out->putUnsignedVarLong($this->networkId);
		$out->writeOptional($this->type, fn(int $type) => $out->putByte($type));
		$out->writeOptional($this->location, fn(Vector3 $location) => $out->putVector3($location));
		$out->writeOptional($this->scale, fn(float $scale) => $out->putLFloat($scale));
		$out->writeOptional($this->rotation, fn(Vector3 $rotation) => $out->putVector3($rotation));
		$out->writeOptional($this->totalTimeLeft, fn(float $totalTimeLeft) => $out->putLFloat($totalTimeLeft));
		$out->writeOptional($this->color, fn(Color $color) => $out->putLInt($color->toARGB()));
		$out->writeOptional($this->text, fn(string $text) => $out->putString($text));
		$out->writeOptional($this->boxBound, fn(Vector3 $boxBound) => $out->putVector3($boxBound));
		$out->writeOptional($this->lineEndLocation, fn(Vector3 $lineEndLocation) => $out->putVector3($lineEndLocation));
		$out->writeOptional($this->arrowHeadLength, fn(float $arrowHeadLength) => $out->putLFloat($arrowHeadLength));
		$out->writeOptional($this->arrowHeadRadius, fn(float $arrowHeadRadius) => $out->putLFloat($arrowHeadRadius));
		$out->writeOptional($this->segments, fn(int $segments) => $out->putByte($segments));
	}
}