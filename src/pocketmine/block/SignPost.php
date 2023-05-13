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

use pocketmine\event\block\SignOpenEditEvent;
use pocketmine\event\block\SignTextColorChangeEvent;
use pocketmine\item\Dye;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\OpenSignPacket;
use pocketmine\Player;
use pocketmine\tile\Sign as TileSign;
use pocketmine\tile\Tile;
use pocketmine\utils\Color;
use function floor;

class SignPost extends Transparent{

	protected $id = self::SIGN_POST;

	protected $itemId = Item::SIGN;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getHardness() : float{
		return 1;
	}

	public function isSolid() : bool{
		return false;
	}

	public function getName() : string{
		return "Sign Post";
	}

	protected function recalculateBoundingBox() : ?AxisAlignedBB{
		return null;
	}

	protected function onOpenEditor(Player $player, Vector3 $vector3) : void{
		$ev = new SignOpenEditEvent($this, $player, true);
		$ev->call();

		if($ev->isCancelled()){
			return;
		}

		$pk = new OpenSignPacket();
		$pk->frontSide = true;
		$pk->x = $vector3->getX();
		$pk->y = $vector3->getY();
		$pk->z = $vector3->getZ();
		$player->sendDataPacket($pk);
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		$this->onOpenEditor($player, $blockReplace);

		if($face !== Vector3::SIDE_DOWN){

			if($face === Vector3::SIDE_UP){
				$this->meta = $player !== null ? (floor((($player->yaw + 180) * 16 / 360) + 0.5) & 0x0f) : 0;
				$this->getLevelNonNull()->setBlock($blockReplace, $this, true);
			}else{
				$this->meta = $face;
				$this->getLevelNonNull()->setBlock($blockReplace, BlockFactory::get(Block::WALL_SIGN, $this->meta), true);
			}

			$sign = Tile::createTile(Tile::SIGN, $this->getLevelNonNull(), TileSign::createNBT($this, $face, $item, $player));
			if($player !== null && $sign instanceof TileSign){
				$sign->setEditorEntityRuntimeId($player->getId());
			}

			return true;
		}

		return false;
	}

	public function onActivate(Item $item, Player $player = null) : bool{
		if(!$player instanceof Player){
			return false;
		}

		$tile = $this->getLevelNonNull()->getTile($this);
		if($tile instanceof TileSign){
			$color = $item instanceof Dye ? $item->getColorFromMeta() : match ($item->getId()){
				ItemIds::BONE => new Color(0xf0, 0xf0, 0xf0),
				BlockIds::LAPIS_ORE => new Color(0x3c, 0x44, 0xaa),
				BlockIds::COCOA => new Color(0x83, 0x54, 0x32),
				default => null
			};

			if(!is_null($color)){
				$ev = new SignTextColorChangeEvent($this, $color);
				$ev->call();

				if($ev->isCancelled()){
					return false;
				}

				$tile->setTextColor($ev->getColor());
				$this->level->setBlock($this, $this, true);
				return true;
			}else if($item->getId() == ItemIds::GLOWSTONE_DUST){
				$tile->setGlowing(true);
				$this->level->setBlock($this, $this, true);
				return true;
			}
		}

		$signPos = new Vector3($this->getX(), $this->getFloorY(), $this->getZ());
		if($player->distance($signPos) > 4){
			return false;
		}

		$this->onOpenEditor($player, $signPos);

		return true;
	}

	public function onNearbyBlockChange() : void{
		if($this->getSide(Vector3::SIDE_DOWN)->getId() === self::AIR){
			$this->getLevelNonNull()->useBreakOn($this);
		}
	}

	public function getToolType() : int{
		return BlockToolType::TYPE_AXE;
	}

	public function getVariantBitmask() : int{
		return 0;
	}
}
