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
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\math\AxisAlignedBB;

class EndPortalFrame extends Solid{

	protected $id = self::END_PORTAL_FRAME;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getLightLevel() : int{
		return 1;
	}

	public function getName() : string{
		return "End Portal Frame";
	}

	public function getHardness() : float{
		return -1;
	}

	public function getBlastResistance() : float{
		return 18000000;
	}

	public function isBreakable(Item $item) : bool{
		return false;
	}

	protected function recalculateBoundingBox() : ?AxisAlignedBB{

		return new AxisAlignedBB(
			$this->x,
			$this->y,
			$this->z,
			$this->x + 1,
			$this->y + (($this->getDamage() & 0x04) > 0 ? 1 : 0.8125),
			$this->z + 1
		);
	}
	
	public function place(Item $item, Block $block, Block $target, int $face, Vector3 $facePos, Player $player = null): bool{
		$faces = [
			0 => 3,
			1 => 0,
			2 => 1,
			3 => 2,
		];
		$this->meta = $faces[$player instanceof Player ? $player->getDirection() : 0];
		$this->getLevel()->setBlock($block, $this, true, true);

		return true;
	}

	public function onActivate(Item $item, Player $player = null): bool{
		if(($this->getDamage() & 0x04) === 0 && $player instanceof Player && $item->getId() === Item::ENDER_EYE){
			$this->setDamage($this->getDamage() + 4);
			$this->getLevel()->setBlock($this, $this, true, true);
			return true;
		}
		return false;
	}

	public function isValidPortal(): array{
		return [
			new Vector3(0, 0, 0),
			new Vector3(0, 0, 0),
			new Vector3(0, 0, 0),
			new Vector3(0, 0, 0),
		];
	}
	
	private function createPortal(array $corners = null): bool{
		if($corners === null){
			return false;
		}
		$x1 = min($corners[0][0], $corners[1][0]);
		$x2 = max($corners[0][0], $corners[1][0]);
		$z1 = min($corners[0][1], $corners[1][1]);
		$z2 = max($corners[0][1], $corners[1][1]);
		$y = $corners[2];
		for($curX = $x1; $curX <= $x2; $curX++){
			for($curZ = $z1; $curZ <= $z2; $curZ++){
				$this->getLevel()->setBlock($player->asVector3()->add(curX, $y, $curZ), Block::get(119));
			}
		}
		return true;
	}
}
