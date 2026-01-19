<?php

declare(strict_types=1);

namespace pocketmine\item;

class Charcoal extends Coal{

	public function __construct(){
		$this->id = self::CHARCOAL;
		$this->name = "Charcoal";
	}
}