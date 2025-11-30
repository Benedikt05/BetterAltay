<?php

namespace pocketmine\block;

use pocketmine\block\material\WoodType;

class WoodenTrapdoor extends Trapdoor{

	public function __construct(private WoodType $material, int $meta = 0){
		$this->id = "minecraft:" . $this->material->getType() . "_trapdoor";
		parent::__construct($meta);
	}


	public function getName() : string{
		return $this->material->getName() . " Trapdoor";
	}
}