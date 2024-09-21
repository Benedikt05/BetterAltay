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

namespace pocketmine\entity;


use pocketmine\block\Liquid;
use pocketmine\entity\behavior\BehaviorPool;
use pocketmine\entity\helper\EntityBodyHelper;
use pocketmine\entity\helper\EntityJumpHelper;
use pocketmine\entity\helper\EntityLookHelper;
use pocketmine\entity\helper\EntityMoveHelper;
use pocketmine\entity\pathfinder\EntityNavigator;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\timings\Timings;
use function abs;
use function boolval;
use function in_array;
use function intval;

abstract class Mob extends Living{

	public const MOTION_THRESHOLD = 0.0005;

	/** @var BehaviorPool */
	protected $behaviorPool;
	/** @var BehaviorPool */
	protected $targetBehaviorPool;
	/** @var EntityNavigator */
	protected $navigator;
	/** @var Entity[] */
	protected $seenEntities = [];
	/** @var Entity[] */
	protected $unseenEntities = [];
	/** @var Vector3 */
	protected $homePosition;

	protected $landMovementFactor = 0.0;
	protected $jumpMovementFactor = 0.02;

	protected $isJumping = false;
	protected $jumpTicks = 0;

	/** @var EntityMoveHelper */
	protected $moveHelper;
	/** @var EntityJumpHelper */
	protected $jumpHelper;
	/** @var EntityBodyHelper */
	protected $bodyHelper;
	/** @var EntityLookHelper */
	protected $lookHelper;

	public $yawOffset = 0.0;
	public $headYaw = 0.0;

	/**
	 * @return Vector3
	 */
	public function getHomePosition() : Vector3{
		return $this->homePosition;
	}

	/**
	 * @param Vector3 $homePosition
	 */
	public function setHomePosition(Vector3 $homePosition) : void{
		$this->homePosition = $homePosition;
	}

	public function getAIMoveSpeed() : float{
		return $this->landMovementFactor;
	}

	public function setAIMoveSpeed(float $value) : void{
		$this->landMovementFactor = $value;
	}

	/**
	 * @return bool
	 */
	public function isJumping() : bool{
		return $this->isJumping;
	}

	/**
	 * @param bool $isJumping
	 */
	public function setJumping(bool $isJumping) : void{
		$this->isJumping = $isJumping;
	}

	/**
	 * @return EntityMoveHelper
	 */
	public function getMoveHelper() : EntityMoveHelper{
		return $this->moveHelper;
	}

	/**
	 * @return EntityJumpHelper
	 */
	public function getJumpHelper() : EntityJumpHelper{
		return $this->jumpHelper;
	}

	/**
	 * @return EntityBodyHelper
	 */
	public function getBodyHelper() : EntityBodyHelper{
		return $this->bodyHelper;
	}

	/**
	 * @return EntityLookHelper
	 */
	public function getLookHelper() : EntityLookHelper{
		return $this->lookHelper;
	}

	protected function initEntity() : void{
		parent::initEntity();

		$this->targetBehaviorPool = new BehaviorPool();
		$this->behaviorPool = new BehaviorPool();
		$this->navigator = new EntityNavigator($this);
		$this->moveHelper = new EntityMoveHelper($this);
		$this->jumpHelper = new EntityJumpHelper($this);
		$this->lookHelper = new EntityLookHelper($this);
		$this->bodyHelper = new EntityBodyHelper($this);

		$this->addBehaviors();
		$this->setImmobile(boolval($this->namedtag->getByte("Immobile", $this->namedtag->getByte("NoAI", 1))));

		$this->stepHeight = 0.6;
	}

	public function saveNBT() : void{
		parent::saveNBT();

		$this->namedtag->setByte("Immobile", intval($this->isImmobile()));
	}

	public function onUpdate(int $currentTick) : bool{
		if($this->closed) return false;

		$hasUpdate = false;

		if(!$this->isImmobile()){
			if($this->jumpTicks > 0){
				$this->jumpTicks--;
			}

			$hasUpdate = $this->onBehaviorUpdate();
		}

		return parent::onUpdate($currentTick) or $hasUpdate;
	}

	public function hasMovementUpdate() : bool{
		return parent::hasMovementUpdate()
			or abs($this->yaw - $this->lastYaw) > 0
			or abs($this->pitch - $this->lastPitch) > 0
			or abs($this->headYaw - $this->lastHeadYaw) > 0;
	}

