<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;

class MilkBucket extends Bucket{

	public function __construct(){
		parent::__construct();
		$this->id = self::MILK_BUCKET;
		$this->name = "Milk Bucket";
	}
}