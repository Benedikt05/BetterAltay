<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class Snout extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::SNOUT_ARMOR_TRIM_SMITHING_TEMPLATE, $meta, "Snout Smithing Template");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}