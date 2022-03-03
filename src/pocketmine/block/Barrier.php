<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\item\Item;

class Barrier extends Solid{

	protected $id = self::BARRIER_BLOCK;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getName() : string{
		return "Barrier";
	}

	public function getHardness() : float{
		return -1;
	}

	public function getBlastResistance() : float{
		return 18000000;
	}

	public function isBreakable(Item $item) : bool{
		return false;
	}
}