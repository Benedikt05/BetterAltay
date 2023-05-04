<?php

declare(strict_types=1);

namespace pocketmine\event\block;

use pocketmine\block\Block;
use pocketmine\event\Cancellable;
use pocketmine\Player;

class SignOpenEditEvent extends BlockEvent implements Cancellable{
	private Player $player;
	private bool $front;

	public function __construct(Block $block, Player $player, bool $front){
		parent::__construct($block);
		$this->player = $player;
		$this->front = $front;
	}

	/**
	 * @return Player
	 */
	public function getPlayer() : Player{
		return $this->player;
	}

	/**
	 * @return bool
	 */
	public function isFront() : bool{
		return $this->front;
	}
}