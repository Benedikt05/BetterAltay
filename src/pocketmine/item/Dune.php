<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class Dune extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::_ARMOR_TRIM_SMITHING_TEMPLATE, $meta, "Dune Smithing Template");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}