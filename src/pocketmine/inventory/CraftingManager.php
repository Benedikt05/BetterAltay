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

namespace pocketmine\inventory;

use Closure;
use Generator;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\convert\ItemTranslator;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\Server;
use pocketmine\timings\Timings;
use pocketmine\utils\AssumptionFailedError;
use function array_map;
use function file_get_contents;
use function is_array;
use function json_decode;
use function json_encode;
use function usort;
use const DIRECTORY_SEPARATOR;
use const pocketmine\RESOURCE_PATH;

class CraftingManager{
	/** @var ShapedRecipe[][] */
	protected $shapedRecipes = [];
	/** @var ShapelessRecipe[][] */
	protected $shapelessRecipes = [];
	/** @var FurnaceRecipe[] */
	protected $furnaceRecipes = [];

	/** @var BatchPacket|null */
	private $craftingDataCache;

	public function __construct(){
		$this->init();
	}

	public function init() : void{
		$data = json_decode(file_get_contents(RESOURCE_PATH . "vanilla" . DIRECTORY_SEPARATOR . "recipes.json"), true);
		if(!is_array($data)){
			throw new AssumptionFailedError("recipes.json root should contain a map of recipe types");
		}

		if (!isset($data["recipes"])){
			throw  new AssumptionFailedError("recipes.json must contain the required root key 'recipes'");
		}

		$itemDeserializerFunc = Closure::fromCallable([Item::class, 'jsonDeserialize']);

		foreach($data["recipes"] as $recipe){
			switch($recipe["type"]){
				case CraftingDataPacket::ENTRY_SHAPELESS:
					$this->readShapelessRecipe($recipe, $itemDeserializerFunc);
					break;
				case CraftingDataPacket::ENTRY_SHAPED:
					$this->readShapedRecipe($recipe, $itemDeserializerFunc);
					break;
				case CraftingDataPacket::ENTRY_FURNACE:
				case CraftingDataPacket::ENTRY_FURNACE_DATA:
					$this->readFurnaceRecipe($recipe, $itemDeserializerFunc);
					break;
			}
		}

		$this->buildCraftingDataCache();
	}

	private function readShapelessRecipe(array $recipe, Closure $itemDeserializerFunc) : void{
		if (isset($recipe["id"])){
			if (!ItemFactory::isRegistered($recipe["id"]) && !BlockFactory::isRegistered($recipe["id"])) {
				return;
			}
		}

		if($recipe["block"] !== "crafting_table"){ //TODO: filter others out for now to avoid breaking economics
			return;
		}

		$ingredients = [];
		foreach($recipe["input"] as $input){
			if ($input["type"] !== "default"){ //TODO: handle item_tag type
				continue;
			}

			$item = ItemFactory::get($input["itemId"], $input["auxValue"] === ItemTranslator::WILDCARD ? -1 : $input["auxValue"], $input["count"]);
			if ($item instanceof ItemBlock){
				if ($item->getBlock()->getId() === BlockIds::UNKNOWN) {
					continue;
				}
			}

			if($item->getName() === "Unknown"){
				continue;
			}

			$ingredients[] = $item;
		}

		$this->registerShapelessRecipe(new ShapelessRecipe(
			$ingredients,
			array_map($itemDeserializerFunc, $recipe["output"])
		));
	}

	private function readShapedRecipe(array $recipe, Closure $itemDeserializerFunc) : void{
		if (isset($recipe["id"])){
			if (!ItemFactory::isRegistered($recipe["id"]) && !BlockFactory::isRegistered($recipe["id"])) {
				return;
			}
		}

		if($recipe["block"] !== "crafting_table"){ //TODO: filter others out for now to avoid breaking economics
			return;
		}

		$ingredients = [];
		foreach($recipe["input"] as $char => $input){
			if ($input["type"] !== "default"){ //TODO: handle item_tag and complex_alias type.
				return;
			}

			$item = ItemFactory::get($input["itemId"], $input["auxValue"] === ItemTranslator::WILDCARD ? -1 : $input["auxValue"], $input["count"]);
			if ($item instanceof ItemBlock){
				if ($item->getBlock()->getId() === BlockIds::UNKNOWN) {
					return;
				}
			}

			if($item->getName() === "Unknown"){
				return;
			}

			$ingredients[$char] = $item;
		}

		$this->registerShapedRecipe(new ShapedRecipe(
			$recipe["shape"],
			$ingredients,
			array_map($itemDeserializerFunc, $recipe["output"])
		));
	}


	private function readFurnaceRecipe(array $recipe, Closure $itemDeserializerFunc) : void{
		if($recipe["block"] === "furnace" || $recipe["block"] === "blast_furnace"){ //TODO: filter others out for now to avoid breaking economics
			$this->registerFurnaceRecipe(new FurnaceRecipe(
					Item::jsonDeserialize($recipe["output"]),
					Item::jsonDeserialize($recipe["input"]))
			);
		}
	}

