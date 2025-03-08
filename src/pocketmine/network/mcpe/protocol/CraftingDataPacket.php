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

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\inventory\FurnaceRecipe;
use pocketmine\inventory\ShapedRecipe;
use pocketmine\inventory\ShapelessRecipe;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\convert\NetworkItemMapping;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\MaterialReducerRecipe;
use pocketmine\network\mcpe\protocol\types\MaterialReducerRecipeOutput;
use pocketmine\network\mcpe\protocol\types\PotionContainerChangeRecipe;
use pocketmine\network\mcpe\protocol\types\PotionTypeRecipe;
use pocketmine\utils\Binary;
use UnexpectedValueException;
use function count;
use function str_repeat;

#ifndef COMPILE

#endif

class CraftingDataPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CRAFTING_DATA_PACKET;

	public const ENTRY_SHAPELESS = 0;
	public const ENTRY_SHAPED = 1;
	public const ENTRY_FURNACE = 2;
	public const ENTRY_FURNACE_DATA = 3;
	public const ENTRY_MULTI = 4; //TODO
	public const ENTRY_SHULKER_BOX = 5; //TODO
	public const ENTRY_SHAPELESS_CHEMISTRY = 6; //TODO
	public const ENTRY_SHAPED_CHEMISTRY = 7; //TODO
	public const ENTRY_SMITHING_TRANSFORM = 8; //TODO
	public const ENTRY_SMITHING_TRIM = 9; //TODO

	/** @var object[] */
	public array $entries = [];
	/** @var PotionTypeRecipe[] */
	public array $potionTypeRecipes = [];
	/** @var PotionContainerChangeRecipe[] */
	public array $potionContainerRecipes = [];
	/** @var MaterialReducerRecipe[] */
	public array $materialReducerRecipes = [];
	public bool $cleanRecipes = false;

	/** @var mixed[][] */
	public array $decodedEntries = [];

	public function clean(){
		$this->entries = [];
		$this->decodedEntries = [];
		return parent::clean();
	}

	protected function decodePayload(){
		$this->decodedEntries = [];
		$recipeCount = $this->getUnsignedVarInt();
		for($i = 0; $i < $recipeCount; ++$i){
			$entry = [];
			$entry["type"] = $recipeType = $this->getVarInt();

			switch($recipeType){
				case self::ENTRY_SHAPELESS:
				case self::ENTRY_SHULKER_BOX:
				case self::ENTRY_SHAPELESS_CHEMISTRY:
					$entry["recipe_id"] = $this->getString();
					$ingredientCount = $this->getUnsignedVarInt();
					$entry["input"] = [];
					for($j = 0; $j < $ingredientCount; ++$j){
						$entry["input"][] = $in = $this->getRecipeIngredient();
						$in->setCount(1); //TODO HACK: they send a useless count field which breaks the PM crafting system because it isn't always 1
					}
					$resultCount = $this->getUnsignedVarInt();
					$entry["output"] = [];
					for($k = 0; $k < $resultCount; ++$k){
						$entry["output"][] = $this->getItemStackWithoutStackId();
					}
					$entry["uuid"] = $this->getUUID()->toString();
					$entry["block"] = $this->getString();
					$entry["priority"] = $this->getVarInt();
					$entry["net_id"] = $this->getUnsignedVarInt();

					break;
				case self::ENTRY_SHAPED:
				case self::ENTRY_SHAPED_CHEMISTRY:
					$entry["recipe_id"] = $this->getString();
					$entry["width"] = $this->getVarInt();
					$entry["height"] = $this->getVarInt();
					$count = $entry["width"] * $entry["height"];
					$entry["input"] = [];
					for($j = 0; $j < $count; ++$j){
						$entry["input"][] = $in = $this->getRecipeIngredient();
						$in->setCount(1); //TODO HACK: they send a useless count field which breaks the PM crafting system
					}
					$resultCount = $this->getUnsignedVarInt();
					$entry["output"] = [];
					for($k = 0; $k < $resultCount; ++$k){
						$entry["output"][] = $this->getItemStackWithoutStackId();
					}
					$entry["uuid"] = $this->getUUID()->toString();
					$entry["block"] = $this->getString();
					$entry["priority"] = $this->getVarInt();
					$entry["net_id"] = $this->getUnsignedVarInt();

					break;
				case self::ENTRY_FURNACE:
				case self::ENTRY_FURNACE_DATA:
					$inputIdNet = $this->getVarInt();
					if($recipeType === self::ENTRY_FURNACE){
						[$inputId, $inputData] = NetworkItemMapping::getInstance()->fromNetworkIdWithWildcardHandling($inputIdNet, 0x7fff);
					}else{
						$inputMetaNet = $this->getVarInt();
						[$inputId, $inputData] = NetworkItemMapping::getInstance()->fromNetworkIdWithWildcardHandling($inputIdNet, $inputMetaNet);
					}
					$entry["input"] = ItemFactory::get($inputId, $inputData);
					$entry["output"] = $out = $this->getItemStackWithoutStackId();
					if($out->getDamage() === 0x7fff){
						$out->setDamage(0); //TODO HACK: some 1.12 furnace recipe outputs have wildcard damage values
					}
					$entry["block"] = $this->getString();

					break;
				case self::ENTRY_MULTI:
					$entry["uuid"] = $this->getUUID()->toString();
					$entry["net_id"] = $this->getUnsignedVarInt();
					break;
				default:
					throw new UnexpectedValueException("Unhandled recipe type $recipeType!"); //do not continue attempting to decode
			}
			$this->decodedEntries[] = $entry;
		}
		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			$inputIdNet = $this->getVarInt();
			$inputMetaNet = $this->getVarInt();
			[$input, $inputMeta] = NetworkItemMapping::getInstance()->fromNetworkId($inputIdNet, $inputMetaNet);
			$ingredientIdNet = $this->getVarInt();
			$ingredientMetaNet = $this->getVarInt();
			[$ingredient, $ingredientMeta] = NetworkItemMapping::getInstance()->fromNetworkId($ingredientIdNet, $ingredientMetaNet);
			$outputIdNet = $this->getVarInt();
			$outputMetaNet = $this->getVarInt();
			[$output, $outputMeta] = NetworkItemMapping::getInstance()->fromNetworkId($outputIdNet, $outputMetaNet);
			$this->potionTypeRecipes[] = new PotionTypeRecipe($input, $inputMeta, $ingredient, $ingredientMeta, $output, $outputMeta);
		}
		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			//TODO: we discard inbound ID here, not safe because netID on its own might map to internalID+internalMeta for us
			$inputIdNet = $this->getVarInt();
			[$input,] = NetworkItemMapping::getInstance()->fromNetworkId($inputIdNet, 0);
			$ingredientIdNet = $this->getVarInt();
			[$ingredient,] = NetworkItemMapping::getInstance()->fromNetworkId($ingredientIdNet, 0);
			$outputIdNet = $this->getVarInt();
			[$output,] = NetworkItemMapping::getInstance()->fromNetworkId($outputIdNet, 0);
			$this->potionContainerRecipes[] = new PotionContainerChangeRecipe($input, $ingredient, $output);
		}
		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			$inputIdAndData = $this->getVarInt();
			[$inputId, $inputMeta] = [$inputIdAndData >> 16, $inputIdAndData & 0x7fff];
			$outputs = [];
			for($j = 0, $outputCount = $this->getUnsignedVarInt(); $j < $outputCount; ++$j){
				$outputItemId = $this->getVarInt();
				$outputItemCount = $this->getVarInt();
				$outputs[] = new MaterialReducerRecipeOutput($outputItemId, $outputItemCount);
			}
			$this->materialReducerRecipes[] = new MaterialReducerRecipe($inputId, $inputMeta, $outputs);
		}
		$this->cleanRecipes = $this->getBool();
	}

	/**
	 * @param object $entry
	 */
	private static function writeEntry($entry, NetworkBinaryStream $stream, int $pos) : int{
		if($entry instanceof ShapelessRecipe){
			return self::writeShapelessRecipe($entry, $stream, $pos);
		}elseif($entry instanceof ShapedRecipe){
			return self::writeShapedRecipe($entry, $stream, $pos);
		}elseif($entry instanceof FurnaceRecipe){
			return self::writeFurnaceRecipe($entry, $stream);
		}
		//TODO: add MultiRecipe

		return -1;
	}

	private static function writeShapelessRecipe(ShapelessRecipe $recipe, NetworkBinaryStream $stream, int $pos) : int{
		$stream->putString(Binary::writeInt($pos)); //some kind of recipe ID, doesn't matter what it is as long as it's unique
		$stream->putUnsignedVarInt($recipe->getIngredientCount());
		foreach($recipe->getIngredientList() as $item){
			$stream->putRecipeIngredient($item);
		}

		$results = $recipe->getResults();
		$stream->putUnsignedVarInt(count($results));
		foreach($results as $item){
			$stream->putItemStackWithoutStackId($item);
		}

		$stream->put(str_repeat("\x00", 16)); //Null UUID
		$stream->putString("crafting_table"); //TODO: blocktype (no prefix) (this might require internal API breaks)
		$stream->putVarInt(50); //TODO: priority
		$stream->putByte(1); //TODO: recipe unlocking requirement - always unlocked
		$stream->putUnsignedVarInt($pos); //TODO: ANOTHER recipe ID, only used on the network

		return CraftingDataPacket::ENTRY_SHAPELESS;
	}

	private static function writeShapedRecipe(ShapedRecipe $recipe, NetworkBinaryStream $stream, int $pos) : int{
		$stream->putString(Binary::writeInt($pos)); //some kind of recipe ID, doesn't matter what it is as long as it's unique
		$stream->putVarInt($recipe->getWidth());
		$stream->putVarInt($recipe->getHeight());

		for($z = 0; $z < $recipe->getHeight(); ++$z){
			for($x = 0; $x < $recipe->getWidth(); ++$x){
				$stream->putRecipeIngredient($recipe->getIngredient($x, $z));
			}
		}

		$results = $recipe->getResults();
		$stream->putUnsignedVarInt(count($results));
		foreach($results as $item){
			$stream->putItemStackWithoutStackId($item);
		}

		$stream->put(str_repeat("\x00", 16)); //Null UUID
		$stream->putString("crafting_table"); //TODO: blocktype (no prefix) (this might require internal API breaks)
		$stream->putVarInt(50); //TODO: priority
		$stream->putBool(true); //TODO: assume symmetry
		$stream->putByte(1); //TODO: recipe unlocking requirement - always unlocked
		$stream->putUnsignedVarInt($pos); //TODO: ANOTHER recipe ID, only used on the network

		return CraftingDataPacket::ENTRY_SHAPED;
	}

	private static function writeFurnaceRecipe(FurnaceRecipe $recipe, NetworkBinaryStream $stream) : int{
		$input = $recipe->getInput();
		if($input->hasAnyDamageValue()){
			[$netId,] = NetworkItemMapping::getInstance()->toNetworkId($input->getId(), 0);
			$netData = 0x7fff;
		}else{
			[$netId, $netData] = NetworkItemMapping::getInstance()->toNetworkId($input->getId(), $input->getDamage());
		}
		$stream->putVarInt($netId);
		$stream->putVarInt($netData);
		$stream->putItemStackWithoutStackId($recipe->getResult());
		$stream->putString("furnace"); //TODO: blocktype (no prefix) (this might require internal API breaks)
		return CraftingDataPacket::ENTRY_FURNACE_DATA;
	}

	/**
	 * @return void
	 */
	public function addShapelessRecipe(ShapelessRecipe $recipe){
		$this->entries[] = $recipe;
	}

	/**
	 * @return void
	 */
	public function addShapedRecipe(ShapedRecipe $recipe){
		$this->entries[] = $recipe;
	}

	/**
	 * @return void
	 */
	public function addFurnaceRecipe(FurnaceRecipe $recipe){
		$this->entries[] = $recipe;
	}

	protected function encodePayload(){
		$this->putUnsignedVarInt(count($this->entries));

		$writer = new NetworkBinaryStream();
		$counter = 0;
		foreach($this->entries as $d){
			$entryType = self::writeEntry($d, $writer, ++$counter);
			if($entryType >= 0){
				$this->putVarInt($entryType);
				$this->put($writer->getBuffer());
			}else{
				$this->putVarInt(-1);
			}

			$writer->reset();
		}
		$this->putUnsignedVarInt(count($this->potionTypeRecipes));
		foreach($this->potionTypeRecipes as $recipe){
			$this->putVarInt($recipe->getInputItemId());
			$this->putVarInt($recipe->getInputItemMeta());
			$this->putVarInt($recipe->getIngredientItemId());
			$this->putVarInt($recipe->getIngredientItemMeta());
			$this->putVarInt($recipe->getOutputItemId());
			$this->putVarInt($recipe->getOutputItemMeta());
		}
		$this->putUnsignedVarInt(count($this->potionContainerRecipes));
		foreach($this->potionContainerRecipes as $recipe){
			$this->putVarInt($recipe->getInputItemId());
			$this->putVarInt($recipe->getIngredientItemId());
			$this->putVarInt($recipe->getOutputItemId());
		}
		$this->putUnsignedVarInt(count($this->materialReducerRecipes));
		foreach($this->materialReducerRecipes as $recipe){
			$this->putVarInt(($recipe->getInputItemId() << 16) | $recipe->getInputItemMeta());
			$this->putUnsignedVarInt(count($recipe->getOutputs()));
			foreach($recipe->getOutputs() as $output){
				$this->putVarInt($output->getItemId());
				$this->putVarInt($output->getCount());
			}
		}

		$this->putBool($this->cleanRecipes);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCraftingData($this);
	}
}
