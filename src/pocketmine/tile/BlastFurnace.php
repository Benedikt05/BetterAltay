<?php

namespace pocketmine\tile;

use pocketmine\network\mcpe\protocol\types\WindowTypes;

class BlastFurnace extends Furnace {

	protected int $windowType = WindowTypes::BLAST_FURNACE;
}