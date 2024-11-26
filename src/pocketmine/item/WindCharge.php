<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class WindCharge extends Item { // Change to extends projectile in the future to spawn actual breeze balls.

    public function __construct(int $meta = 0){
        parent::__construct(self::WIND_CHARGE, $meta, "Wind Charge");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}