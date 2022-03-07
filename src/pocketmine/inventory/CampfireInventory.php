<?php

declare(strict_types=1);

namespace pocketmine\inventory;

class CampfireInventory extends SimpleInventory{

	public function __construct(){
		parent::__construct(4);
	}

	public function getName() : string{
		return "CampfireInventory";
	}

	public function getDefaultSize() : int{
		return 4;
	}
}