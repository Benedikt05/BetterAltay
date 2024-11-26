<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class Wild extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::WILD_ARMOR_TRIM_SMITHING_TEMPLATE, $meta, "Wild Smithing Template");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}