<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class RawGold extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::RAW_GOLD, $meta, "Raw Gold");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}