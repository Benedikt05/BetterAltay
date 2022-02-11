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

namespace pocketmine\entity\passive;

use pocketmine\entity\behavior\FloatBehavior;
use pocketmine\entity\behavior\LeapAtTargetBehavior;
use pocketmine\entity\behavior\LookAtPlayerBehavior;
use pocketmine\entity\behavior\MeleeAttackBehavior;
use pocketmine\entity\behavior\RandomLookAroundBehavior;
use pocketmine\entity\behavior\RandomStrollBehavior;
use pocketmine\entity\behavior\StayWhileSittingBehavior;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\Player;
use function mt_rand;
use pocketmine\entity\Ageable;
use pocketmine\inventory\AltayEntityEquipment;
use pocketmine\item\ItemFactory;
use pocketmine\entity\Monster;
use pocketmine\entity\Smite;

class ZombiePigman extends Monster implements Ageable, Smite{
	public const NETWORK_ID = self::ZOMBIE_PIGMAN;

	public $width = 0.6;
	public $height = 1.8;
	
	/** @var AltayEntityEquipment */
	protected $equipment;
	
	/** @var StayWhileSittingBehavior */
	protected $behaviorSitting;

	protected function addBehaviors() : void{
		$this->behaviorPool->setBehavior(0, new FloatBehavior($this));
		$this->behaviorPool->setBehavior(1, new LeapAtTargetBehavior($this, 0.4));
		$this->behaviorPool->setBehavior(2, new MeleeAttackBehavior($this, 1.0));
		$this->behaviorPool->setBehavior(3, new RandomStrollBehavior($this, 1.0));
		$this->behaviorPool->setBehavior(4, new LookAtPlayerBehavior($this, 8.0));
		$this->behaviorPool->setBehavior(5, new RandomLookAroundBehavior($this));
	}

	protected function initEntity() : void{
		$this->setMaxHealth(20);
		$this->setMovementSpeed(0.4);
		$this->setAttackDamage(4);

		parent::initEntity();
		$this->equipment = new AltayEntityEquipment($this);

		$this->equipment->setItemInHand(ItemFactory::get(Item::GOLDEN_SWORD));
	}

	public function getName() : string{
		return "ZombiePigman";
	}
	
	public function getDrops() : array{
		$drops = [
			ItemFactory::get(Item::ROTTEN_FLESH, 0, mt_rand(0, 2))
		];

		if(mt_rand(0, 199) < 5){
			switch(mt_rand(0, 2)){
				case 0:
					$drops[] = ItemFactory::get(Item::GOLD_NUGGET, 0, 1);
					break;
				case 1:
					$drops[] = ItemFactory::get(Item::GOLDEN_SWORD, 0, 1);
					break;
			}
		}

		return $drops;
	}


	public function setTargetEntity(?Entity $target) : void{
		parent::setTargetEntity($target);
		$this->setAngry(true);
		if($target == null){
			$this->setAngry();
		}
	}

	public function isAngry() : bool{
		return $this->getGenericFlag(self::DATA_FLAG_ANGRY);
	}

	public function setAngry(bool $angry = true) : void{
		$this->setGenericFlag(self::DATA_FLAG_ANGRY, $angry);
	}

	public function sendSpawnPacket(Player $player) : void{
		parent::sendSpawnPacket($player);

		$this->equipment->sendContents([$player]);
	}
}
