<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class Eye extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::EYE_ARMOR_TRIM_SMITHING_TEMPLATE, $meta, "Eye Smithing Template");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}