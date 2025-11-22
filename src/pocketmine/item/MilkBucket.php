<?php

declare(strict_types=1);

namespace pocketmine\item;

class MilkBucket extends Bucket{

	public function __construct(){
		parent::__construct();
		$this->id = self::MILK_BUCKET;
		$this->name = "Milk Bucket";
	}
}