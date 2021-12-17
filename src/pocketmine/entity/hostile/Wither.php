<?php

/*
 *               _ _
 *         /\   | | |
 *        /  \  | | |_ __ _ _   _
 *       / /\ \ | | __/ _` | | | |
 *      / ____ \| | || (_| | |_| |
 *     /_/    \_|_|\__\__,_|\__, |
 *                           __/ |
 *                          |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author TuranicTeam
 * @link https://github.com/TuranicTeam/Altay
 *
 */

declare(strict_types=1);

namespace pocketmine\entity\hostile;

use pocketmine\entity\behavior\HurtByTargetBehavior;
use pocketmine\entity\behavior\LookAtPlayerBehavior;
use pocketmine\entity\behavior\NearestAttackableTargetBehavior;
use pocketmine\entity\behavior\RandomLookAroundBehavior;
use pocketmine\entity\behavior\RandomStrollBehavior;
use pocketmine\entity\behavior\RangedAttackBehavior;
use pocketmine\entity\Entity;
use pocketmine\entity\Monster;
use pocketmine\entity\projectile\WitherSkull;
use pocketmine\entity\projectile\WitherSkullDangerous;
use pocketmine\entity\RangedAttackerMob;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\Player;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use function rand;

class Wither extends Monster implements RangedAttackerMob{

	public const NETWORK_ID = self::WITHER;

	public $height = 3.0;
	public $width = 1.0;

	public function initEntity() : void{
		$this->setMaxHealth(600);
		$this->setMovementSpeed(0.7);
		$this->setFollowRange(45);

		parent::initEntity();
	}

	public function getName() : string{
		return "Wither";
	}

	protected function addBehaviors() : void{
		$this->targetBehaviorPool->setBehavior(0, new HurtByTargetBehavior($this));
		$this->targetBehaviorPool->setBehavior(1, new NearestAttackableTargetBehavior($this, Player::class));

		$this->behaviorPool->setBehavior(1, new RangedAttackBehavior($this, 1.0, 15, 15, 15));
		$this->behaviorPool->setBehavior(2, new RandomStrollBehavior($this, 2.0));
		$this->behaviorPool->setBehavior(3, new LookAtPlayerBehavior($this, 15.0));
		$this->behaviorPool->setBehavior(4, new RandomLookAroundBehavior($this));
	}

	public function getXpDropAmount() : int{
		return 50;
	}

	public function getDrops() : array{
		return [
			ItemFactory::get(Item::NETHER_STAR, 0, 1)
		];
	}

	public function onBehaviorUpdate() : bool{
		$hasUpdate = parent::onBehaviorUpdate();

		$target = $this->getTargetEntity();
		if($target !== null and $target->y + $target->getEyeHeight() > $this->y + $this->getEyeHeight()){
			$this->motion->y += (0.10000001192092896 - $this->motion->y) * 0.10000001192092896;
		}

		return $hasUpdate;
	}

	public function onRangedAttackToTarget(Entity $target, float $power) : void{
		$dv = $target->subtract($this)->normalize();
		$fireball = new WitherSkull($this->level, Entity::createBaseNBT($this->add($this->random->nextFloat() * $power, $this->getEyeHeight() - 1, $this->random->nextFloat() * $power), $dv), $this);
		switch(mt_rand(0, 5)){
		case 0:
			$fireball = new WitherSkullDangerous($this->level, Entity::createBaseNBT($this->add($this->random->nextFloat() * $power, $this->getEyeHeight() - 1, $this->random->nextFloat() * $power), $dv), $this);
		break;
		}
		$fireball->setMotion($dv->multiply($power));
		$fireball->spawnToAll();
	}
	
	public function onCollideWithEntity(Entity $entity) : void{
		parent::onCollideWithEntity($entity);
		$entity->addEffect(new EffectInstance(Effect::getEffect(Effect::WITHER), 7 * 20, 1));
	}
}
