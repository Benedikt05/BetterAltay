<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class ArmadilloScute extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::ARMADILLO_SCUTE, $meta, "Armadillo Scute");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
}