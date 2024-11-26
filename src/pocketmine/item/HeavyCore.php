<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class HeavyCore extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::HEAVY_CORE, $meta, "Heavy Core");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
    // Should this be a block?
}