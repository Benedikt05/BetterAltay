<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\{ByteTag, CompoundTag, ShortTag};
use pocketmine\Player;
use pocketmine\tile\{Placeholder as PTile};
use pocketmine\tile\Tile;

final class Placeholder extends Block{
	private $default = null;

	public function __construct(Block $block = null, Tile $tile = null){
		$block = $block ?? new Reserved6(Block::RESERVED6, 0, 'reserved6');
		parent::__construct(255, $block->getDamage());
		if($block->isValid()){
			$this->position($block);
			if($tile === null){
				$nbt = PTile::createNBT($this);
				$nbt->setTag(new CompoundTag("Block", [
					new ShortTag("id", $block->getId()),
					new ByteTag("meta", $block->getDamage())
				]));
				Tile::createTile("Placeholder", $this->getLevel(), $nbt);
				return;
			}
			assert($tile instanceof PTile);
			$nbt = $tile->getCleanedNBT();
			$nbt->setTag(new CompoundTag("Block", [
				new ShortTag("id", $block->getId()),
				new ByteTag("meta", $block->getDamage())
			]));
			$readSaveData = new \ReflectionMethod($tile, 'readSaveData');
			$readSaveData->setAccessible(true);
			$readSaveData->invoke($tile, $nbt);
		}
	}

	private function getDefault() : Block{
		if($this->default === null){
			$this->default = new Reserved6(Block::RESERVED6, 0, 'reserved6');
		}
		if($this->isValid() and !$this->default->isValid()){
			$this->default->position($this);
		}
		return $this->default;
	}

	public function getBlock() : Block{
		if($this->isValid()){
			$tile = $this->getLevel()->getTile($this);
			if(!$tile instanceof PTile){
				$tile = Tile::createTile("Placeholder", $this->getLevel(), PTile::createNBT($this));
				if(!$tile instanceof PTile){
					return $this->getDefault();
				}
			}
			return $tile->getBlock(true);
		}
		return $this->getDefault();
	}

	public function getName() : string{
		return $this->getBlock()->getName();
	}

	public function getItemId() : int{
		return $this->getBlock()->getItemId();
	}

	public function getRuntimeId() : int{
		return $this->getBlock()->getRuntimeId();
	}

	public function getVariantBitmask() : int{
		return $this->getBlock()->getVariantBitmask();
	}

	public function canBeReplaced() : bool{
		return $this->getBlock()->canBeReplaced();
	}

	public function isBreakable(Item $item) : bool{
		return $this->getBlock()->isBreakable($item);
	}

	public function getToolType() : int{
		return $this->getBlock()->getToolType();
	}

	public function getToolHarvestLevel() : int{
		return $this->getBlock()->getToolHarvestLevel();
	}

	public function isCompatibleWithTool(Item $tool) : bool{
		return $this->getBlock()->isCompatibleWithTool($tool);
	}

	public function onBreak(Item $item, Player $player = null) : bool{
		return $this->getBlock()->onBreak($item, $player);
	}

	public function getBreakTime(Item $item) : float{
		return $this->getBlock()->getBreakTime($item);
	}

	public function onNearbyBlockChange() : void{
		$this->getBlock()->onNearbyBlockChange();
	}

	public function ticksRandomly() : bool{
		return true;
	}

	public function onRandomTick() : void{
		foreach($this->getLevelNonNull()->getTiles() as $tile){
			if($tile instanceof PTile){
				$block = $tile->getBlock(true);
				if($block->ticksRandomly()){
					$block->onRandomTick();
				}
			}
		}
	}

	public function onScheduledUpdate() : void{
		$this->getBlock()->onScheduledUpdate();
	}

	public function onActivate(Item $item, Player $player = null) : bool{
		return $this->getBlock()->onActivate($item, $player);
	}

	public function getHardness() : float{
		return $this->getBlock()->getHardness();
	}

	public function getBlastResistance() : float{
		return 18000000;
	}

	public function getFrictionFactor() : float{
		return $this->getBlock()->getFrictionFactor();
	}

	public function getLightLevel() : int{
		return $this->getBlock()->getLightLevel();
	}

	public function getLightFilter() : int{
		return $this->getBlock()->getLightFilter();
	}

	public function diffusesSkyLight() : bool{
		return $this->getBlock()->diffusesSkyLight();
	}

	public function isTransparent() : bool{
		return $this->getBlock()->isTransparent();
	}

	public function isSolid() : bool{
		return $this->getBlock()->isSolid();
	}

	public function canBeFlowedInto() : bool{
		return $this->getBlock()->canBeFlowedInto();
	}

	public function hasEntityCollision() : bool{
		return $this->getBlock()->hasEntityCollision();
	}

	public function canPassThrough() : bool{
		return $this->getBlock()->canPassThrough();
	}

	public function canClimb() : bool{
		return $this->getBlock()->canClimb();
	}

	public function addVelocityToEntity(Entity $entity, Vector3 $vector) : void{
		$this->getBlock()->addVelocityToEntity($entity, $vector);
	}

	public function getDrops(Item $item) : array{
		return $this->getBlock()->getDrops($item);
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		return $this->getBlock()->getDropsForCompatibleTool($item);
	}

	public function getSilkTouchDrops(Item $item) : array{
		return $this->getBlock()->getSilkTouchDrops($item);
	}

	public function getXpDropForTool(Item $item) : int{
		return $this->getBlock()->getXpDropForTool($item);
	}

	public function isAffectedBySilkTouch() : bool{
		return $this->getBlock()->isAffectedBySilkTouch();
	}

	public function getPickedItem() : Item{
		return $this->getBlock()->getPickedItem();
	}

	public function getFlameEncouragement() : int{
		return $this->getBlock()->getFlameEncouragement();
	}

	public function getFlammability() : int{
		return $this->getBlock()->getFlammability();
	}

	public function burnsForever() : bool{
		return $this->getBlock()->burnsForever();
	}

	public function isFlammable() : bool{
		return $this->getBlock()->isFlammable();
	}

	public function onIncinerate() : void{
		$this->getBlock()->onIncinerate();
	}

	public function getAffectedBlocks() : array{
		return $this->getBlock()->getAffectedBlocks();
	}

	public function onEntityCollide(Entity $entity) : void{
		$this->getBlock()->onEntityCollide($entity);
	}

	public function getCollisionBoxes() : array{
		return $this->getBlock()->getCollisionBoxes();
	}

	public function getBoundingBox() : ?AxisAlignedBB{
		return $this->getBlock()->getBoundingBox();
	}

	public function clearCaches() : void{
		$this->getBlock()->clearCaches();
	}
}