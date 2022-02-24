<?php

declare(strict_types=1);

namespace pocketmine\entity\projectile;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityCombustByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\level\Level;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\TakeItemActorPacket;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;

class TridentEntity extends Projectile{

	public const NETWORK_ID = self::TRIDENT;

	public $height = 0.25;
	public $width = 0.25;
	public $gravity = 0.04;

	protected $damage = 8;

	public function __construct(Level $level, CompoundTag $nbt, ?Entity $shootingEntity = null, bool $isCiritical = false) {
		parent::__construct($level, $nbt, $shootingEntity);
		$this->setCritical($isCiritical);
	}

	public function isCritical(): bool {
		return $this->getGenericFlag(self::DATA_FLAG_CRITICAL);
	}

	public function setCritical(bool $value = true): void {
		$this->setGenericFlag(self::DATA_FLAG_CRITICAL, $value);
	}

	public function getResultDamage(): int {
		$base = parent::getResultDamage();
		if ($this->isCritical()) {
			return ($base + mt_rand(0, (int)($base / 2) + 1));
		}

		return $base;
	}

	public function entityBaseTick(int $tickDiff = 1): bool {
		if ($this->closed) {
			return false;
		}

		if ($this->ticksLived > 1200) {
			$this->flagForDespawn();
		}

		return parent::entityBaseTick($tickDiff);
	}

	public function onCollideWithPlayer(Player $player): void {
		if ($this->ticksLived < 10 and $this->getOwningEntity() === $player) {
			return;
		}

		$item = ItemFactory::get(ItemIds::TRIDENT, $this->namedtag->getInt("trident_damage", 0));
		foreach ($this->getEnchantments() as $enchantment) {
			$item->addEnchantment($enchantment);
		}
		$playerInventory = $player->getInventory();
		if (!$player->isCreative()) {
			if (!$playerInventory->canAddItem($item)) {
				return;
			}

			$playerInventory->addItem(clone $item);
		}

		$pk = new TakeItemActorPacket();
		$pk->eid = $player->getId();
		$pk->target = $this->getId();
		$this->server->broadcastPacket($this->getViewers(), $pk);

		$this->flagForDespawn();
	}

	public function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void {
		if ($entityHit !== $this->getOwningEntity()) {
			$damage = $this->getResultDamage();

			if ($damage >= 0) {
				if ($this->getOwningEntity() === null) {
					$ev = new EntityDamageByEntityEvent($this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
				} else {
					$ev = new EntityDamageByChildEntityEvent($this->getOwningEntity(), $this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
				}

				$entityHit->attack($ev);

				if ($this->isOnFire()) {
					$ev = new EntityCombustByEntityEvent($this, $entityHit, 5);
					$ev->call();
					if (!$ev->isCancelled()) {
						$entityHit->setOnFire($ev->getDuration());
					}
				}
			}

			$nbt = Entity::createBaseNBT($this->add(0.5, 0, 0.5), new Vector3(), -$this->yaw);
			$trident = new TridentEntity($this->getLevelNonNull(), $nbt, $this->getOwningEntity());
			$trident->namedtag->setInt("trident_damage", $this->namedtag->getInt("trident_damage", 0));
			foreach ($this->getEnchantments() as $enchantment) {
				$trident->addEnchantment($enchantment);
			}
			$trident->spawnToAll();

			$this->flagForDespawn();
		}

		$this->getLevelNonNull()->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_ITEM_TRIDENT_HIT);
	}

	public function onHitBlock(Block $blockHit, RayTraceResult $hitResult): void {
		parent::onHitBlock($blockHit, $hitResult);

		$this->getLevelNonNull()->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_ITEM_TRIDENT_HIT_GROUND);

		$ench = $this->namedtag->getTag(Item::TAG_ENCH);
		if ($ench instanceof ListTag) {
			/** @var CompoundTag $entry */
			foreach ($ench as $entry) {
				if ($entry->getShort("id") === Enchantment::LOYALTY) {
					$this->server->getScheduler()->scheduleDelayedTask(new ClosureTask(function(): void {
						if (!$this->isFlaggedForDespawn()) {
							$owner = $this->getOwningEntity();

							if ($owner instanceof Player && !$owner->isCreative()) {
								$item = ItemFactory::get(ItemIds::TRIDENT, $this->namedtag->getInt("trident_damage", 0));
								foreach ($this->getEnchantments() as $enchantment) {
									$item->addEnchantment($enchantment);
								}

								$playerInventory = $owner->getInventory();
								if ($playerInventory->canAddItem($item)) {
									$playerInventory->addItem(clone $item);
								}
							}

							$this->flagForDespawn();
						}
					}), (int)$this->distance($this->getOwningEntity()));
				}
			}
		}
	}

	/**
	 * @return EnchantmentInstance[]
	 */
	public function getEnchantments(): array {
		/** @var EnchantmentInstance[] $enchantments */
		$enchantments = [];

		$ench = $this->namedtag->getTag(Item::TAG_ENCH);
		if ($ench instanceof ListTag) {
			/** @var CompoundTag $entry */
			foreach ($ench as $entry) {
				$e = Enchantment::getEnchantment($entry->getShort("id"));
				if ($e !== null) {
					$enchantments[] = new EnchantmentInstance($e, $entry->getShort("lvl"));
				}
			}
		}

		return $enchantments;
	}

	public function addEnchantment(EnchantmentInstance $enchantment): void {
		$found = false;

		$ench = $this->namedtag->getTag(Item::TAG_ENCH);
		if (!($ench instanceof ListTag)) {
			$ench = new ListTag(Item::TAG_ENCH, [], NBT::TAG_Compound);
		} else {
			/** @var CompoundTag $entry */
			foreach ($ench as $k => $entry) {
				if ($entry->getShort("id") === $enchantment->getId()) {
					$ench->set($k, new CompoundTag("", [
						new ShortTag("id", $enchantment->getId()),
						new ShortTag("lvl", $enchantment->getLevel())
					]));
					$found = true;
					break;
				}
			}
		}

		if (!$found) {
			$ench->push(new CompoundTag("", [
				new ShortTag("id", $enchantment->getId()),
				new ShortTag("lvl", $enchantment->getLevel())
			]));
		}

		$this->namedtag->setTag($ench);
	}
}