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

use pocketmine\item\TieredTool;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;

class CobblestoneWall extends Transparent{
	public const NONE_MOSSY_WALL = 0;
	public const MOSSY_WALL = 1;
	public const GRANITE_WALL = 2;
	public const DIORITE_WALL = 3;
	public const ANDESITE_WALL = 4;
	public const SANDSTONE_WALL = 5;
	public const BRICK_WALL = 6;
	public const STONE_BRICK_WALL = 7;
	public const MOSSY_STONE_BRICK_WALL = 8;
	public const NETHER_BRICK_WALL = 9;
	public const END_STONE_BRICK_WALL = 10;
	public const PRISMARINE_WALL = 11;
	public const RED_SANDSTONE_WALL = 12;
	public const RED_NETHER_BRICK_WALL = 13;

	protected $id = self::COBBLESTONE_WALL;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getToolType() : int{
		return BlockToolType::TYPE_PICKAXE;
	}

	public function getToolHarvestLevel() : int{
		return TieredTool::TIER_WOODEN;
	}

	public function getHardness() : float{
		return 2;
	}

	public function getName() : string{
		static $names = [
			self::NONE_MOSSY_WALL => "Cobblestone",
			self::MOSSY_WALL => "Mossy Cobblestone",
			self::GRANITE_WALL => "Granite",
			self::DIORITE_WALL => "Diorite",
			self::ANDESITE_WALL => "Andesite",
			self::SANDSTONE_WALL => "Sandstone",
			self::BRICK_WALL => "Brick",
			self::STONE_BRICK_WALL => "Stone Brick",
			self::MOSSY_STONE_BRICK_WALL => "Mossy Stone Brick",
			self::NETHER_BRICK_WALL => "Nether Brick",
			self::END_STONE_BRICK_WALL => "End Stone Brick",
			self::PRISMARINE_WALL => "Prismarine",
			self::RED_SANDSTONE_WALL => "Red Sandstone",
			self::RED_NETHER_BRICK_WALL => "Red Nether Brick"
		];
		return ($names[$this->getVariant()] ?? "Unknown") . " Wall";
	}

	protected function recalculateBoundingBox() : ?AxisAlignedBB{
		$width = 0.5 - 0.25 / 2;

		return new AxisAlignedBB(
			$this->x + ($this->canConnect($this->getSide(Vector3::SIDE_WEST)) ? 0 : $width),
			$this->y,
			$this->z + ($this->canConnect($this->getSide(Vector3::SIDE_NORTH)) ? 0 : $width),
			$this->x + 1 - ($this->canConnect($this->getSide(Vector3::SIDE_EAST)) ? 0 : $width),
			$this->y + 1.5,
			$this->z + 1 - ($this->canConnect($this->getSide(Vector3::SIDE_SOUTH)) ? 0 : $width)
		);
	}

	protected function recalculateCollisionBoxes() : array{
		$inset = 0.5 - 0.25 / 2;

		/** @var AxisAlignedBB[] $bbs */
		$bbs = [];

		$connectWest = $this->canConnect($this->getSide(Vector3::SIDE_WEST));
		$connectEast = $this->canConnect($this->getSide(Vector3::SIDE_EAST));

		if($connectWest or $connectEast){
			//X axis (west/east)
			$bbs[] = new AxisAlignedBB(
				$this->x + ($connectWest ? 0 : $inset),
				$this->y,
				$this->z + $inset,
				$this->x + 1 - ($connectEast ? 0 : $inset),
				$this->y + 1.5,
				$this->z + 1 - $inset
			);
		}

		$connectNorth = $this->canConnect($this->getSide(Vector3::SIDE_NORTH));
		$connectSouth = $this->canConnect($this->getSide(Vector3::SIDE_SOUTH));

		if($connectNorth or $connectSouth){
			//Z axis (north/south)
			$bbs[] = new AxisAlignedBB(
				$this->x + $inset,
				$this->y,
				$this->z + ($connectNorth ? 0 : $inset),
				$this->x + 1 - $inset,
				$this->y + 1.5,
				$this->z + 1 - ($connectSouth ? 0 : $inset)
			);
		}

		if(count($bbs) === 0){
			//centre post AABB (only needed if not connected on any axis - other BBs overlapping will do this if any connections are made)
			return [
				new AxisAlignedBB(
					$this->x + $inset,
					$this->y,
					$this->z + $inset,
					$this->x + 1 - $inset,
					$this->y + 1.5,
					$this->z + 1 - $inset
				)
			];
		}

		return $bbs;
	}

	/**
	 * @return bool
	 */
	public function canConnect(Block $block){
		return $block instanceof static or $block instanceof FenceGate or ($block->isSolid() and !$block->isTransparent());
	}
}