	/**
	 * Rebuilds the cached CraftingDataPacket.
	 */
	public function buildCraftingDataCache() : void{
		Timings::$craftingDataCacheRebuildTimer->startTiming();
		$pk = new CraftingDataPacket();
		$pk->cleanRecipes = true;

		foreach($this->shapelessRecipes as $list){
			foreach($list as $recipe){
				$pk->addShapelessRecipe($recipe);
			}
		}
		foreach($this->shapedRecipes as $list){
			foreach($list as $recipe){
				$pk->addShapedRecipe($recipe);
			}
		}

		foreach($this->furnaceRecipes as $recipe){
			$pk->addFurnaceRecipe($recipe);
		}

		$pk->encode();

		$batch = new BatchPacket();
		$batch->addPacket($pk);
		$batch->setCompressionLevel(Server::getInstance()->networkCompressionLevel);
		$batch->encode();

		$this->craftingDataCache = $batch;
		Timings::$craftingDataCacheRebuildTimer->stopTiming();
	}

	/**
	 * Returns a pre-compressed CraftingDataPacket for sending to players. Rebuilds the cache if it is not found.
	 */
	public function getCraftingDataPacket() : BatchPacket{
		if($this->craftingDataCache === null){
			$this->buildCraftingDataCache();
		}

		return $this->craftingDataCache;
	}

	/**
	 * Function used to arrange Shapeless Recipe ingredient lists into a consistent order.
	 *
	 * @return int
	 */
	public static function sort(Item $i1, Item $i2){
		//Use spaceship operator to compare each property, then try the next one if they are equivalent.
		($retval = $i1->getId() <=> $i2->getId()) === 0 && ($retval = $i1->getDamage() <=> $i2->getDamage()) === 0 && ($retval = $i1->getCount() <=> $i2->getCount()) === 0;

		return $retval;
	}

	/**
	 * @param Item[] $items
	 *
	 * @return Item[]
	 */
	private static function pack(array $items) : array{
		/** @var Item[] $result */
		$result = [];

		foreach($items as $i => $item){
			foreach($result as $otherItem){
				if($item->equals($otherItem)){
					$otherItem->setCount($otherItem->getCount() + $item->getCount());
					continue 2;
				}
			}

			//No matching item found
			$result[] = clone $item;
		}

		return $result;
	}

	/**
	 * @param Item[] $outputs
	 */
	private static function hashOutputs(array $outputs) : string{
		$outputs = self::pack($outputs);
		usort($outputs, [self::class, "sort"]);
		foreach($outputs as $o){
			//this reduces accuracy of hash, but it's necessary to deal with recipe book shift-clicking stupidity
			$o->setCount(1);
		}

		return json_encode($outputs);
	}

	/**
	 * @return ShapelessRecipe[][]
	 */
	public function getShapelessRecipes() : array{
		return $this->shapelessRecipes;
	}

	/**
	 * @return ShapedRecipe[][]
	 */
	public function getShapedRecipes() : array{
		return $this->shapedRecipes;
	}

	/**
	 * @return FurnaceRecipe[]
	 */
	public function getFurnaceRecipes() : array{
		return $this->furnaceRecipes;
	}

	public function registerShapedRecipe(ShapedRecipe $recipe) : void{
		$this->shapedRecipes[self::hashOutputs($recipe->getResults())][] = $recipe;

		$this->craftingDataCache = null;
	}

	public function registerShapelessRecipe(ShapelessRecipe $recipe) : void{
		$this->shapelessRecipes[self::hashOutputs($recipe->getResults())][] = $recipe;

		$this->craftingDataCache = null;
	}

	public function registerFurnaceRecipe(FurnaceRecipe $recipe) : void{
		$input = $recipe->getInput();
		$this->furnaceRecipes[$input->getId() . ":" . ($input->hasAnyDamageValue() ? "?" : $input->getDamage())] = $recipe;
		$this->craftingDataCache = null;
	}

	/**
	 * @param Item[] $outputs
	 */
	public function matchRecipe(CraftingGrid $grid, array $outputs) : ?CraftingRecipe{
		//TODO: try to match special recipes before anything else (first they need to be implemented!)

		$outputHash = self::hashOutputs($outputs);

		if(isset($this->shapedRecipes[$outputHash])){
			foreach($this->shapedRecipes[$outputHash] as $recipe){
				if($recipe->matchesCraftingGrid($grid)){
					return $recipe;
				}
			}
		}

		if(isset($this->shapelessRecipes[$outputHash])){
			foreach($this->shapelessRecipes[$outputHash] as $recipe){
				if($recipe->matchesCraftingGrid($grid)){
					return $recipe;
				}
			}
		}

		return null;
	}

	/**
	 * @param Item[] $outputs
	 *
	 * @return CraftingRecipe[]|Generator
	 * @phpstan-return Generator<int, CraftingRecipe, void, void>
	 */
	public function matchRecipeByOutputs(array $outputs) : Generator{
		//TODO: try to match special recipes before anything else (first they need to be implemented!)

		$outputHash = self::hashOutputs($outputs);

		if(isset($this->shapedRecipes[$outputHash])){
			foreach($this->shapedRecipes[$outputHash] as $recipe){
				yield $recipe;
			}
		}

		if(isset($this->shapelessRecipes[$outputHash])){
			foreach($this->shapelessRecipes[$outputHash] as $recipe){
				yield $recipe;
			}
		}
	}

	public function matchFurnaceRecipe(Item $input) : ?FurnaceRecipe{
		return $this->furnaceRecipes[$input->getId() . ":" . $input->getDamage()] ?? $this->furnaceRecipes[$input->getId() . ":?"] ?? null;
	}

	/**
	 * @deprecated
	 */
	public function registerRecipe(Recipe $recipe) : void{
		$recipe->registerToCraftingManager($this);
	}
}
