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

namespace pocketmine\tile;

use pocketmine\inventory\ChestInventory;
use pocketmine\inventory\DoubleChestInventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;

class Chest extends Spawnable implements InventoryHolder, Container, Nameable{
	use NameableTrait {
		addAdditionalSpawnData as addNameSpawnData;
	}
	use ContainerTrait;

	public const TAG_PAIRX = "pairx";
	public const TAG_PAIRZ = "pairz";
	public const TAG_PAIR_LEAD = "pairlead";
	public const TAG_FINDABLE = "Findable";
	public const TAG_MOVABLE = "isMovable";

	/** @var ChestInventory */
	protected $inventory;
	/** @var DoubleChestInventory|null */
	protected $doubleInventory = null;

	/** @var int|null */
	private $pairX;
	/** @var int|null */
	private $pairZ;

	private $findable = false;
	private $isMovable = true;

	protected function readSaveData(CompoundTag $nbt) : void{
		if($nbt->hasTag(self::TAG_PAIRX, IntTag::class) and $nbt->hasTag(self::TAG_PAIRZ, IntTag::class)){
			$this->pairX = $nbt->getInt(self::TAG_PAIRX);
			$this->pairZ = $nbt->getInt(self::TAG_PAIRZ);
		}

		$this->findable = $nbt->getByte(self::TAG_FINDABLE, 0) === 1;
		$this->isMovable = $nbt->getByte(self::TAG_MOVABLE, 1) === 1;
		$this->loadName($nbt);

		$this->inventory = new ChestInventory($this);
		$this->loadItems($nbt);
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		if($this->isPaired()){
			$nbt->setInt(self::TAG_PAIRX, $this->pairX);
			$nbt->setInt(self::TAG_PAIRZ, $this->pairZ);
		}

		$nbt->setByte(self::TAG_FINDABLE, $this->findable ? 1 : 0);
		$nbt->setByte(self::TAG_MOVABLE, $this->isMovable ? 1 : 0);
		$this->saveName($nbt);
		$this->saveItems($nbt);
	}

	public function getCleanedNBT() : ?CompoundTag{
		$tag = parent::getCleanedNBT();
		if($tag !== null){
			//TODO: replace this with a purpose flag on writeSaveData()
			$tag->removeTag(self::TAG_PAIRX, self::TAG_PAIRZ);
		}
		return $tag;
	}

	public function close() : void{
		if(!$this->closed){
			$this->inventory->removeAllViewers(true);

			if($this->doubleInventory !== null){
				if($this->isPaired() and $this->level->isChunkLoaded($this->pairX >> 4, $this->pairZ >> 4)){
					$this->doubleInventory->removeAllViewers(true);
					$this->doubleInventory->invalidate();
					if(($pair = $this->getPair()) !== null){
						$pair->doubleInventory = null;
					}
				}
				$this->doubleInventory = null;
			}

			$this->inventory = null;

			parent::close();
		}
	}

	/**
	 * @return ChestInventory|DoubleChestInventory
	 */
	public function getInventory(){
		if($this->isPaired() and $this->doubleInventory === null){
			$this->checkPairing();
		}
		return $this->doubleInventory instanceof DoubleChestInventory ? $this->doubleInventory : $this->inventory;
	}

	/**
	 * @return ChestInventory
	 */
	public function getRealInventory(){
		return $this->inventory;
	}

	/**
	 * @return void
	 */
	protected function checkPairing(){
		if($this->isPaired() and !$this->getLevelNonNull()->isInLoadedTerrain(new Vector3($this->pairX, $this->y, $this->pairZ))){
			//paired to a tile in an unloaded chunk
			$this->doubleInventory = null;

		}elseif(($pair = $this->getPair()) instanceof Chest){
			if(!$pair->isPaired()){
				$pair->createPair($this);
				$pair->checkPairing();
			}
			if($this->doubleInventory === null){
				if($pair->doubleInventory !== null){
					$this->doubleInventory = $pair->doubleInventory;
				}else{
					if(($pair->x + ($pair->z << 15)) > ($this->x + ($this->z << 15))){ //Order them correctly
						$this->doubleInventory = $pair->doubleInventory = new DoubleChestInventory($pair, $this);
					}else{
						$this->doubleInventory = $pair->doubleInventory = new DoubleChestInventory($this, $pair);
					}
				}
			}
		}else{
			$this->doubleInventory = null;
			$this->pairX = $this->pairZ = null;
		}
	}

	public function getDefaultName() : string{
		return "Chest";
	}

	/**
	 * @return bool
	 */
	public function isPaired(){
		return $this->pairX !== null and $this->pairZ !== null;
	}

	public function getPair() : ?Chest{
		if($this->isPaired()){
			$tile = $this->getLevelNonNull()->getTileAt($this->pairX, $this->y, $this->pairZ);
			if($tile instanceof Chest){
				return $tile;
			}
		}

		return null;
	}

	/**
	 * @return bool
	 */
	public function pairWith(Chest $tile){
		if($this->isPaired() or $tile->isPaired()){
			return false;
		}

		$this->createPair($tile);

		$this->onChanged();
		$tile->onChanged();
		$this->checkPairing();

		return true;
	}

	private function createPair(Chest $tile) : void{
		$this->pairX = $tile->x;
		$this->pairZ = $tile->z;

		$tile->pairX = $this->x;
		$tile->pairZ = $this->z;
	}

	/**
	 * @return bool
	 */
	public function unpair(){
		if(!$this->isPaired()){
			return false;
		}

		$tile = $this->getPair();
		$this->pairX = $this->pairZ = null;

		$this->onChanged();

		if($tile instanceof Chest){
			$tile->pairX = $tile->pairZ = null;
			$tile->checkPairing();
			$tile->onChanged();
		}
		$this->checkPairing();

		return true;
	}

	protected function addAdditionalSpawnData(CompoundTag $nbt) : void{
		if($this->isPaired()){
			$nbt->setInt(self::TAG_PAIRX, $this->pairX);
			$nbt->setInt(self::TAG_PAIRZ, $this->pairZ);
		}

		$nbt->setByte(self::TAG_FINDABLE, $this->findable ? 1 : 0);
		$nbt->setByte(self::TAG_MOVABLE, $this->isMovable ? 1 : 0);

		$this->addNameSpawnData($nbt);
	}

	protected static function createAdditionalNBT(CompoundTag $nbt, Vector3 $pos, ?int $face = null, ?Item $item = null, ?Player $player = null) : void{
		parent::createAdditionalNBT($nbt, $pos, $face, $item, $player);
		$nbt->setByte(self::TAG_FINDABLE, 0);
		$nbt->setByte(self::TAG_MOVABLE, 1);
	}
}
