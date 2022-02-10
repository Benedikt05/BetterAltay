<?php

declare(strict_types=1);

namespace pocketmine\entity\projectile;

use pocketmine\entity\Entity;
use pocketmine\item\Trident;
use pocketmine\level\Level;
use pocketmine\level\sound\TridentHitSound;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;

class TridentEntity extends Projectile{

	public const NETWORK_ID = self::THROWN_TRIDENT;

	public $width = 0.25, $height = 0.25;

	public const PICKUP_NONE = 0;
	public const PICKUP_ANY = 1;
	public const PICKUP_CREATIVE = 2;

	/** @var Trident */
	public $item;

	protected $gravity = 0.05;

	protected $drag = 0.01;
	/** @var float */
	protected $damage = 8.0;

	/** @var int */
	protected $pickupMode = self::PICKUP_ANY;

	/** @var bool */
	protected $canHitEntity = true;

	private const TAG_PICKUP = "pickup";

	public function __construct(Level $level, CompoundTag $nbt, Trident $item, ?Entity $shootingEntity = null){
		if($item->isNull()){
			return;
		}
		$this->item = clone $item;
		parent::__construct($level, $nbt, $shootingEntity);
	}

	public function initEntity() : void{
		parent::initEntity();
		$this->pickupMode = $this->namedtag->getByte(self::TAG_PICKUP, self::PICKUP_ANY);
		$this->canHitEntity = $this->namedtag->getByte("canHitEntity", 1) === 1;
	}

	public function saveNBT() : void{
		parent::saveNBT();
		$this->namedtag->setTag("Trident", $this->item->nbtSerialize());
		$this->namedtag->setByte(self::TAG_PICKUP, $this->pickupMode);
		$this->namedtag->setByte("canHitEntity", $this->canHitEntity ? 1 : 0);
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		if($this->closed){
			return false;
		}

		if($this->getGenericFlag(self::DATA_FLAG_ENCHANTED)){
			$this->setGenericFlag(self::DATA_FLAG_ENCHANTED, $this->item->hasEnchantments());
		}

		return parent::entityBaseTick($tickDiff);
	}

	public function move(float $dx, float $dy, float $dz) : void{
		$motion = $this->motion;
		parent::move($dx, $dy, $dz);
		if($this->isCollided && !$this->canHitEntity){
			$this->motion = $motion;
		}
	}

	public function onHitEntity(Entity $entityHit, RayTraceResult $hitResult) : void{
		if(!$this->canHitEntity){
			return;
		}
		if($entityHit->getId() === $this->getOwningEntity()->getId()){
			if($entityHit instanceof Player){
				$this->pickup($entityHit);
				return;
			}
		}
		parent::onHitEntity($entityHit, $hitResult);
		$this->canHitEntity = false;
		$this->item->applyDamage(1);
		$newTrident = new self($this->level, $this->namedtag, $this->item, $this->getOwningEntity());
		$newTrident->spawnToAll();
		$motion = new Vector3($this->motion->x * -0.01, $this->motion->y * -0.1, $this->motion->z * -0.01);
		$newTrident->setMotion($motion);
		$this->level->addSound(new TridentHitSound());
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

		$this->pickup($player);
	}

	private function pickup(Player $player) : void{
		if($this->pickupMode === self::PICKUP_NONE or ($this->pickupMode === self::PICKUP_CREATIVE and !$player->isCreative())){
			$this->flagForDespawn();
			return;
		}

		$player->getInventory()?->addItem($this->item);
		$this->flagForDespawn();
	}
}