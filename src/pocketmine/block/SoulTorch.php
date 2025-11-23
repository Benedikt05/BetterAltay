<?php

declare(strict_types=1);

namespace pocketmine\block;

class SoulTorch extends Torch{

	protected string $id = self::SOUL_TORCH;

	public function getName() : string{
		return "Soul Torch";
	}

	public function getLightLevel() : int{
		return 10;
	}
}