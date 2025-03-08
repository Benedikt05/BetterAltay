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

use pocketmine\item\Item;
use pocketmine\level\sound\DoorSound;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;

abstract class Door extends Transparent {

	private bool $open = false;
	private bool $hingeRight = false;
	private bool $topHalf = false;

	private int $direction = 0;

	public function isSolid() : bool {
		return false;
	}

	public function isPassable() : bool {
		return $this->open;
	}

	public function isHingeRight() : bool {
		return $this->hingeRight;
	}

	public function getDirection() : int {
		return $this->direction;
	}

	// todo: this should be from world not on scheduled update

	protected function recalculateBoundingBox(): ?AxisAlignedBB
	{
		$f = 0.1825;

		$faces = [
			0 => 3,
			1 => 4,
			2 => 2,
			3 => 5
		];

		$bb = new AxisAlignedBB(
			$this->x,
			$this->y,
			$this->z,
			$this->x + 1,
			$this->y + 2,
			$this->z + 1
		);

		$j = $this->open ? ($this->hingeRight ? $faces[$this->direction] : $faces[($this->direction + 2) % 4]) : $this->direction % 4;

		switch ($j) {
			case 0:
				if ($this->open) {
					if (!$this->hingeRight) {
						$bb->setBounds($this->x, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + $f);
					} else {
						$bb->setBounds($this->x, $this->y, $this->z + 1 - $f, $this->x + 1, $this->y + 1, $this->z + 1);
					}
				} else {
					$bb->setBounds($this->x, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + 1);
				}
				break;
			case 1:
				if ($this->open) {
					if (!$this->hingeRight) {
						$bb->setBounds($this->x + 1 - $f, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + 1);
					} else {
						$bb->setBounds($this->x, $this->y, $this->z, $this->x + $f, $this->y + 1, $this->z + 1);
					}
				} else {
					$bb->setBounds($this->x, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + $f);
				}
				break;
			case 2:
				if ($this->open) {
					if (!$this->hingeRight) {
						$bb->setBounds($this->x, $this->y, $this->z + 1 - $f, $this->x + 1, $this->y + 1, $this->z + 1);
					} else {
						$bb->setBounds($this->x, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + $f);
					}
				} else {
					$bb->setBounds($this->x + 1 - $f, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + 1);
				}
				break;
			case 3:
				if ($this->open) {
					if (!$this->hingeRight) {
						$bb->setBounds($this->x, $this->y, $this->z, $this->x + $f, $this->y + 1, $this->z + 1);
					} else {
						$bb->setBounds($this->x + 1 - $f, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + 1);
					}
				} else {
					$bb->setBounds($this->x, $this->y, $this->z + 1 - $f, $this->x + 1, $this->y + 1, $this->z + 1);
				}
				break;
		}

		return $bb;
	}

	public function onNearbyBlockChange(): void
	{
		if ($this->getSide(Vector3::SIDE_DOWN)->getId() === self::AIR) { //Replace with common break method
			$this->getLevelNonNull()->setBlock($this, BlockFactory::get(Block::AIR), false);
			if ($this->getSide(Vector3::SIDE_UP) instanceof Door) {
				$this->getLevelNonNull()->setBlock($this->getSide(Vector3::SIDE_UP), BlockFactory::get(Block::AIR), false);
			}
		}
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool
	{
		if ($face === Vector3::SIDE_UP) {
			$blockUp = $this->getSide(Vector3::SIDE_UP);
			$blockDown = $this->getSide(Vector3::SIDE_DOWN);
			if (!$blockUp->canBeReplaced() or $blockDown->isTransparent()) {
				return false;
			}
			$direction = $player instanceof Player ? $player->getDirection() : 0;
			$faces = [
				0 => 3,
				1 => 4,
				2 => 2,
				3 => 5
			];
			$next = $this->getSide($faces[($direction + 2) % 4]);
			$next2 = $this->getSide($faces[$direction]);
			if ($next->getId() === $this->getId() or (!$next2->isTransparent() and $next->isTransparent())) {
				$this->hingeRight = true;
			}

			$nextHalf = clone $this;
			$nextHalf->topHalf = true;

			$this->direction = $direction;
			$this->getLevelNonNull()->setBlock($blockReplace, $this, true, true);
			$this->getLevelNonNull()->setBlock($blockReplace, $nextHalf, true);
			return true;
		}

		return false;
	}

	public function getNextHalf(): ?Door
	{
		$result = $this->getSide($this->topHalf ? Vector3::SIDE_DOWN : Vector3::SIDE_UP);
		return $result instanceof Door ? $result : null;
	}

	public function onActivate(Item $item, Player $player = null): bool
	{
		$this->open = !$this->open;

		$nextHalf = $this->getNextHalf();
		if ($nextHalf !== null && $nextHalf->getId() === $this->getId()) {
			$nextHalf->open = $this->open;
			$this->level->setBlock($nextHalf, $nextHalf, true);
		}

		$this->level->setBlock($this, $this, true, true);
		$this->level->addSound(new DoorSound($this));

		return true;
	}

	public function getVariantBitmask(): int
	{
		return 0;
	}

	public function getDropsForCompatibleTool(Item $item): array
	{
		if (!$this->topHalf) { //bottom half only
			return parent::getDropsForCompatibleTool($item);
		}

		return [];
	}

	public function isAffectedBySilkTouch(): bool
	{
		return false;
	}

	public function getAffectedBlocks(): array
	{
		$nextHalf = $this->getNextHalf();
		if ($nextHalf !== null && $nextHalf->getId() === $this->getId()) {
			return [$this, $nextHalf];
		}

		return parent::getAffectedBlocks();
	}
}
