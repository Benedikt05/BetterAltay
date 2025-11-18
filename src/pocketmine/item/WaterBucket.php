<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockNames;
use pocketmine\block\Water;

class WaterBucket extends Bucket{

	public function __construct(){
		parent::__construct();
		$this->id = ItemNames::WATER_BUCKET;
		$this->name = "Water Bucket";
	}

	public function getContentBlock() : Block{
		return BlockFactory::get(BlockNames::WATER);
	}
}