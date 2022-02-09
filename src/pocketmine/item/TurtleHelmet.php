<?php

declare(strict_types=1);

namespace pocketmine\item;

class TurtleHelmet extends Armor{

	public function __construct(){
		parent::__construct(self::TURTLE_HELMET, 0, "Turtle Shell");
	}

	public function getArmorSlot() : int{
		return 0;
	}

	public function getMaxDurability() : int{
		return 275;
	}
}