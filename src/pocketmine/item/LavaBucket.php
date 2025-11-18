<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockNames;
use pocketmine\block\Lava;

class LavaBucket extends Bucket {

	public function __construct(){
		parent::__construct();
		$this->id = ItemNames::LAVA_BUCKET;
		$this->name = "Lava Bucket";
	}

	public function getContentBlock() : Block{
		return BlockFactory::get(BlockNames::LAVA);
	}
}