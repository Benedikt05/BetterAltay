<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\entity\projectile;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\inventory\InventoryPickupArrowEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\Level;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\TakeItemActorPacket;
use pocketmine\player\Player;
use function mt_rand;
use function sqrt;

class Arrow extends Projectile{
	public const NETWORK_ID = self::ARROW;

	public const PICKUP_NONE = 0;
	public const PICKUP_ANY = 1;
	public const PICKUP_CREATIVE = 2;

	private const TAG_PICKUP = "pickup"; //TAG_Byte

	public $width = 0.25;
	public $height = 0.25;

	protected $gravity = 0.05;
	protected $drag = 0.01;

	/** @var float */
	protected $damage = 2.0;

	/** @var int */
	protected $pickupMode = self::PICKUP_ANY;

	/** @var float */
	protected $punchKnockback = 0.0;

	/** @var int */
	protected $collideTicks = 0;

	public function __construct(Level $level, CompoundTag $nbt, ?Entity $shootingEntity = null, bool $critical = false){
		parent::__construct($level, $nbt, $shootingEntity);
		$this->setCritical($critical);
	}

	protected function initEntity() : void{
		parent::initEntity();

		$this->pickupMode = $this->namedtag->getByte(self::TAG_PICKUP, self::PICKUP_ANY, true);
		$this->collideTicks = $this->namedtag->getShort("life", $this->collideTicks);
	}

	public function setThrowableMotion(Vector3 $motion, float $velocity, float $inaccuracy) : bool{
		return $this->setMotion($motion->add(
			$this->random->nextFloat() * ($this->random->nextBoolean() ? 1 : -1) * 0.0075 * $inaccuracy,
			$this->random->nextFloat() * ($this->random->nextBoolean() ? 1 : -1) * 0.0075 * $inaccuracy,
			$this->random->nextFloat() * ($this->random->nextBoolean() ? 1 : -1) * 0.0075 * $inaccuracy)
			->multiply($velocity));
	}

	public function saveNBT() : void{
		parent::saveNBT();

		$this->namedtag->setByte(self::TAG_PICKUP, $this->pickupMode, true);
		$this->namedtag->setShort("life", $this->collideTicks);
	}

	public function isCritical() : bool{
		return $this->getGenericFlag(self::DATA_FLAG_CRITICAL);
	}

	public function setCritical(bool $value = true) : void{
		$this->setGenericFlag(self::DATA_FLAG_CRITICAL, $value);
	}

	public function getResultDamage() : int{
		$base = parent::getResultDamage();
		if($this->isCritical()){
			return ($base + mt_rand(0, (int) ($base / 2) + 1));
		}else{
			return $base;
		}
	}

	public function getPunchKnockback() : float{
		return $this->punchKnockback;
	}

	public function setPunchKnockback(float $punchKnockback) : void{
		$this->punchKnockback = $punchKnockback;
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		if($this->closed){
			return false;
		}

		$hasUpdate = parent::entityBaseTick($tickDiff);

		if($this->blockHit !== null){
			$this->collideTicks += $tickDiff;
			if($this->collideTicks > 1200){
				$this->flagForDespawn();
				$hasUpdate = true;
			}
		}else{
			$this->collideTicks = 0;
		}

		return $hasUpdate;
	}

	protected function onHit(ProjectileHitEvent $event) : void{
		$this->setCritical(false);
		$this->level->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_BOW_HIT);
	}

	protected function onHitBlock(Block $blockHit, RayTraceResult $hitResult) : void{
		parent::onHitBlock($blockHit, $hitResult);
		$this->broadcastEntityEvent(ActorEventPacket::ARROW_SHAKE, 7); //7 ticks
	}

	protected function onHitEntity(Entity $entityHit, RayTraceResult $hitResult) : void{
		parent::onHitEntity($entityHit, $hitResult);
		if($this->punchKnockback > 0){
			$horizontalSpeed = sqrt($this->motion->x ** 2 + $this->motion->z ** 2);
			if($horizontalSpeed > 0){
				$multiplier = $this->punchKnockback * 0.6 / $horizontalSpeed;
				$entityHit->setMotion($entityHit->getMotion()->add($this->motion->x * $multiplier, 0.1, $this->motion->z * $multiplier));
			}
		}
	}

	public function getPickupMode() : int{
		return $this->pickupMode;
	}

	public function setPickupMode(int $pickupMode) : void{
		$this->pickupMode = $pickupMode;
	}

	public function onCollideWithPlayer(Player $player) : void{
		if($this->blockHit === null){
			return;
		}

		$item = ItemFactory::get(Item::ARROW, 0, 1);

		$pickupInventory = $player->getOffHandInventory();
		if(!$pickupInventory->getItemInOffHand()->equals($item)){
			$pickupInventory = $player->getInventory();
			if(!$pickupInventory->canAddItem($item) and $player->isSurvival()){
				return;
			}
		}

		$ev = new InventoryPickupArrowEvent($pickupInventory, $this);
		if($this->pickupMode === self::PICKUP_NONE or ($this->pickupMode === self::PICKUP_CREATIVE and !$player->isCreative())){
			$ev->setCancelled();
		}

		$ev->call();
		if($ev->isCancelled()){
			return;
		}

		$pk = new TakeItemActorPacket();
		$pk->eid = $player->getId();
		$pk->target = $this->getId();
		$this->server->broadcastPacket($this->getViewers(), $pk);

		$pickupInventory->addItem(clone $item);
		$this->flagForDespawn();
	}
}