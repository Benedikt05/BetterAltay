<?php

declare(strict_types=1);

namespace pocketmine\tile;

use pocketmine\inventory\CampfireInventory;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;

class Campfire extends Spawnable implements Container{
	use ContainerTrait;

	public const TAG_ITEM_TIME = "ItemTimes";

	/** @var CampfireInventory */
	protected $inventory;
	/** @var int[] */
	private array $itemTime = [];

	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
		$this->inventory = new CampfireInventory();
	}

	public function getInventory(){
		return $this->inventory;
	}

	public function getRealInventory(){
		return $this->inventory;
	}
	public function getItemTimes() : array{
		return $this->itemTime;
	}

	public function setItemTimes(array $itemTime) : void{
		$this->itemTime = $itemTime;
	}

	public function readSaveData(CompoundTag $nbt) : void{
		$this->loadItems($nbt);

		if(($tag = $nbt->getTag(self::TAG_ITEM_TIME)) !== null){
			/** @var IntTag $time */
			foreach($tag->getValue() as $slot => $time){
				$this->itemTime[$slot] = $time->getValue();
			}
		}
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$this->saveItems($nbt);

		$times = [];
		foreach($this->itemTime as $time){
			$times[] = new IntTag("", $time);
		}
		$nbt->setTag(new ListTag(self::TAG_ITEM_TIME, $times));
	}

	protected function addAdditionalSpawnData(CompoundTag $nbt) : void{
		foreach($this->getInventory()->getContents() as $slot => $item){
			$slot++;
			$nbt->setTag(new ListTag("Item" . $slot, $item->nbtSerialize()));
			$nbt->setInt("ItemTime" . $slot, $this->itemTime[$slot] ?? 0);
		}
	}
}