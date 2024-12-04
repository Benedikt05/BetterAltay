<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class Flow extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::FLOW_ARMOR_TRIM_SMITHING_TEMPLATE, $meta, "Flow Smithing Template");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}