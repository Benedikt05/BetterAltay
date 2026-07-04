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

namespace pocketmine\network\mcpe\protocol\types\inventory\stackrequest;

use pocketmine\network\mcpe\NetworkBinaryStream;

/**
 * This action precedes a "take" or "place" action involving the "created item" magic slot. It indicates that the
 * "created item" output slot now contains output N of a previously specified crafting recipe.
 * This is only used with crafting recipes that have multiple outputs. For recipes with single outputs, it's assumed
 * that the content of the "created item" slot is the only output.
 *
 * @see ContainerUIIds::CREATED_OUTPUT
 * @see UIInventorySlotOffset::CREATED_ITEM_OUTPUT
 */
final class CraftingCreateSpecificResultStackRequestAction extends ItemStackRequestAction{

	public function __construct(private int $resultIndex
	){
	}

	public function getResultIndex() : int{ return $this->resultIndex; }

	public static function getTypeId() : int{ return ItemStackRequestActionType::CRAFTING_CREATE_SPECIFIC_RESULT; }

	public static function read(NetworkBinaryStream $in) : self{
		$slot = $in->getByte();
		return new self($slot);
	}

	public function write(NetworkBinaryStream $out) : void{
		$out->putByte($this->resultIndex);
	}
}
