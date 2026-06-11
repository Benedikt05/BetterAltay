<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

class BossBarOverlay{

	private function __construct(){
		//NOOP
	}

	public const PROGRESS = 0;
	public const NOTCHED_6 = 1;
	public const NOTCHED_10 = 2;
	public const NOTCHED_12 = 3;
	public const NOTCHED_20 = 4;
}
