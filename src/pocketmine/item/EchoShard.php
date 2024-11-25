<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\Player;

class EchoShard extends Item {

    public function __construct(int $meta = 0){
        parent::__construct(self::ECHO_SHARD, $meta, "Echo Shard");
    }

    public function getMaxStackSize(): int {
        return 64;
    }
    public function getName(): string{
        return "Echo Shard";
}
}
