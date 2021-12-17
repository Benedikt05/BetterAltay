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

use pocketmine\entity\behavior\AvoidMobTypeBehavior;
use pocketmine\entity\behavior\FleeSunBehavior;
use pocketmine\entity\behavior\FloatBehavior;
use pocketmine\entity\behavior\LookAtPlayerBehavior;
use pocketmine\entity\behavior\NearestAttackableTargetBehavior;
use pocketmine\entity\behavior\RandomLookAroundBehavior;
use pocketmine\entity\behavior\RandomStrollBehavior;
use pocketmine\entity\behavior\RangedAttackBehavior;
use pocketmine\entity\behavior\RestrictSunBehavior;
use pocketmine\entity\Entity;
use pocketmine\entity\Monster;
use pocketmine\entity\passive\Wolf;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\RangedAttackerMob;
use pocketmine\entity\Smite;
use pocketmine\inventory\AltayEntityEquipment;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use function rand;
use function sqrt;

class Pillager extends Monster implements RangedAttackerMob, Smite{

	public const NETWORK_ID = self::PILLAGER;

	public $width = 0.6;
	public $height = 1.9;

	/** @var AltayEntityEquipment */
	protected $equipment;

	protected function initEntity() : void{
		$this->setMovementSpeed(0.3);
		$this->setFollowRange(35);
		$this->setAttackDamage(4);

		parent::initEntity();

		$this->equipment = new AltayEntityEquipment($this);

		$this->equipment->setItemInHand(ItemFactory::get(Item::CROSSBOW));

		// TODO: Armors
	}

	public function getName() : string{
		return "Pillager";
	}

	public function getDrops() : array{
		return [
			ItemFactory::get(Item::BONE, 0, rand(0, 2)), ItemFactory::get(Item::ARROW, 0, rand(0, 2))
		];
	}

	public function getXpDropAmount() : int{
		return 5;
	}

	protected function addBehaviors() : void{
		$this->behaviorPool->setBehavior(0, new FloatBehavior($this));
		$this->behaviorPool->setBehavior(1, new RestrictSunBehavior($this));
		$this->behaviorPool->setBehavior(2, new FleeSunBehavior($this, 1.0));
		$this->targetBehaviorPool->setBehavior(3, new NearestAttackableTargetBehavior($this, Villager::class));
		$this->behaviorPool->setBehavior(4, new RandomStrollBehavior($this, 1.0));
		$this->behaviorPool->setBehavior(5, new RangedAttackBehavior($this, 1.0, 20, 60, 15.0));
		$this->behaviorPool->setBehavior(6, new LookAtPlayerBehavior($this, 8.0));
		$this->behaviorPool->setBehavior(7, new RandomLookAroundBehavior($this));
		$this->targetBehaviorPool->setBehavior(0, new NearestAttackableTargetBehavior($this, Player::class, true));
	}

	public function onRangedAttackToTarget(Entity $target, float $power) : void{
		$pos = $this->add(0, $this->getEyeHeight() - 0.1, 0);
		$motion = $target->add(0, $target->height / 3, 0)->subtract($pos)->normalize();
		$f = sqrt($motion->x ** 2 + $motion->z ** 2);

		/** @var Arrow $arrow */
		$arrow = Entity::createEntity("Arrow", $this->level, Entity::createBaseNBT($pos->add($motion)));
		// TODO: Enchants
		$arrow->setThrowableMotion($motion->add(0, $f * 0.2, 0), 1.6, (14 - $this->level->getDifficulty() * 4));
		$arrow->setPickupMode(Arrow::PICKUP_NONE);
		$arrow->setBaseDamage($power * 2 + $this->random->nextFloat() * 0.25 + ($this->level->getDifficulty() * 0.11));

		$this->level->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_BOW);
		$arrow->spawnToAll();
	}

	public function sendSpawnPacket(Player $player) : void{
		parent::sendSpawnPacket($player);

		$this->equipment->sendContents([$player]);
	}
}
