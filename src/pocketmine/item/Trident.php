<?php

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\entity\Entity;
use pocketmine\entity\projectile\TridentEntity;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\level\sound\TridentThrowSound;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Trident extends Tool
{

	public function getMaxDurability() : int{
		return 251;
	}

	public function onClickAir(Player $player, Vector3 $directionVector) : bool{
		$location = $player->getLocation();

		$diff = $player->getItemUseDuration();
		$p = $diff / 20;
		$baseForce = min((($p ** 2) + $p * 2) / 3, 1) * 3;
		if($baseForce < 0.9 || $diff < 8){
			return false;
		}

		$entity = new TridentEntity($player->level, Entity::createBaseNBT($player->add(0, $player->getEyeHeight() - 0.1, 0), $player->getDirectionVector()->multiply(0.4)), $this, $player);
		$entity->setMotion($player->getDirectionVector()->multiply($baseForce));

		$ev = new ProjectileLaunchEvent($entity);
		$ev->call();
		if($ev->isCancelled()){
			$ev->getEntity()->flagForDespawn();
			return false;
		}
		$ev->getEntity()->spawnToAll();
		$location->level->addSound(new TridentThrowSound());
		$entity->item->applyDamage(1);
		$this->pop();
		return true;
	}
}