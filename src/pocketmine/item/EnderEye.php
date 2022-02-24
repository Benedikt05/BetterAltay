<?php

namespace pocketmine\item;

use pocketmine\object\EnderEyeEntity;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;

class EnderEye extends Item
{
	public function getMaxStackSize() : int{
		return 1;
	}

	public function onClickAir(Player $player, Vector3 $directionVector): bool{
        $nbt = Entity::createBaseNBT($player->add(0, $player->getEyeHeight(), 0), $directionVector, $player->yaw, $player->pitch);
        $entity = Entity::createEntity("EnderEyeEntity", $player->level, $nbt);
        $entity->setOwningEntity($player);
        $entity->setMotion($entity->getMotion()->multiply(1.5));
        $this->pop();
        //TODO End Portal Location
        return true;
    }
}
