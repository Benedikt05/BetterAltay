<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class RawCopper extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::RAW_COPPER, $meta, "Raw Copper");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}