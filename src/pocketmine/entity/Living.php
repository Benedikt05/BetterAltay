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

namespace pocketmine\entity;

use pocketmine\block\Block;
use pocketmine\entity\object\LeashKnot;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityEffectAddEvent;
use pocketmine\event\entity\EntityEffectRemoveEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\ArmorInventoryEventProcessor;
use pocketmine\item\Armor;
use pocketmine\item\Consumable;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\MaybeConsumable;
use pocketmine\math\Vector3;
use pocketmine\math\VoxelRayTrace;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\Player;
use pocketmine\timings\Timings;
use pocketmine\utils\Binary;
use pocketmine\utils\Color;
use pocketmine\utils\UUID;
use function abs;
use function array_shift;
use function atan2;
use function boolval;
use function ceil;
use function count;
use function floor;
use function intval;
use function lcg_value;
use function max;
use function min;
use function mt_getrandmax;
use function mt_rand;
use function sqrt;
use const M_PI;

abstract class Living extends Entity implements Damageable{

	protected $gravity = 0.08;
	protected $drag = 0.02;

	/** @var int */
	protected $attackTime = 0;

	/** @var int */
	public $deadTicks = 0;
	/** @var int */
	protected $maxDeadTicks = 25;

	/** @var float */
	protected $jumpVelocity = 0.42;

	/** @var EffectInstance[] */
	protected $effects = [];

	/** @var ArmorInventory */
	protected $armorInventory;

	/** @var int|null */
	protected $revengeTargetId = null;
	protected $revengeTimer = 0;

	/** @var int|null */
	protected $lastAttackedEntityId = null;

	/** @var bool */
	protected $leashed = false;
	/** @var CompoundTag */
	protected $leashNbt;
	/** @var int|null */
	protected $leashedToEntityId = null;

	/** @var float */
	protected $moveForward = 0.0;
	/** @var float */
	protected $moveStrafing = 0.0;

	abstract public function getName() : string;

	public function getRevengeTarget() : ?Entity{
		if($this->revengeTargetId !== null){
			return $this->server->findEntity($this->revengeTargetId);
		}

		return null;
	}

	public function setRevengeTarget(?Entity $revengeTarget) : void{
		if($revengeTarget === null){
			$this->revengeTargetId = null;
		}else{
			$this->revengeTargetId = $revengeTarget->getId();
		}

		$this->revengeTimer = $this->ticksLived;
	}

	public function getLastAttackedEntity() : ?Entity{
		if($this->lastAttackedEntityId !== null){
			return $this->server->findEntity($this->lastAttackedEntityId);
		}

		return null;
	}

	public function setLastAttackedEntity(?Entity $attackedEntity) : void{
		if($attackedEntity === null){
			$this->lastAttackedEntityId = null;
		}else{
			$this->lastAttackedEntityId = $attackedEntity->getId();
		}
	}

	public function getRevengeTimer() : int{
		return $this->revengeTimer;
	}

	public function getLeashedToEntity() : ?Entity{
		if($this->leashedToEntityId !== null){
			return $this->server->findEntity($this->leashedToEntityId);
		}

		return null;
	}

	public function setLeashedToEntity(Entity $leashedToEntity, bool $send = true) : void{
		$this->leashed = true;
		$this->leashedToEntityId = $leashedToEntity->getId();

		if($send){
			$this->setGenericFlag(self::DATA_FLAG_LEASHED, true);
			$this->propertyManager->setLong(self::DATA_LEAD_HOLDER_EID, $leashedToEntity->getId());
		}
	}

	public function isLeashed() : bool{
		return $this->leashed;
	}

