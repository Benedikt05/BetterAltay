<?php

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\Player;

class ChestMinecart extends Item{

	public function getMaxStackSize(): int{
		return 1;
	}

	public function onActivate(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector): bool{
		if($blockClicked->getId() !== Block::RAIL){
			return false;
		}

		$nbt = Entity::createBaseNBT($blockReplace->add(0.5, 0, 0.5));
		$entity = Entity::createEntity("Minecart", $player->level, $nbt);
		$entity->getDataPropertyManager()->setInt(Entity::DATA_MINECART_DISPLAY_BLOCK, RuntimeBlockMapping::toStaticRuntimeId(Block::CHEST, 0));
		$entity->getDataPropertyManager()->setInt(Entity::DATA_MINECART_DISPLAY_OFFSET, 6);
		$entity->getDataPropertyManager()->setByte(Entity::DATA_MINECART_HAS_DISPLAY, 1);
		$entity->spawnToAll();

		$this->pop();

		return true;
	}
}