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

namespace pocketmine\tile;

use pocketmine\block\BlockIds;
use pocketmine\block\DoubleWoodenSlab;
use pocketmine\block\Fence;
use pocketmine\block\FenceGate;
use pocketmine\block\GlazedTerracotta;
use pocketmine\block\Log;
use pocketmine\block\Planks;
use pocketmine\block\SignPost;
use pocketmine\block\WoodenDoor;
use pocketmine\block\WoodenSlab;
use pocketmine\block\WoodenStairs;
use pocketmine\level\sound\NoteBlockSound;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;

class NoteBlock extends Spawnable{

	public const TAG_NOTE = "note";
	public const TAG_POWERED = "powered";

	/** @var int */
	protected $note = 0;
	/** @var bool */
	protected $powered = false;

	protected function readSaveData(CompoundTag $nbt) : void{
		$this->note = max(0, min(24, $nbt->getByte(self::TAG_NOTE, 0, true)));
		$this->powered = boolval($nbt->getByte(self::TAG_POWERED, 0));
	}

	public function setNote(int $note) : void{
		$this->note = $note;
	}

	public function getNote() : int{
		return $this->note;
	}

	public function changePitch() : void{
		$this->note = ($this->note + 1) % 25;
	}

	public function triggerNote() : bool{
		$up = $this->level->getBlock($this->getSide(Vector3::SIDE_UP));
		if($up->getId() === BlockIds::AIR){
			$below = $this->level->getBlock($this->getSide(Vector3::SIDE_DOWN));
			$instrument = NoteBlockSound::INSTRUMENT_PIANO;

			if ($below instanceof Log ||
				$below instanceof Planks ||
				$below instanceof WoodenStairs ||
				$below instanceof WoodenSlab ||
				$below instanceof WoodenDoor ||
				$below instanceof DoubleWoodenSlab ||
				$below instanceof SignPost ||
				$below instanceof Fence ||
				$below instanceof FenceGate
			){
				$instrument = NoteBlockSound::INSTRUMENT_BASS;
			}

			if ($below instanceof GlazedTerracotta) {
				$instrument = NoteBlockSound::INSTRUMENT_BASS_DRUM;
			}

			switch($below->getId()){ // TODO: implement block materials
				case BlockIds::BOOKSHELF:
				case BlockIds::CHEST:
				case BlockIds::CRAFTING_TABLE:
				case BlockIds::NOTEBLOCK:
					$instrument = NoteBlockSound::INSTRUMENT_BASS;
					break;
				case BlockIds::SAND:
				case BlockIds::SOUL_SAND:
					$instrument = NoteBlockSound::INSTRUMENT_TABOUR;
					break;
				case BlockIds::GLASS:
				case BlockIds::GLASS_PANE:
					$instrument = NoteBlockSound::INSTRUMENT_CLICK;
					break;
				case BlockIds::STONE:
				case BlockIds::COBBLESTONE:
				case BlockIds::SANDSTONE:
				case BlockIds::MOSSY_STONE_BRICKS:
				case BlockIds::BRICK_BLOCK:
				case BlockIds::STONE_BRICKS:
				case BlockIds::NETHER_BRICK:
				case BlockIds::QUARTZ_BLOCK:
				case BlockIds::STONE_BRICK_SLAB:
				case BlockIds::STONE_STAIRS:
				case BlockIds::BRICK_STAIRS:
				case BlockIds::STONE_BRICK_STAIRS:
				case BlockIds::NETHER_BRICK_STAIRS:
				case BlockIds::SANDSTONE_STAIRS:
				case BlockIds::QUARTZ_STAIRS:
				case BlockIds::COBBLESTONE_WALL:
				case BlockIds::NETHER_BRICK_FENCE:
				case BlockIds::BEDROCK:
				case BlockIds::GOLD_ORE:
				case BlockIds::IRON_ORE:
				case BlockIds::COAL_ORE:
				case BlockIds::LAPIS_ORE:
				case BlockIds::DIAMOND_ORE:
				case BlockIds::REDSTONE_ORE:
				case BlockIds::LIT_REDSTONE_ORE:
				case BlockIds::EMERALD_ORE:
				case BlockIds::FURNACE:
				case BlockIds::LIT_FURNACE:
				case BlockIds::BLAST_FURNACE:
				case BlockIds::LIT_BLAST_FURNACE:
				case BlockIds::OBSIDIAN:
				case BlockIds::MOB_SPAWNER:
				case BlockIds::NETHERRACK:
				case BlockIds::ENCHANTING_TABLE:
				case BlockIds::END_STONE:
				case BlockIds::COAL_BLOCK:
					$instrument = NoteBlockSound::INSTRUMENT_BASS_DRUM;
					break;
			}

			$this->level->addSound(new NoteBlockSound($this, $instrument, $this->note));

			return true;
		}
		return false;
	}

	public function setPowered(bool $value) : void{
		$this->powered = $value;
	}

	public function isPowered() : bool{
		return $this->powered;
	}

	public function getDefaultName() : string{
		return "NoteBlock";
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$nbt->setByte(self::TAG_NOTE, $this->note, true);
		$nbt->setByte(self::TAG_POWERED, intval($this->powered));
	}

	public function addAdditionalSpawnData(CompoundTag $nbt) : void{

	}
}