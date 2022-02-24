<?php

namespace pocketmine\item;

use pocketmine\entity\{
	Entity, projectile\Projectile
};
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\{
	Item, Potion, ProjectileItem
};
use pocketmine\level\sound\LaunchSound;
use pocketmine\math\Vector3;
use pocketmine\Player;

class LingeringPotion extends ProjectileItem {

	public const TAG_POTION_ID = "PotionId";

	public function __construct($meta = 0){
		parent::__construct(Item::LINGERING_POTION, $meta, $this->getNameByMeta($meta));
	}

	public function getNameByMeta($meta){
		switch($meta){
			case Potion::WATER:
				return "Lingering Water Bottle";
			case Potion::MUNDANE:
			case Potion::LONG_MUNDANE:
				return "Lingering Mundane Potion";
			case Potion::THICK:
				return "Lingering Thick Potion";
			case Potion::AWKWARD:
				return "Lingering Awkward Potion";
			case Potion::INVISIBILITY:
			case Potion::LONG_INVISIBILITY:
				return "Lingering Potion of Invisibility";
			case Potion::LEAPING:
			case Potion::LONG_LEAPING:
				return "Lingering Potion of Leaping";
			case Potion::STRONG_LEAPING:
				return "Lingering Potion of Leaping II";
			case Potion::FIRE_RESISTANCE:
			case Potion::LONG_FIRE_RESISTANCE:
				return "Lingering Potion of Fire Residence";
			case Potion::SWIFTNESS:
			case Potion::LONG_SWIFTNESS:
				return "Lingering Potion of Swiftness";
			case Potion::STRONG_SWIFTNESS:
				return "Lingering Potion of Swiftness II";
			case Potion::SLOWNESS:
			case Potion::LONG_SLOWNESS:
				return "Lingering Potion of Slowness";
			case Potion::WATER_BREATHING:
			case Potion::LONG_WATER_BREATHING:
				return "Lingering Potion of Water Breathing";
			case Potion::HARMING:
				return "Lingering Potion of Harming";
			case Potion::STRONG_HARMING:
				return "Lingering Potion of Harming II";
			case Potion::POISON:
			case Potion::LONG_POISON:
				return "Lingering Potion of Poison";
			case Potion::STRONG_POISON:
				return "Lingering Potion of Poison II";
			case Potion::HEALING:
				return "Lingering Potion of Healing";
			case Potion::STRONG_HEALING:
				return "Lingering Potion of Healing II";
			case Potion::NIGHT_VISION:
			case Potion::LONG_NIGHT_VISION:
				return "Lingerin Potion of Night Vision";
			default:
				return "Lingering Potion";
		}
	}

	public function getMaxStackSize(): int{
		return 1;
	}

	public function onClickAir(Player $player, Vector3 $directionVector): bool{//TODO optimise
		$nbt = Entity::createBaseNBT($player->add(0, $player->getEyeHeight(), 0), $directionVector, $player->yaw, $player->pitch);
		$nbt->setShort(self::TAG_POTION_ID, $this->meta);
		$projectile = Entity::createEntity($this->getProjectileEntityType(), $player->getLevel(), $nbt, $player);

		if($projectile !== null){
			$projectile->setMotion($projectile->getMotion()->multiply($this->getThrowForce()));
		}

		$this->count--;

		if($projectile instanceof Projectile){
			$player->getServer()->getPluginManager()->callEvent($projectileEv = new ProjectileLaunchEvent($projectile));
			if($projectileEv->isCancelled()){
				$projectile->kill();
			}else{
				$projectile->spawnToAll();
				$player->getLevel()->addSound(new LaunchSound($player), $player->getViewers());
			}
		}else{
			$projectile->spawnToAll();
		}

		return true;
	}

	public function getProjectileEntityType(): string{
		return "LingeringPotion";
	}

	public function getThrowForce(): float{
		return 0.5;
	}
}