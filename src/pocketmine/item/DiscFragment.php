<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class DiscFragment extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::DISC_FRAGMENT_5, $meta, "Disc Fragment");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}