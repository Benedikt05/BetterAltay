<?php

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\level\sound\GhastShootSound;
use pocketmine\math\Vector3;
use pocketmine\Player;

class FireCharge extends Item{

	public function onActivate(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector) : bool{
		if($player->isSpectator())return false;
		if($blockReplace->getId() === self::AIR){
			$level = $player->getLevelNonNull();
			$level->setBlock($blockReplace, BlockFactory::get(Block::FIRE), true);
			$level->addSound(new GhastShootSound($blockReplace->add(0.5, 0.5, 0.5)), $level->getPlayers());
			if(!$player->isCreative()){
				$this->pop();
			}
		}
		return true;
	}
}