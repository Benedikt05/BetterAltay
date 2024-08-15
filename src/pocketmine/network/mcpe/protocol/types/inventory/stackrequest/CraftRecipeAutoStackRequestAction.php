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
 * Tells that the current transaction crafted the specified recipe, using the recipe book. This is effectively the same
 * as the regular crafting result action.
 */
final class CraftRecipeAutoStackRequestAction extends ItemStackRequestAction{

	final public function __construct(
		private int $recipeId,
		private int $repetitions2,
		private int $repetitions,
		private array $ingredients
	){
	}

	public function getRecipeId() : int{ return $this->recipeId; }

	public function getRepetitions2() : int{ return $this->repetitions2; }

	public function getRepetitions() : int{ return $this->repetitions; }

	public function getIngredients() : array{ return $this->ingredients; }

	public static function getTypeId() : int{ return ItemStackRequestActionType::CRAFTING_RECIPE_AUTO; }

	public static function read(NetworkBinaryStream $in) : self{
		$recipeId = $in->readGenericTypeNetworkId();
		$repetitions2 = $in->getByte();
		$repetitions = $in->getByte();
		$ingredients = [];
		for($i = 0, $count = $in->getByte(); $i < $count; ++$i){
			$ingredients[] = $in->getRecipeIngredient();
		}
		return new self($recipeId, $repetitions2, $repetitions, $ingredients);
	}

	public function write(NetworkBinaryStream $out) : void{
		$out->writeGenericTypeNetworkId($this->recipeId);
		$out->putByte($this->repetitions2);
		$out->putByte($this->repetitions);
		$out->putByte(count($this->ingredients));
		foreach ($this->ingredients as $ingredient) {
			$out->putRecipeIngredient($ingredient);
		}
	}
}
