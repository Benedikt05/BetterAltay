<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class Coast extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::COAST_ARMOR_TRIM_SMITHING_TEMPLATE, $meta, "Coast Smithing Template");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}