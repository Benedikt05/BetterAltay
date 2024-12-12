<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class Host extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::HOST_ARMOR_TRIM_SMITHING_TEMPLATE, $meta, "Host Smithing Template");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}