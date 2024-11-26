<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class Ward extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::WARD_ARMOR_TRIM_SMITHING_TEMPLATE, $meta, "Ward Smithing Template");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}