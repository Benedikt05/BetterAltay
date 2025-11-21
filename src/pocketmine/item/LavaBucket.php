<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\block\Lava;

class LavaBucket extends Bucket {

	public function __construct(){
		parent::__construct();
		$this->id = self::LAVA_BUCKET;
		$this->name = "Lava Bucket";
	}

	public function getContentBlock() : Block{
		return BlockFactory::get(BlockIds::LAVA);
	}
}