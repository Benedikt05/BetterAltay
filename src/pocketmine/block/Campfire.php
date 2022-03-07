<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\entity\projectile\SplashPotion;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\inventory\FurnaceRecipe;
use pocketmine\item\FlintSteel;
use pocketmine\item\Item;
use pocketmine\item\Potion;
use pocketmine\item\Shovel;
use pocketmine\level\sound\GhastShootSound;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;

class Campfire extends Transparent{
	use PlaceholderTrait;

	/** @var bool */
	protected $extinguished = false;
	/** @var Item[] */
	protected $items = [];
	/** @var int[] */
	protected $itemTime = [];

	public function __construct(){
		parent::__construct(Block::NORMAL_CAMPFIRE_BLOCK, 0, "Campfire", Item::NORMAL_CAMPFIRE_ITEM);
	}

	public function hasEntityCollision() : bool{
		return true;
	}

	public function getLightLevel() : int{
		return $this->extinguished ? 0 : 15;
	}

	public function isAffectedBySilkTouch() : bool{
		return true;
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		return [Item::get(Item::COAL, 1, 2)];
	}

	public function isExtinguished() : bool{
		return $this->extinguished;
	}

	public function setExtinguished(bool $extinguish = true): self{
		$this->extinguished = $extinguish;
		return $this;
	}

	public function canCook(Item $item) : bool{
		return $this->getLevel()->getServer()->getCraftingManager()->getFurnaceRecipes()[600] == $item instanceof FurnaceRecipe;
	}

	public function canAddItem(Item $item) : bool{
		if(count($this->items) >= 4){
			return false;
		}
		return $this->canCook($item);
	}

	public function setItem(Item $item, int $slot) : void{
		if($slot < 0 or $slot > 3){
			throw new \InvalidArgumentException("Slot must be in range 0-3, got " . $slot);
		}
		if($item->isNull()){
			if(isset($this->items[$slot])){
				unset($this->items[$slot]);
			}
		}else{
			$this->items[$slot] = $item;
		}
	}

	public function setSlotTime(int $slot, int $time) : void{
		$this->itemTime[$slot] = $time;
	}

	public function getSlotTime(int $slot) : int{
		return $this->itemTime[$slot] ?? 0;
	}

	public function addItem(Item $item) : bool{
		$item->setCount(1);
		if(!$this->canAddItem($item)){
			return false;
		}
		$this->setItem($item, count($this->items));
		return true;
	}

	private function increaseSlotTime(int $slot) : void{
		$this->setSlotTime($slot, $this->getSlotTime($slot) + 1);
	}

	private function extinguish() : void{
		$this->getLevel()->broadcastLevelSoundEvent($this->asVector3(), LevelSoundEventPacket::SOUND_EXTINGUISH_FIRE);
		$this->getLevel()->setBlock($this->asVector3(), $this->setExtinguished());
	}

	private function fire() : void{
		$this->getLevel()->addSound(new GhastShootSound($this->asVector3()->add(0.5, 0.5, 0.5)), $this->getLevel()->getPlayers());
		$this->getLevel()->setBlock($this, $this->setExtinguished(false));
		$this->getLevel()->scheduleDelayedBlockUpdate($this->asVector3(), 1);
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		if($this->getSide(Vector3::SIDE_DOWN)->isTransparent()){
			return false;
		}
		$faces = [
			0 => 3,
			1 => 0,
			2 => 1,
			3 => 2,
		];
		$this->meta = $faces[$player instanceof Player ? $player->getDirection() : 0];
		return parent::place($item, $blockReplace, $blockClicked, $face, $clickVector, $player);
	}

	public function onBreak(Item $item, ?Player $player = null) : bool{
		$this->items = [];
		$this->itemTime = [];
		return parent::onBreak($item, $player);
	}

	public function onActivate(Item $item, ?Player $player = null) : bool{
		if($player !== null){
			if($item instanceof FlintSteel){
				if($this->extinguished){
					$item->applyDamage(1);
					$this->fire();
				}
				return true;
			}
			if($item instanceof Shovel && !$this->extinguished){
				$item->applyDamage(1);
				$this->extinguish();
				return true;
			}

			if($this->addItem(clone $item)){
				$item->pop();
				$this->asVector3()->add(0.5, 0.5, 0.5);
				$this->getLevel()->broadcastLevelEvent($this->asVector3(), LevelEventPacket::EVENT_SOUND_ITEMFRAME_ADD_ITEM);
				$this->getLevel()->setBlock($this->asVector3(), $this);
				if(count($this->items) === 1){
					$this->getLevel()->scheduleDelayedBlockUpdate($this->asVector3(), 1);
				}
				return true;
			}
		}
		return false;
	}

	public function onNearbyBlockChange() : void{
		$block = $this->getSide(Vector3::SIDE_UP);
		if($block instanceof Water && !$this->extinguished){
			$this->extinguish();
		}
	}

	public function onEntityCollide(Entity $entity) : void{
		if($this->extinguished){
			if($entity->isOnFire()){
				$this->fire();
				return;
			}
			return;
		}
		if($entity instanceof SplashPotion && $entity->getPotionId()->getDisplayName() === Potion::WATER){
			$this->extinguish();
		}elseif($entity instanceof Living){
			$entity->attack(new EntityDamageByBlockEvent($this, $entity, EntityDamageEvent::CAUSE_FIRE, 1));
			$entity->setOnFire(8);
		}
	}

	public function onScheduledUpdate() : void{
		if(!$this->extinguished){
			foreach($this->items as $slot => $old_item){
				$this->increaseSlotTime($slot);
				if($this->getSlotTime($slot) >= 600){
					$this->setItem(Item::get(0), $slot);
					$this->setSlotTime($slot, 0);
					$this->getLevel()->dropItem($this->asVector3()->add(0, 1), Item::get(1));
				}
			}
			$this->getLevel()->setBlock($this->asVector3(), $this);
			if(!empty($this->items)){
				$this->getLevel()->scheduleDelayedBlockUpdate($this->asVector3(), 1);
			}
		}
	}
}