	protected function initEntity() : void{
		parent::initEntity();

		$this->armorInventory = new ArmorInventory($this);
		//TODO: load/save armor inventory contents
		$this->armorInventory->setEventProcessor(new ArmorInventoryEventProcessor($this));

		if($this->namedtag->hasTag("HealF", FloatTag::class)){
			$health = $this->namedtag->getFloat("HealF");
			$this->namedtag->removeTag("HealF");
		}elseif($this->namedtag->hasTag("Health", ShortTag::class)){
			//Older versions of PocketMine-MP incorrectly saved this as a short instead of a float
			$health = $this->namedtag->getShort("Health");
			$this->namedtag->removeTag("Health");
		}else{
			$health = $this->namedtag->getFloat("Health", $this->getMaxHealth());
		}

		$this->setHealth($health);

		/** @var CompoundTag[]|ListTag|null $activeEffectsTag */
		$activeEffectsTag = $this->namedtag->getListTag("ActiveEffects");
		if($activeEffectsTag !== null){
			foreach($activeEffectsTag as $e){
				$effect = Effect::getEffect($e->getByte("Id"));
				if($effect === null){
					continue;
				}

				$this->addEffect(new EffectInstance(
					$effect,
					$e->getInt("Duration"),
					Binary::unsignByte($e->getByte("Amplifier")),
					$e->getByte("ShowParticles", 1) !== 0,
					$e->getByte("Ambient", 0) !== 0
				));
			}
		}

		$this->leashed = boolval($this->namedtag->getByte("Leashed", 0));

		if($this->isLeashed() and $this->namedtag->hasTag("Leash", CompoundTag::class)){
			$this->leashNbt = $this->namedtag->getCompoundTag("Leash");
		}

		$this->setGenericFlag(self::DATA_FLAG_LEASHED, $this->leashed);
	}

