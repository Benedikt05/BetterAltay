<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class SnifferEgg extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::SNIFFER_EGG, $meta, "Sniffer Egg");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}
