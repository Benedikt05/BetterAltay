<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class Mace extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::MACE, $meta, "Mace");
    }

    public function getMaxStackSize(): int {
        return 1;
    }
    
    // Add functionality for vanilla mechanics. Including dmg scaling from fall height, canceling fall damage when entity is hit, etc. This is Basically an item shell rn.
}