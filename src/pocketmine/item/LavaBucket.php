<?php

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;

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