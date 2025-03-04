<?php

declare(strict_types=1);

namespace pocketmine\event\player;

use pocketmine\Player;

class PlayerGameplayUpdateEvent extends PlayerEvent{

	private int $graphicsMode;

	public function __construct(Player $player, int $graphicsMode){
		$this->player = $player;
		$this->graphicsMode = $graphicsMode;
	}

	/**
	 * @return int
	 */
	public function getGraphicsMode() : int{
		return $this->graphicsMode;
	}

}