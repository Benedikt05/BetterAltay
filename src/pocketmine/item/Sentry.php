<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class Sentry extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::SENTRY_ARMOR_TRIM_SMITHING_TEMPLATE, $meta, "Sentry Smithing Template");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}