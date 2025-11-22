<?php

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;

class WaterBucket extends Bucket{

	public function __construct(){
		parent::__construct();
		$this->id = self::WATER_BUCKET;
		$this->name = "Water Bucket";
	}

	public function getContentBlock() : Block{
		return BlockFactory::get(BlockIds::WATER);
	}
}