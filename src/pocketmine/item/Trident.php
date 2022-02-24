<?php

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\entity\Entity;
use pocketmine\entity\projectile\TridentEntity;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;

class Trident extends Tool{

	public function getMaxDurability() : int{
		return 251;
	}

	public function onAttackEntity(Entity $victim) : bool{
		return $this->applyDamage(1);
	}

	public function getAttackPoints() : int{
		return 9;
	}

	public function onReleaseUsing(Player $player) : bool{
		$diff = $player->getItemUseDuration();
		$p = $diff / 20;
		$force = min((($p ** 2) + $p * 2) / 3, 1);

		if($force < 0.5 or $diff < 5){
			return false;
		}

		$player->getLevelNonNull()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_ITEM_TRIDENT_THROW);

		if($this->hasEnchantment(Enchantment::RIPTIDE)){
			if(!$player->isCreative()){
				$this->applyDamage(1);
			}

			return true;
		}

		if(!$player->isCreative()){
			$this->applyDamage(1);
			$this->pop();
		}

		$nbt = Entity::createBaseNBT($player->add(0, $player->getEyeHeight()), $player->getDirectionVector()->multiply($force * 4), ($player->yaw > 180 ? 360 : 0) - $player->yaw, -$player->pitch);
		$entity = new TridentEntity($player->getLevelNonNull(), $nbt, $player, $force >= 1);
		$entity->namedtag->setInt("trident_damage", $this->meta);
		foreach($this->getEnchantments() as $enchantment){
			$entity->addEnchantment($enchantment);
		}
		$entity->spawnToAll();

		return true;
	}
}