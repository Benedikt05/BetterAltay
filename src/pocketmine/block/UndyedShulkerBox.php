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

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\tile\ShulkerBox as TileShulkerBox;

class UndyedShulkerBox extends ShulkerBox{

	protected $id = self::UNDYED_SHULKER_BOX;

	public function getName() : string{
		return "Undyed Shulker Box";
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		$t = $this->getLevel()->getTile($this);
		if($t instanceof TileShulkerBox){
			$item = ItemFactory::get(Item::UNDYED_SHULKER_BOX, $this->getVariant());

			$blockData = new CompoundTag();
			$t->writeBlockData($blockData);

			$item->setCustomBlockData($blockData);

			return [$item];
		}

		return [];
	}

}