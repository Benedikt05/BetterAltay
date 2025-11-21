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

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\block\material\WoodType;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Boat extends Item{
	public function __construct(WoodType $material){
		parent::__construct("minecraft:" . $material->getType() . "_boat", 0, "Boat");
	}

	public function getFuelTime() : int{
		return 1200; //400 in PC
	}

	public function getMaxStackSize() : int{
		return 1;
	}

	public function onActivate(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector) : bool{
		$nbt = Entity::createBaseNBT($blockReplace->add(0.5, 0, 0.5), null, (int) round($player->getYaw() + 90) % 360, 0);
		$type = match ($this->id) {
			ItemIds::SPRUCE_BOAT => 1,
			ItemIds::BIRCH_BOAT => 2,
			ItemIds::JUNGLE_BOAT => 3,
			ItemIds::ACACIA_BOAT => 4,
			ItemIds::DARK_OAK_BOAT => 5,
			ItemIds::MANGROVE_BOAT => 6,
			ItemIds::BAMBOO_RAFT => 7,
			ItemIds::CHERRY_BOAT => 8,
			ItemIds::PALE_OAK_BOAT => 9,
			default => 0,
		};

		$nbt->setInt("Variant", $type);
		$entity = Entity::createEntity("Boat", $player->level, $nbt);
		$entity->spawnToAll();

		$this->pop();

		return true;
	}
}