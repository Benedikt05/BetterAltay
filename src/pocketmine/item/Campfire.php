<?php

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\block\Campfire as CampfireBlock;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Campfire extends Item{

	public function __construct(){
		parent::__construct(Item::NORMAL_CAMPFIRE_ITEM, 0, "Campfire");
	}

	public function onActivate(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector) : bool{
		if($blockReplace->getId() === self::AIR){
			$level = $player->getLevelNonNull();
			$level->setBlock($blockReplace, new CampfireBlock(), true);
			$this->pop();
		}
		return true;
	}
}