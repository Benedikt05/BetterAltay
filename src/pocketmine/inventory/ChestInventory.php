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

use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\player\Player;
use pocketmine\tile\Chest;
use function count;

class ChestInventory extends ContainerInventory{

	/** @var Chest */
	protected $holder;

	public function __construct(Chest $tile){
		parent::__construct($tile);
	}

	public function getNetworkType() : int{
		return WindowTypes::CONTAINER;
	}

	public function getName() : string{
		return "Chest";
	}

	public function getDefaultSize() : int{
		return 27;
	}

	/**
	 * This override is here for documentation and code completion purposes only.
	 * @return Chest|Position
	 */
	public function getHolder(){
		return $this->holder;
	}

	protected function getOpenSound() : int{
		return LevelSoundEventPacket::SOUND_CHEST_OPEN;
	}

	protected function getCloseSound() : int{
		return LevelSoundEventPacket::SOUND_CHEST_CLOSED;
	}

	public function onOpen(Player $who) : void{
		parent::onOpen($who);

		if(count($this->getViewers()) === 1 and $this->getHolder()->isValid()){
			//TODO: this crap really shouldn't be managed by the inventory
			$this->broadcastBlockEventPacket(true);
			$this->getHolder()->getLevelNonNull()->broadcastLevelSoundEvent($this->getHolder()->add(0.5, 0.5, 0.5), $this->getOpenSound());
		}
	}

	public function onClose(Player $who) : void{
		if(count($this->getViewers()) === 1 and $this->getHolder()->isValid()){
			//TODO: this crap really shouldn't be managed by the inventory
			$this->broadcastBlockEventPacket(false);
			$this->getHolder()->getLevelNonNull()->broadcastLevelSoundEvent($this->getHolder()->add(0.5, 0.5, 0.5), $this->getCloseSound());
		}
		parent::onClose($who);
	}

	protected function broadcastBlockEventPacket(bool $isOpen) : void{
		$holder = $this->getHolder();

		$pk = new BlockEventPacket();
		$pk->x = (int) $holder->x;
		$pk->y = (int) $holder->y;
		$pk->z = (int) $holder->z;
		$pk->eventType = 1; //it's always 1 for a chest
		$pk->eventData = $isOpen ? 1 : 0;
		$holder->getLevelNonNull()->broadcastPacketToViewers($holder, $pk);
	}
}