	protected function onBehaviorUpdate() : bool{
		$hasUpdate = false;

		Timings::$mobBehaviorUpdateTimer->startTiming();
		$hasUpdate |= $this->targetBehaviorPool->onUpdate();
		$hasUpdate |= $this->behaviorPool->onUpdate();
		Timings::$mobBehaviorUpdateTimer->stopTiming();

		Timings::$mobNavigationUpdateTimer->startTiming();
		$hasUpdate |= $this->navigator->onNavigateUpdate();
		Timings::$mobNavigationUpdateTimer->stopTiming();

		$this->moveHelper->onUpdate();
		$this->lookHelper->onUpdate();
		$this->jumpHelper->doJump();

		$this->clearSightCache();

		if($this->isJumping){
			if($this->isInsideOfWater()){
				$this->handleWaterJump();
			}elseif($this->isInsideOfLava()){
				$this->handleLavaJump();
			}elseif($this->onGround and $this->jumpTicks === 0){
				$this->jump();
				$this->jumpTicks = 10;
			}
		}else{
			$this->jumpTicks = 0;
		}

		$this->moveStrafing *= 0.98;
		$this->moveForward *= 0.98;
		$this->moveWithHeading($this->moveStrafing, $this->moveForward);

		$this->bodyHelper->onUpdate();

		$this->tryToDespawn();

		return (bool) $hasUpdate;
	}

	/**
	 * @param Entity $target
	 *
	 * @return bool
	 */
	public function canSeeEntity(Entity $target) : bool{
		if(in_array($target->getId(), $this->unseenEntities)){
			return false;
		}elseif(in_array($target->getId(), $this->seenEntities)){
			return true;
		}else{
			// TODO: Fix seen from corners
			$canSee = $this->getNavigator()->isClearBetweenPoints($this, $target);

			if($canSee){
				$this->seenEntities[] = $target->getId();
			}else{
				$this->unseenEntities[] = $target->getId();
			}

			return $canSee;
		}
	}

	public function clearSightCache() : void{
		$this->seenEntities = [];
		$this->unseenEntities = [];
	}

	protected function addBehaviors() : void{

	}

	/**
	 * @return BehaviorPool
	 */
	public function getBehaviorPool() : BehaviorPool{
		return $this->behaviorPool;
	}

	/**
	 * @return BehaviorPool
	 */
	public function getTargetBehaviorPool() : BehaviorPool{
		return $this->targetBehaviorPool;
	}

	public function handleWaterJump() : void{
		$this->motion->y += 0.04;
	}

	public function handleLavaJump() : void{
		$this->motion->y += 0.04;
	}

	/**
	 * @return EntityNavigator
	 */
	public function getNavigator() : EntityNavigator{
		return $this->navigator;
	}

	public function getVerticalFaceSpeed() : int{
		return 40;
	}

	/**
	 * @return bool
	 */
	public function canBePushed() : bool{
		return !$this->isImmobile();
	}

	public function updateLeashedState() : void{
		parent::updateLeashedState();

		$entity = $this->getLeashedToEntity();
		if($this->isLeashed() and $entity !== null){
			$f = $this->distance($entity);

			if($this instanceof Tamable and $this->isSitting()){
				if($f > 10){
					$this->clearLeashed(true, true);
				}
				return;
			}

			if($f > 4){
				$this->navigator->tryMoveTo($entity, 1.0);
			}

			if($f > 6){
				$d0 = ($entity->x - $this->x) / $f;
				$d1 = ($entity->y - $this->y) / $f;
				$d2 = ($entity->z - $this->z) / $f;

				$this->motion->x += $d0 * abs($d0) * 0.4;
				$this->motion->y += $d1 * abs($d1) * 0.4;
				$this->motion->z += $d2 * abs($d2) * 0.4;
			}

			if($f > 10){
				$this->clearLeashed(true, true);
			}
		}
	}

	protected function tryToDespawn() : void{
		if($this->canDespawn() and $this->level->getNearestEntity($this, 128, Player::class, true) === null){
			$this->flagForDespawn();
		}
	}

	/**
	 * @return bool
	 */
	public function canDespawn() : bool{
		return !$this->isImmobile() and !$this->isLeashed() and $this->getOwningEntityId() === null;
	}

