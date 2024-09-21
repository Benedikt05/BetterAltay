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

namespace pocketmine\block;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\TieredTool;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class SnowLayer extends Flowable{

	protected $id = self::SNOW_LAYER;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getName() : string{
		return "Snow Layer";
	}

	public function canBeReplaced() : bool{
		return $this->meta < 7; //8 snow layers
	}

	public function getHardness() : float{
		return 0.1;
	}

	public function getToolType() : int{
		return BlockToolType::TYPE_SHOVEL;
	}

	public function getToolHarvestLevel() : int{
		return TieredTool::TIER_WOODEN;
	}

	private function canBeSupportedBy(Block $b) : bool{
		return $b->isSolid() or ($b->getId() === $this->getId() and $b->getDamage() === 7);
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		if($blockReplace->getId() === $this->getId() and $blockReplace->getDamage() < 7){
			$this->setDamage($blockReplace->getDamage() + 1);
		}
		if($this->canBeSupportedBy($blockReplace->getSide(Vector3::SIDE_DOWN))){
			$this->getLevelNonNull()->setBlock($blockReplace, $this, true);

			return true;
		}

		return false;
	}

	public function onNearbyBlockChange() : void{
        $vec3 = $this->asVector3();
        if(!$this->canBeSupportedBy($this->getSide(Vector3::SIDE_DOWN))){
            $this->getLevelNonNull()->setBlock($this, BlockFactory::get(Block::AIR), false, false);
            $nbt = Entity::createBaseNBT($vec3->add(0.5, 0, 0.5));
            $nbt->setInt("TileID", $this->getId());
            $nbt->setByte("Data", $this->getDamage());

            $fall = Entity::createEntity("FallingSand", $this->getLevelNonNull(), $nbt);

            if($fall !== null){
                $fall->spawnToAll();
            }
        }
    }

	public function ticksRandomly() : bool{
		return true;
	}

	public function onRandomTick() : void{
		if($this->level->getBlockLightAt($this->x, $this->y, $this->z) >= 12){
			$this->getLevelNonNull()->setBlock($this, BlockFactory::get(Block::AIR), false, false);
		}
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		return [
			ItemFactory::get(Item::SNOWBALL) //TODO: check layer count
		];
	}

	public function isAffectedBySilkTouch() : bool{
		return false;
	}
}