<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types\entityProperty;

class FloatEntityProperty implements EntityProperty{

	private int $index;
	private float $value;

	public function __construct(int $index, float $value) {
		$this->index = $index;
		$this->value = $value;
	}

	public function getIndex() : int{
		return $this->index;
	}

	public function getValue() : float{
		return $this->value;
	}
}