	/**
	 * @param Vector3 $pos
	 *
	 * @return float
	 */
	public function getBlockPathWeight(Vector3 $pos) : float{
		return 0.0;
	}

	/**
	 * @return bool
	 */
	public function canSpawnHere() : bool{
		return parent::canSpawnHere() and $this->getBlockPathWeight($this) > 0;
	}

	public function moveWithHeading(float $strafe, float $forward){
		if(!$this->isInsideOfWater()){
			if(!$this->isInsideOfLava()){
				$f4 = 0.91;

				if($this->onGround){
					$f4 *= $this->level->getBlock($this->down())->getFrictionFactor();
				}

				$f = 0.16277136 / ($f4 * $f4 * $f4);

				if($this->onGround){
					$f5 = $this->getAIMoveSpeed() * $f;
				}else{
					$f5 = $this->jumpMovementFactor;
				}

				$this->moveFlying($strafe, $forward, $f5);
			}else{
				$this->moveFlying($strafe, $forward, 0.02);

				if($this->isCollidedHorizontally and $this->level->getBlock($this) instanceof Liquid){
					$this->motion->y = 0.3;
				}
			}
		}else{
			$f2 = 0.02;
			$f3 = 0; // TODO: check enchantment

			if($f3 > 3.0){
				$f3 = 3.0;
			}

			if(!$this->onGround){
				$f3 *= 0.5;
			}

			if($f3 > 0.0){
				$f2 += ($this->getAIMoveSpeed() * 1.0 - $f2) * $f3 / 3.0;
			}

			$this->moveFlying($strafe, $forward, $f2);
			if($this->isCollidedHorizontally and $this->level->getBlock($this) instanceof Liquid){
				$this->motion->y = 0.3;
			}
		}
	}

	protected function onMovementUpdate() : void{
		if($this->clientMoveTicks === 0){
			$f = 1 - $this->drag;

			$this->motion->x *= $f;
			$this->motion->y *= $f;
			$this->motion->z *= $f;
		}

		$this->checkMotion();

		if($this->motion->x != 0 or $this->motion->y != 0 or $this->motion->z != 0){
			$this->move($this->motion->x, $this->motion->y, $this->motion->z);
		}

		$this->tryChangeMovement();
	}

	protected function tryChangeMovement() : void{
		if($this->isInsideOfWater()){
			$this->motion->x *= 0.8;
			$this->motion->y *= 0.8;
			$this->motion->z *= 0.8;

			$this->motion->y -= 0.02;
		}elseif($this->isInsideOfLava()){
			$this->motion->x *= 0.5;
			$this->motion->y *= 0.5;
			$this->motion->z *= 0.5;

			$this->motion->y -= 0.02;
		}else{
			$friction = 0.91;

			if(!$this->onGround or $this->forceMovementUpdate){
				$this->applyGravity();
			}

			$this->motion->y *= $friction;

			if($this->onGround){
				$friction *= $this->level->getBlockAt((int) floor($this->x), (int) floor($this->y - 1), (int) floor($this->z))->getFrictionFactor();
			}

			$this->motion->x *= $friction;
			$this->motion->z *= $friction;
		}
	}

	/**
	 * @return Vector3
	 */
	public function getLookVector() : Vector3{
		$y = -sin(deg2rad($this->pitch));
		$xz = cos(deg2rad($this->pitch));
		$x = -$xz * sin(deg2rad($this->headYaw));
		$z = $xz * cos(deg2rad($this->headYaw));

		return $this->temporalVector->setComponents($x, $y, $z)->normalize();
	}

	/**
	 * @param Entity $entity
	 * @param float  $dxz
	 * @param float  $dy
	 */
	public function faceEntity(Entity $entity, float $dxz, float $dy) : void{
		if($entity instanceof Living){
			$d2 = $entity->y + $entity->getEyeHeight() - ($this->y + $this->getEyeHeight());
		}else{
			$d2 = ($entity->y + $entity->getBoundingBox()->maxY) / 2 - ($this->y + $this->getEyeHeight());
		}

		$this->lookAt(new Vector3($entity->x, $d2, $entity->z));

		$this->yaw = EntityLookHelper::updateRotation($this->lastYaw, $this->yaw, $dxz);
		$this->pitch = EntityLookHelper::updateRotation($this->lastPitch, $this->pitch, $dy);
	}
}