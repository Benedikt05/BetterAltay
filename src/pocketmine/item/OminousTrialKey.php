<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class OminousTrialKey extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::OMINOUS_TRIAL_KEY, $meta, "Ominous Trial Key");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}