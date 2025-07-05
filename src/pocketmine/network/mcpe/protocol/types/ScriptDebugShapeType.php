<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

class ScriptDebugShapeType{

	private function __construct(){
		//NOOP
	}

	const LINE = 0;
	const BOX = 1;
	const SPHERE = 2;
	const CIRCLE = 3;
	const TEXT = 4;
	const ARROW = 5;
}