<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types\entityProperty;

class IntEntityProperty implements EntityProperty{

	private int $index;
	private int $value;

	public function __construct(int $index, int $value) {
		$this->index = $index;
		$this->value = $value;
	}

	public function getIndex() : int{
		return $this->index;
	}

	public function getValue() : int{
		return $this->value;
	}
}