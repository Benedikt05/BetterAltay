<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class Wayfinder extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::WAYFINDER_ARMOR_TRIM_SMITHING_TEMPLATE, $meta, "Wayfinder Smithing Template");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}