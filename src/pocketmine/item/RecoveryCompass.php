<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class RecoveryCompass extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::RECOVERY_COMPASS, $meta, "Recovery Compass");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}