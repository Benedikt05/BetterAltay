<?php

declare(strict_types=1);

namespace pocketmine\event\player;

use pocketmine\event\Cancellable;
use pocketmine\Player;

class PlayerToggleCrawlEvent extends PlayerEvent implements Cancellable{

	protected bool $isCrawling;

	public function __construct(Player $player, bool $isCrawling){
		$this->player = $player;
		$this->isCrawling = $isCrawling;
	}

	public function isCrawling() : bool{
		return $this->isCrawling;
	}

}
