<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class Rib extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::RIB_ARMOR_TRIM_SMITHING_TEMPLATE, $meta, "Rib Smithing Template");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}