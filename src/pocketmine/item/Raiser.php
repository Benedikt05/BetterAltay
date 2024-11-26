<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class Raiser extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::RAISER_ARMOR_TRIM_SMITHING_TEMPLATE, $meta, "Raiser Smithing Template");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}