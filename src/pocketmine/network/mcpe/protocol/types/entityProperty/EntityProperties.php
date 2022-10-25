<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types\entityProperty;

use pocketmine\network\mcpe\protocol\DataPacket;
use function count;

class EntityProperties{

	/**
	 * @var IntEntityProperty[]
	 */
	private array $intEntityProperties;

	/**
	 * @var FloatEntityProperty[]
	 */
	private array $floatEntityProperties;

	public function __construct(array $intEntityProperties = [], array $floatEntityProperties = []) {
		$this->intEntityProperties = $intEntityProperties;
		$this->floatEntityProperties = $floatEntityProperties;
	}

	public function getFloatEntityProperties() : array{
		return $this->floatEntityProperties;
	}

	public function getIntEntityProperties() : array{
		return $this->intEntityProperties;
	}


	public function encode(DataPacket $packet) : void{
		$packet->putUnsignedVarInt(count($this->intEntityProperties));
		foreach($this->intEntityProperties as $property){
			$packet->putUnsignedVarInt($property->getIndex());
			$packet->putVarInt($property->getValue());
		}

		$packet->putUnsignedVarInt(count($this->floatEntityProperties));
		foreach($this->floatEntityProperties as $property){
			$packet->putUnsignedVarInt($property->getIndex());
			$packet->putLFloat($property->getValue());
		}
	}

	public static function readFromPacket(DataPacket $packet) : self{
		$intEntityProperties = [];
		$floatEntityProperties = [];

		for($i = 0, $count = $packet->getUnsignedVarInt(); $i < $count; ++$i){
			$intEntityProperties[] = new IntEntityProperty($packet->getUnsignedVarInt(), $packet->getVarInt());
		}

		for($i = 0, $count = $packet->getUnsignedVarInt(); $i < $count; ++$i){
			$floatEntityProperties[] = new FloatEntityProperty($packet->getUnsignedVarInt(), $packet->getLFloat());
		}

		return new self($intEntityProperties, $floatEntityProperties);
	}
}