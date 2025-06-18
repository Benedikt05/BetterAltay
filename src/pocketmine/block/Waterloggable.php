<?php

namespace pocketmine\block;

abstract class Waterloggable extends Transparent{

	public function isWaterloggable() : bool{
		return true;
	}
}