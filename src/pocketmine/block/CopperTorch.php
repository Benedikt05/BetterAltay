<?php

declare(strict_types=1);

namespace pocketmine\block;

class CopperTorch extends Torch{

	protected string $id = self::COPPER_TORCH;

	public function getName() : string{
		return "Copper Torch";
	}

}
