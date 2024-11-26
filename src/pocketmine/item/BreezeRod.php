<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class BreezeRod extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::BREEZE_ROD, $meta, "Breeze Rod");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}