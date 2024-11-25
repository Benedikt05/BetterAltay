<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class AmethystCrystal extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::AMETHYST_SHARD, $meta, "Amethyst Crystal");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}
