<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class RawIron extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::RAW_IRON, $meta, "Raw Iron");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}