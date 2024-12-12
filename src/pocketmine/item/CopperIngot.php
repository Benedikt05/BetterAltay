<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class CopperIngot extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::COPPER_INGOT, $meta, "Copper Ingot");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}