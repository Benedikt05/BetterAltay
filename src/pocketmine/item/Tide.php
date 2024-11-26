<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class Tide extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::TIDE_ARMOR_TRIM_SMITHING_TEMPLATE, $meta, "Tide Smithing Template");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}