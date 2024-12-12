<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class TrialKey extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::TRIAL_KEY, $meta, "Trial Key");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}