	protected function addAttributes() : void{
		$this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::HEALTH));
		$this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::FOLLOW_RANGE));
		$this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::KNOCKBACK_RESISTANCE));
		$this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::MOVEMENT_SPEED));
		$this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::ATTACK_DAMAGE));
		$this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::ABSORPTION));
	}

	public function setMovementSpeed(float $speed) : void{
		$this->attributeMap->getAttribute(Attribute::MOVEMENT_SPEED)->setValue($speed, true);
	}

	public function getMovementSpeed() : float{
		return $this->attributeMap->getAttribute(Attribute::MOVEMENT_SPEED)->getValue();
	}

	public function setAttackDamage(float $damage) : void{
		$this->attributeMap->getAttribute(Attribute::ATTACK_DAMAGE)->setValue($damage, true);
	}

	public function getAttackDamage() : float{
		return $this->attributeMap->getAttribute(Attribute::ATTACK_DAMAGE)->getValue();
	}

	public function setFollowRange(float $range) : void{
		$this->attributeMap->getAttribute(Attribute::FOLLOW_RANGE)->setValue($range, true);
	}

	public function getFollowRange() : float{
		return $this->attributeMap->getAttribute(Attribute::FOLLOW_RANGE)->getValue();
	}

	public function setHealth(float $amount) : void{
		$wasAlive = $this->isAlive();
		parent::setHealth($amount);
		$this->attributeMap->getAttribute(Attribute::HEALTH)->setValue(ceil($this->getHealth()), true);
		if($this->isAlive() and !$wasAlive){
			$this->broadcastEntityEvent(ActorEventPacket::RESPAWN);
		}
	}

	public function getMaxHealth() : int{
		return (int) $this->attributeMap->getAttribute(Attribute::HEALTH)->getMaxValue();
	}

	public function setMaxHealth(int $amount) : void{
		$this->attributeMap->getAttribute(Attribute::HEALTH)->setMaxValue($amount)->setDefaultValue($amount);
	}

	public function getAbsorption() : float{
		return $this->attributeMap->getAttribute(Attribute::ABSORPTION)->getValue();
	}

	public function setAbsorption(float $absorption) : void{
		$this->attributeMap->getAttribute(Attribute::ABSORPTION)->setValue($absorption);
	}

	public function saveNBT() : void{
		parent::saveNBT();
		$this->namedtag->setFloat("Health", $this->getHealth(), true);

		if(count($this->effects) > 0){
			$effects = [];
			foreach($this->effects as $effect){
				$effects[] = new CompoundTag("", [
					new ByteTag("Id", $effect->getId()),
					new ByteTag("Amplifier", Binary::signByte($effect->getAmplifier())),
					new IntTag("Duration", $effect->getDuration()),
					new ByteTag("Ambient", $effect->isAmbient() ? 1 : 0),
					new ByteTag("ShowParticles", $effect->isVisible() ? 1 : 0)
				]);
			}

			$this->namedtag->setTag(new ListTag("ActiveEffects", $effects));
		}else{
			$this->namedtag->removeTag("ActiveEffects");
		}

		$this->namedtag->setByte("Leashed", intval($this->leashed));
		if(($leashedToEntity = $this->getLeashedToEntity())){
			$leashNbt = new CompoundTag("Leash");

			if($leashedToEntity instanceof Living){
				$leashNbt->setString("UUID", $leashedToEntity->getUniqueId()->toString());
			}elseif($leashedToEntity instanceof LeashKnot){
				$pos = $leashedToEntity->getHangingPosition();
				$leashNbt->setInt("X", $pos->x);
				$leashNbt->setInt("Y", $pos->y);
				$leashNbt->setInt("Z", $pos->z);
			}

			$this->namedtag->setTag($leashNbt);
		}
	}

	public function hasLineOfSight(Entity $entity) : bool{
		//TODO: head height
		return true;
		//return $this->getLevelNonNull()->rayTraceBlocks(Vector3::createVector($this->x, $this->y + $this->height, $this->z), Vector3::createVector($entity->x, $entity->y + $entity->height, $entity->z)) === null;
	}

	/**
	 * Returns an array of Effects currently active on the mob.
	 * @return EffectInstance[]
	 */
	public function getEffects() : array{
		return $this->effects;
	}

	/**
	 * Removes all effects from the mob.
	 */
	public function removeAllEffects() : void{
		foreach($this->effects as $effect){
			$this->removeEffect($effect->getId());
		}
	}

	/**
	 * Removes the effect with the specified ID from the mob.
	 */
	public function removeEffect(int $effectId) : void{
		if(isset($this->effects[$effectId])){
			$effect = $this->effects[$effectId];
			$hasExpired = $effect->hasExpired();
			$ev = new EntityEffectRemoveEvent($this, $effect);
			$ev->call();
			if($ev->isCancelled()){
				if($hasExpired and !$ev->getEffect()->hasExpired()){ //altered duration of an expired effect to make it not get removed
					$this->sendEffectAdd($ev->getEffect(), true);
				}
				return;
			}

			unset($this->effects[$effectId]);
			$effect->getType()->remove($this, $effect);
			$this->sendEffectRemove($effect);

			$this->recalculateEffectColor();
		}
	}

	/**
	 * Returns the effect instance active on this entity with the specified ID, or null if the mob does not have the
	 * effect.
	 */
	public function getEffect(int $effectId) : ?EffectInstance{
		return $this->effects[$effectId] ?? null;
	}

	/**
	 * Returns whether the specified effect is active on the mob.
	 */
	public function hasEffect(int $effectId) : bool{
		return isset($this->effects[$effectId]);
	}

	/**
	 * Returns whether the mob has any active effects.
	 */
	public function hasEffects() : bool{
		return count($this->effects) > 0;
	}

	/**
	 * Adds an effect to the mob.
	 * If a weaker effect of the same type is already applied, it will be replaced.
	 * If a weaker or equal-strength effect is already applied but has a shorter duration, it will be replaced.
	 *
	 * @return bool whether the effect has been successfully applied.
	 */
	public function addEffect(EffectInstance $effect) : bool{
		$oldEffect = null;
		$cancelled = false;

		if(isset($this->effects[$effect->getId()])){
			$oldEffect = $this->effects[$effect->getId()];
			if(
				abs($effect->getAmplifier()) < $oldEffect->getAmplifier()
				or (abs($effect->getAmplifier()) === abs($oldEffect->getAmplifier()) and $effect->getDuration() < $oldEffect->getDuration())
			){
				$cancelled = true;
			}
		}

		$ev = new EntityEffectAddEvent($this, $effect, $oldEffect);
		$ev->setCancelled($cancelled);

		$ev->call();
		if($ev->isCancelled()){
			return false;
		}

		if($oldEffect !== null){
			$oldEffect->getType()->remove($this, $oldEffect);
		}

		$effect->getType()->add($this, $effect);
		$this->sendEffectAdd($effect, $oldEffect !== null);

		$this->effects[$effect->getId()] = $effect;

		$this->recalculateEffectColor();

		return true;
	}

	/**
	 * Recalculates the mob's potion bubbles colour based on the active effects.
	 */
	protected function recalculateEffectColor() : void{
		/** @var Color[] $colors */
		$colors = [];
		$ambient = true;
		foreach($this->effects as $effect){
			if($effect->isVisible() and $effect->getType()->hasBubbles()){
				$level = $effect->getEffectLevel();
				$color = $effect->getColor();
				for($i = 0; $i < $level; ++$i){
					$colors[] = $color;
				}

				if(!$effect->isAmbient()){
					$ambient = false;
				}
			}
		}

		if(count($colors) > 0){
			$this->propertyManager->setInt(Entity::DATA_POTION_COLOR, Color::mix(...$colors)->toARGB());
			$this->propertyManager->setByte(Entity::DATA_POTION_AMBIENT, $ambient ? 1 : 0);
		}else{
			$this->propertyManager->setInt(Entity::DATA_POTION_COLOR, 0);
			$this->propertyManager->setByte(Entity::DATA_POTION_AMBIENT, 0);
		}
	}

	/**
	 * Sends the mob's potion effects to the specified player.
	 */
	public function sendPotionEffects(Player $player) : void{
		foreach($this->effects as $effect){
			$pk = new MobEffectPacket();
			$pk->entityRuntimeId = $this->id;
			$pk->effectId = $effect->getId();
			$pk->amplifier = $effect->getAmplifier();
			$pk->particles = $effect->isVisible();
			$pk->duration = $effect->getDuration();
			$pk->eventId = MobEffectPacket::EVENT_ADD;

			$player->dataPacket($pk);
		}
	}

	protected function sendEffectAdd(EffectInstance $effect, bool $replacesOldEffect) : void{

	}

	protected function sendEffectRemove(EffectInstance $effect) : void{

	}

	/**
	 * Causes the mob to consume the given Consumable object, applying applicable effects, health bonuses, food bonuses,
	 * etc.
	 */
	public function consumeObject(Consumable $consumable) : bool{
		if($consumable instanceof MaybeConsumable and !$consumable->canBeConsumed()){
			return false;
		}

		$this->applyConsumptionResults($consumable);
		return true;
	}

	/**
	 * Applies effects from consuming the object. This shouldn't do any can-consume checks (those are expected to be
	 * handled by the caller).
	 */
	protected function applyConsumptionResults(Consumable $consumable) : void{
		foreach($consumable->getAdditionalEffects() as $effect){
			$this->addEffect($effect);
		}

		$consumable->onConsume($this);
	}

	/**
	 * Returns the initial upwards velocity of a jumping entity in blocks/tick, including additional velocity due to effects.
	 */
	public function getJumpVelocity() : float{
		return $this->jumpVelocity + ($this->hasEffect(Effect::JUMP) ? ($this->getEffect(Effect::JUMP)->getEffectLevel() / 10) : 0);
	}

	/**
	 * Called when the entity jumps from the ground. This method adds upwards velocity to the entity.
	 */
	public function jump() : void{
		if($this->onGround){
			$this->motion->y = $this->getJumpVelocity(); //Y motion should already be 0 if we're jumping from the ground.
		}
	}

	public function fall(float $fallDistance) : void{
		$damage = ceil($fallDistance - 3 - ($this->hasEffect(Effect::JUMP) ? $this->getEffect(Effect::JUMP)->getEffectLevel() : 0));
		if($damage > 0){
			$ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_FALL, $damage);
			$this->attack($ev);
		}
	}

	/**
	 * Returns how many armour points this mob has. Armour points provide a percentage reduction to damage.
	 * For mobs which can wear armour, this should return the sum total of the armour points provided by their
	 * equipment.
	 */
	public function getArmorPoints() : int{
		$total = 0;
		foreach($this->armorInventory->getContents() as $item){
			$total += $item->getDefensePoints();
		}

		return $total;
	}

	/**
	 * Returns the highest level of the specified enchantment on any armour piece that the entity is currently wearing.
	 */
	public function getHighestArmorEnchantmentLevel(int $enchantmentId) : int{
		$result = 0;
		foreach($this->armorInventory->getContents() as $item){
			$result = max($result, $item->getEnchantmentLevel($enchantmentId));
		}

		return $result;
	}

	public function getArmorInventory() : ArmorInventory{
		return $this->armorInventory;
	}

	public function setOnFire(int $seconds) : void{
		parent::setOnFire($seconds - (int) min($seconds, $seconds * $this->getHighestArmorEnchantmentLevel(Enchantment::FIRE_PROTECTION) * 0.15));
	}

	/**
	 * Called prior to EntityDamageEvent execution to apply modifications to the event's damage, such as reduction due
	 * to effects or armour.
	 */
	public function applyDamageModifiers(EntityDamageEvent $source) : void{
		if($this->lastDamageCause !== null and $this->attackTime > 0){
			if($this->lastDamageCause->getBaseDamage() >= $source->getBaseDamage()){
				$source->setCancelled();
			}
			$source->setModifier(-$this->lastDamageCause->getBaseDamage(), EntityDamageEvent::MODIFIER_PREVIOUS_DAMAGE_COOLDOWN);
		}
		if($source->canBeReducedByArmor()){
			//MCPE uses the same system as PC did pre-1.9
			$source->setModifier(-$source->getFinalDamage() * $this->getArmorPoints() * 0.04, EntityDamageEvent::MODIFIER_ARMOR);
		}

		$cause = $source->getCause();
		if($this->hasEffect(Effect::DAMAGE_RESISTANCE) and $cause !== EntityDamageEvent::CAUSE_VOID and $cause !== EntityDamageEvent::CAUSE_SUICIDE){
			$source->setModifier(-$source->getFinalDamage() * min(1, 0.2 * $this->getEffect(Effect::DAMAGE_RESISTANCE)->getEffectLevel()), EntityDamageEvent::MODIFIER_RESISTANCE);
		}

		$totalEpf = 0;
		foreach($this->armorInventory->getContents() as $item){
			if($item instanceof Armor){
				$totalEpf += $item->getEnchantmentProtectionFactor($source);
			}
		}
		$source->setModifier(-$source->getFinalDamage() * min(ceil(min($totalEpf, 25) * (mt_rand(50, 100) / 100)), 20) * 0.04, EntityDamageEvent::MODIFIER_ARMOR_ENCHANTMENTS);

		$source->setModifier(-min($this->getAbsorption(), $source->getFinalDamage()), EntityDamageEvent::MODIFIER_ABSORPTION);
	}

	/**
	 * Called after EntityDamageEvent execution to apply post-hurt effects, such as reducing absorption or modifying
	 * armour durability.
	 * This will not be called by damage sources causing death.
	 */
	protected function applyPostDamageEffects(EntityDamageEvent $source) : void{
		$this->setAbsorption(max(0, $this->getAbsorption() + $source->getModifier(EntityDamageEvent::MODIFIER_ABSORPTION)));
		$this->damageArmor($source->getBaseDamage());

		if($source instanceof EntityDamageByEntityEvent){
			$damage = 0;
			foreach($this->armorInventory->getContents() as $k => $item){
				foreach($item->getEnchantments() as $enchantmentInstance){
					$enchantmentInstance->getType()->onHurtEntity($this, $source->getEntity(), $item, $enchantmentInstance->getLevel());
				}

				$this->armorInventory->setItem($k, $item);
			}

			if($damage > 0){
				$source->getDamager()->attack(new EntityDamageByEntityEvent($this, $source->getDamager(), EntityDamageEvent::CAUSE_MAGIC, $damage));
			}
		}
	}

	/**
	 * Damages the worn armour according to the amount of damage given. Each 4 points (rounded down) deals 1 damage
	 * point to each armour piece, but never less than 1 total.
	 */
	public function damageArmor(float $damage) : void{
		$durabilityRemoved = (int) max(floor($damage / 4), 1);

		$armor = $this->armorInventory->getContents(true);
		foreach($armor as $item){
			if($item instanceof Armor){
				$this->damageItem($item, $durabilityRemoved);
			}
		}

		$this->armorInventory->setContents($armor);
	}

	private function damageItem(Durable $item, int $durabilityRemoved) : void{
		$item->applyDamage($durabilityRemoved);
		if($item->isBroken()){
			$this->level->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_BREAK);
		}
	}

	public function attack(EntityDamageEvent $source) : void{
		if($this->noDamageTicks > 0){
			$source->setCancelled();
		}

		if($this->hasEffect(Effect::FIRE_RESISTANCE) and (
				$source->getCause() === EntityDamageEvent::CAUSE_FIRE
				or $source->getCause() === EntityDamageEvent::CAUSE_FIRE_TICK
				or $source->getCause() === EntityDamageEvent::CAUSE_LAVA
			)
		){
			$source->setCancelled();
		}

		$this->applyDamageModifiers($source);

		if($source instanceof EntityDamageByEntityEvent and (
			$source->getCause() === EntityDamageEvent::CAUSE_BLOCK_EXPLOSION or
			$source->getCause() === EntityDamageEvent::CAUSE_ENTITY_EXPLOSION)
		){
			//TODO: knockback should not just apply for entity damage sources
			//this doesn't matter for TNT right now because the PrimedTNT entity is considered the source, not the block.
			$base = $source->getKnockBack();
			$source->setKnockBack($base - min($base, $base * $this->getHighestArmorEnchantmentLevel(Enchantment::BLAST_PROTECTION) * 0.15));
		}

		parent::attack($source);

		if($source->isCancelled()){
			return;
		}

		$this->attackTime = $source->getAttackCooldown();

		if($source instanceof EntityDamageByChildEntityEvent){
			$e = $source->getChild();
			if($e !== null){
				$motion = $e->getMotion();
				$this->knockBack($e, $source->getBaseDamage(), $motion->x, $motion->z, $source->getKnockBack());
			}
		}elseif($source instanceof EntityDamageByEntityEvent){
			$e = $source->getDamager();
			if($e !== null){
				$deltaX = $this->x - $e->x;
				$deltaZ = $this->z - $e->z;
				$this->knockBack($e, $source->getBaseDamage(), $deltaX, $deltaZ, $source->getKnockBack());

				$attacker = $source->getDamager();
				if($attacker instanceof Living){
					$this->setRevengeTarget($attacker);
					$attacker->setLastAttackedEntity($this);
				}
			}
		}

		if($this->isAlive()){
			$this->applyPostDamageEffects($source);
			$this->doHitAnimation();
		}
	}

	protected function doHitAnimation() : void{
		$this->broadcastEntityEvent(ActorEventPacket::HURT_ANIMATION);
	}

	public function knockBack(Entity $attacker, float $damage, float $x, float $z, float $base = 0.4) : void{
		$f = sqrt($x * $x + $z * $z);
		if($f <= 0){
			return;
		}
		if(mt_rand() / mt_getrandmax() > $this->getAttributeMap()->getAttribute(Attribute::KNOCKBACK_RESISTANCE)->getValue()){
			$f = 1 / $f;

			$motion = clone $this->motion;

			$motion->x /= 2;
			$motion->y /= 2;
			$motion->z /= 2;
			$motion->x += $x * $f * $base;
			$motion->y += $base;
			$motion->z += $z * $f * $base;

			if($motion->y > $base){
				$motion->y = $base;
			}

			$this->setMotion($motion);
		}
	}

	public function kill() : void{
		parent::kill();
		$this->onDeath();
		$this->startDeathAnimation();
	}

	protected function onDeath() : void{
		$ev = new EntityDeathEvent($this, $this->getDrops(), $this->getXpDropAmount());
		$ev->call();
		foreach($ev->getDrops() as $item){
			$this->getLevelNonNull()->dropItem($this, $item);
		}

		//TODO: check death conditions (must have been damaged by player < 5 seconds from death)
		$this->level->dropExperience($this, $ev->getXpDropAmount());
	}

	protected function onDeathUpdate(int $tickDiff) : bool{
		if($this->deadTicks < $this->maxDeadTicks){
			$this->deadTicks += $tickDiff;
			if($this->deadTicks >= $this->maxDeadTicks){
				$this->endDeathAnimation();
			}
		}

		return $this->deadTicks >= $this->maxDeadTicks;
	}

	protected function startDeathAnimation() : void{
		$this->broadcastEntityEvent(ActorEventPacket::DEATH_ANIMATION);
	}

	protected function endDeathAnimation() : void{
		$this->despawnFromAll();
	}

	public function onUpdate(int $currentTick) : bool{
		if($this->closed) return false;

		$this->updateLeashedState();

		return parent::onUpdate($currentTick);
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		Timings::$timerLivingEntityBaseTick->startTiming();

		if($revengeTarget = $this->getRevengeTarget()){
			if(!$revengeTarget->isAlive() or ($this->ticksLived - $this->revengeTimer) > 100){
				$this->setRevengeTarget(null);
			}
		}

		$hasUpdate = parent::entityBaseTick($tickDiff);

		if($this->isAlive()){
			if($this->doEffectsTick($tickDiff)){
				$hasUpdate = true;
			}

			if($this->isInsideOfSolid()){
				$hasUpdate = true;
				$ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_SUFFOCATION, 1);
				$this->attack($ev);
			}

			if($this->doAirSupplyTick($tickDiff)){
				$hasUpdate = true;
			}
		}

		if($this->attackTime > 0){
			$this->attackTime -= $tickDiff;
		}

		Timings::$timerLivingEntityBaseTick->stopTiming();

		return $hasUpdate;
	}

	protected function doEffectsTick(int $tickDiff = 1) : bool{
		foreach($this->effects as $instance){
			$type = $instance->getType();
			if($type->canTick($instance)){
				$type->applyEffect($this, $instance);
			}
			$instance->decreaseDuration($tickDiff);
			if($instance->hasExpired()){
				$this->removeEffect($instance->getId());
			}
		}

		return count($this->effects) > 0;
	}

	/**
	 * Ticks the entity's air supply, consuming it when underwater and regenerating it when out of water.
	 */
	protected function doAirSupplyTick(int $tickDiff) : bool{
		$ticks = $this->getAirSupplyTicks();
		$oldTicks = $ticks;
		if(!$this->canBreathe()){
			$this->setBreathing(false);

			if(($respirationLevel = $this->armorInventory->getHelmet()->getEnchantmentLevel(Enchantment::RESPIRATION)) <= 0 or
				lcg_value() <= (1 / ($respirationLevel + 1))
			){
				$ticks -= $tickDiff;
				if($ticks <= -20){
					$ticks = 0;
					$this->onAirExpired();
				}
			}
		}elseif(!$this->isBreathing()){
			if($ticks < ($max = $this->getMaxAirSupplyTicks())){
				$ticks += $tickDiff * 5;
			}
			if($ticks >= $max){
				$ticks = $max;
				$this->setBreathing(true);
			}
		}

		if($ticks !== $oldTicks){
			$this->setAirSupplyTicks($ticks);
		}

		return $ticks !== $oldTicks;
	}

	/**
	 * Returns whether the entity can currently breathe.
	 */
	public function canBreathe() : bool{
		return $this->hasEffect(Effect::WATER_BREATHING) or $this->hasEffect(Effect::CONDUIT_POWER) or !$this->isUnderwater();
	}

	/**
	 * Returns whether the entity is currently breathing or not. If this is false, the entity's air supply will be used.
	 */
	public function isBreathing() : bool{
		return $this->getGenericFlag(self::DATA_FLAG_BREATHING);
	}

	/**
	 * Sets whether the entity is currently breathing. If false, it will cause the entity's air supply to be used.
	 * For players, this also shows the oxygen bar.
	 */
	public function setBreathing(bool $value = true) : void{
		$this->setGenericFlag(self::DATA_FLAG_BREATHING, $value);
	}

	/**
	 * Returns the number of ticks remaining in the entity's air supply. Note that the entity may survive longer than
	 * this amount of time without damage due to enchantments such as Respiration.
	 */
	public function getAirSupplyTicks() : int{
		return $this->propertyManager->getShort(self::DATA_AIR);
	}

	/**
	 * Sets the number of air ticks left in the entity's air supply.
	 */
	public function setAirSupplyTicks(int $ticks) : void{
		$this->propertyManager->setShort(self::DATA_AIR, $ticks);
	}

	/**
	 * Returns the maximum amount of air ticks the entity's air supply can contain.
	 */
	public function getMaxAirSupplyTicks() : int{
		return $this->propertyManager->getShort(self::DATA_MAX_AIR);
	}

	/**
	 * Sets the maximum amount of air ticks the air supply can hold.
	 */
	public function setMaxAirSupplyTicks(int $ticks) : void{
		$this->propertyManager->setShort(self::DATA_MAX_AIR, $ticks);
	}

	/**
	 * Called when the entity's air supply ticks reaches -20 or lower. The entity will usually take damage at this point
	 * and then the supply is reset to 0, so this method will be called roughly every second.
	 */
	public function onAirExpired() : void{
		$ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_DROWNING, 2);
		$this->attack($ev);
	}

	/**
	 * @return Item[]
	 */
	public function getDrops() : array{
		return [];
	}

	/**
	 * Returns the amount of XP this mob will drop on death.
	 */
	public function getXpDropAmount() : int{
		return 0;
	}

	/**
	 * @param true[] $transparent
	 * @phpstan-param array<int, true> $transparent
	 *
	 * @return Block[]
	 */
	public function getLineOfSight(int $maxDistance, int $maxLength = 0, array $transparent = []) : array{
		if($maxDistance > 120){
			$maxDistance = 120;
		}

		if(count($transparent) === 0){
			$transparent = null;
		}

		$blocks = [];
		$nextIndex = 0;

		foreach(VoxelRayTrace::inDirection($this->add(0, $this->eyeHeight, 0), $this->getDirectionVector(), $maxDistance) as $vector3){
			$block = $this->level->getBlockAt($vector3->x, $vector3->y, $vector3->z);
			$blocks[$nextIndex++] = $block;

			if($maxLength !== 0 and count($blocks) > $maxLength){
				array_shift($blocks);
				--$nextIndex;
			}

			$id = $block->getId();

			if($transparent === null){
				if($id !== 0){
					break;
				}
			}else{
				if(!isset($transparent[$id])){
					break;
				}
			}
		}

		return $blocks;
	}

	/**
	 * @param true[] $transparent
	 * @phpstan-param array<int, true> $transparent
	 */
	public function getTargetBlock(int $maxDistance, array $transparent = []) : ?Block{
		$line = $this->getLineOfSight($maxDistance, 1, $transparent);
		if(count($line) > 0){
			return array_shift($line);
		}

		return null;
	}

	/**
	 * Changes the entity's yaw and pitch to make it look at the specified Vector3 position. For mobs, this will cause
	 * their heads to turn.
	 */
	public function lookAt(Vector3 $target) : void{
		$horizontal = sqrt(($target->x - $this->x) ** 2 + ($target->z - $this->z) ** 2);
		$vertical = $target->y - $this->y;
		$this->pitch = -atan2($vertical, $horizontal) / M_PI * 180; //negative is up, positive is down

		$xDist = $target->x - $this->x;
		$zDist = $target->z - $this->z;
		$this->yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
		if($this->yaw < 0){
			$this->yaw += 360.0;
		}
	}

	protected function sendSpawnPacket(Player $player) : void{
		parent::sendSpawnPacket($player);

		$this->armorInventory->sendContents($player);
	}

	public function close() : void{
		if(!$this->closed){
			if($this->armorInventory !== null){
				$this->armorInventory->removeAllViewers(true);
				$this->armorInventory = null;
			}
			parent::close();
		}
	}

	public function allowLeashing() : bool{
		return false;
	}

	public function clearLeashed(bool $send, bool $dropLead) : void{
		if($this->isLeashed()){
			$this->leashed = false;
			$this->leashedToEntityId = null;

			if($dropLead){
				$this->level->dropItem($this, Item::get(Item::LEAD));
			}

			if($send){
				$this->setGenericFlag(self::DATA_FLAG_LEASHED, false);
				$this->propertyManager->setLong(self::DATA_LEAD_HOLDER_EID, -1);

				$this->broadcastEntityEvent(ActorEventPacket::REMOVE_LEASH);
			}
		}
	}

	public function recreateLeash() : void{
		if($this->isLeashed() and $this->leashNbt !== null){
			if($this->leashNbt->hasTag("UUID", StringTag::class)){
				$uuid = UUID::fromString($this->leashNbt->getString("UUID"));

				foreach($this->level->getCollidingEntities($this->getBoundingBox()->expandedCopy(10, 10, 10)) as $entity){
					if($entity instanceof Living){
						if($entity->getUniqueId()->equals($uuid)){
							$this->setLeashedToEntity($entity);
							break;
						}
					}
				}
			}elseif($this->leashNbt->hasTag("X", IntTag::class) and $this->leashNbt->hasTag("Y", IntTag::class) and $this->leashNbt->hasTag("Z", IntTag::class)){
				$pos = new Vector3($this->leashNbt->getInt("X"), $this->leashNbt->getInt("Y"), $this->leashNbt->getInt("Z"));
				$knot = LeashKnot::getKnotFromPosition($this->level, $pos);

				if($knot === null){
					$knot = new LeashKnot($this->level, Entity::createBaseNBT($pos));
					$knot->spawnToAll();
				}

				$this->setLeashedToEntity($knot);
			}else{
				$this->clearLeashed(false, true);
			}
		}

		$this->leashNbt = null;
	}

	public function updateLeashedState() : void{
		if($this->leashNbt !== null){
			$this->recreateLeash();
		}

		if($this->isLeashed()){
			if(!$this->isAlive()){
				$this->clearLeashed(true, true);
			}

			$leashedToEntity = $this->getLeashedToEntity();
			if($leashedToEntity === null){
				$this->clearLeashed(true, true);
			}
		}
	}

	public function onFirstInteract(Player $player, Item $item, Vector3 $clickPos) : bool{
		if($this->isLeashed() and $this->getLeashedToEntity() === $player){
			$this->clearLeashed(true, !$player->isCreative());
			return true;
		}else{
			if($item->getId() === Item::LEAD and $this->allowLeashing()){
				$this->setLeashedToEntity($player);
				$item->pop();
				return true;
			}
		}
		return $this->onInteract($player, $item, $clickPos) ?: parent::onFirstInteract($player, $item, $clickPos);
	}

	public function onInteract(Player $player, Item $item, Vector3 $clickPos) : bool{
		return false;
	}

	public function canSpawnHere() : bool{
		return true;
	}

	public function getMaxSpawnedInChunk() : int{
		return 4;
	}

	public function getMoveForward() : float{
		return $this->moveForward;
	}

	public function setMoveForward(float $moveForward) : void{
		$this->moveForward = $moveForward;
	}

	public function getMoveStrafing() : float{
		return $this->moveStrafing;
	}

	public function setMoveStrafing(float $moveStrafing) : void{
		$this->moveStrafing = $moveStrafing;
	}
}