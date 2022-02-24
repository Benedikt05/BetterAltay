<?php

namespace pocketmine\item;

use pocketmine\Player;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\Tool;

use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;

use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;

use pocketmine\math\Vector3;

use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

class CrossBow extends Tool
{

	public function __construct(int $meta = 0) {
		parent::__construct(self::CROSSBOW, $meta, "Crossbow");
	}
	
	public function getFuelTime() : int{
		return 200;
	}

	public function getMaxDurability() : int{
		return 385;
	}
	
	public function onClickAir(Player $player, Vector3 $directionVector) : bool{
		if($this->canShoot()){
			$this->shoot($player);
			$player->getInventory()->setItemInHand($this);
			return true;
		}
		if($this->isCharged()){
			return true;
		}
		if($player->isSurvival() and !$player->getInventory()->contains(ItemFactory::get(Item::ARROW, 0, 1))){
			$player->getInventory()->sendContents($player);
			return false;
		}
		$diff = $player->getItemUseDuration();
		if($diff <= 0){
			$player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_CROSSBOW_LOADING_START);
			$player->getInventory()->setItemInHand($this);
		}
		if($diff >= 24){
			$player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_CROSSBOW_LOADING_END);
			$this->setCharged($player, true);
			$player->getInventory()->setItemInHand($this);
		}
		return true;
	}

	private function setCharged(Player $player, bool $value){
		if(!$value){
			$this->removeNamedTagEntry("chargedItem");
			return;
		}
		if($player->isSurvival()){
			$player->getInventory()->removeItem(ItemFactory::get(Item::ARROW, 0, 1));
		}
		$arrow = ItemFactory::get(Item::ARROW, 0, 1);
		$list = $arrow->nbtSerialize(-1, "chargedItem");
		$list->removeTag("id");
		$list->setString("Name", "minecraft:arrow");
		$list->setInt("chargedTime", time() + 1);
		$this->setNamedTagEntry($list);
	}
	
	public function isCharged(){
		$list = $this->getNamedTagEntry("chargedItem");
		if($list == null){
			return false;
		}
		return true;
	}
	
	private function canShoot(){
		$list = $this->getNamedTagEntry("chargedItem");
		if($list == null){
			return false;
		}
		if($list->getInt("chargedTime") > time()){
			return false;
		}
		return true;
	}
	
	public function shoot(Player $player){
		$nbt = Entity::createBaseNBT(
			$player->add(0, $player->getEyeHeight(), 0),
			$player->getDirectionVector(),
			($player->yaw > 180 ? 360 : 0) - $player->yaw,
			-$player->pitch
		);
		$nbt->setShort("Fire", $player->isOnFire() ? 45 * 60 : 0);
		$entity = Entity::createEntity("Arrow", $player->getLevel(), $nbt, $player);
		if($entity instanceof Projectile){
			if($entity instanceof ArrowEntity){
				if($this->hasEnchantment(Enchantment::INFINITY)){
					$entity->setPickupMode(ArrowEntity::PICKUP_CREATIVE);
				}
				if(($punchLevel = $this->getEnchantmentLevel(Enchantment::PUNCH)) > 0){
					$entity->setPunchKnockback($punchLevel);
				}
				if(($powerLevel = $this->getEnchantmentLevel(Enchantment::POWER)) > 0){
					$entity->setBaseDamage($entity->getBaseDamage() + (($powerLevel + 1) / 2));
				}
				if($this->hasEnchantment(Enchantment::FLAME)){
					$entity->setOnFire(intdiv($entity->getFireTicks(), 20) + 100);
				}
			}
			$ev = new EntityShootBowEvent($player, $this, $entity, 7);
			$ev->call();
			$entity = $ev->getProjectile();
			if($ev->isCancelled()){
				$entity->flagForDespawn();
				$player->getInventory()->sendContents($player);
			} else {
				$entity->setMotion($entity->getMotion()->multiply($ev->getForce()));
				if($player->isSurvival()){
					$this->applyDamage(1);
				}
				if($entity instanceof Projectile){
					$projectileEv = new ProjectileLaunchEvent($entity);
					$projectileEv->call();
					if($projectileEv->isCancelled()){
						$ev->getProjectile()->flagForDespawn();
					}else{
						$ev->getProjectile()->spawnToAll();
						$player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_CROSSBOW_SHOOT);
						$this->setCharged($player, false);
					}
				} else {
					$entity->spawnToAll();
				}
			}
		} else {
			$entity->spawnToAll();
		}
	}
	
}