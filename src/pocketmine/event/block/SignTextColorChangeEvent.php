<?php

namespace pocketmine\event\block;

use pocketmine\block\Block;
use pocketmine\event\Cancellable;
use pocketmine\utils\Color;

class SignTextColorChangeEvent extends BlockEvent implements Cancellable{

	private Color $color;

	public function __construct(Block $block, Color $color){
		parent::__construct($block);

		$this->color = $color;
	}

	/**
	 * @return Color
	 */
	public function getColor() : Color{
		return $this->color;
	}

	/**
	 * @param Color $color
	 */
	public function setColor(Color $color) : void{
		$this->color = $color;
	}
}