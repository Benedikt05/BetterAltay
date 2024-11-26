<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class SmithingTemplate extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::NETHERITE_UPGRADE_SMITHING_TEMPLATE, $meta, "Smiting Template");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}