<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

class ScriptDebugShapeType{

	private function __construct(){
		//NOOP
	}

	public const LINE = 0;
	public const BOX = 1;
	public const SPHERE = 2;
	public const CIRCLE = 3;
	public const TEXT = 4;
	public const ARROW = 5;

	public const PAYLOAD_TYPE_NONE = 0;
	public const PAYLOAD_TYPE_ARROW = 1;
	public const PAYLOAD_TYPE_TEXT = 2;
	public const PAYLOAD_TYPE_BOX = 3;
	public const PAYLOAD_TYPE_LINE = 4;
	public const PAYLOAD_TYPE_CIRCLE_OR_SPHERE = 5;

	public static function getPayloadType(int $shapeType) : int{
		return match($shapeType){
			self::ARROW => self::PAYLOAD_TYPE_ARROW,
			self::TEXT => self::PAYLOAD_TYPE_TEXT,
			self::BOX => self::PAYLOAD_TYPE_BOX,
			self::LINE => self::PAYLOAD_TYPE_LINE,
			self::CIRCLE, self::SPHERE => self::PAYLOAD_TYPE_CIRCLE_OR_SPHERE,
			default => self::PAYLOAD_TYPE_NONE
		};
	}
}