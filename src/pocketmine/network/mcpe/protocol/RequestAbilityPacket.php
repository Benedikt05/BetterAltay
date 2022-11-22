<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use LogicException;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\RequestAbilityType;
use RuntimeException;
use function is_bool;
use function is_float;

class RequestAbilityPacket extends DataPacket{
	
	public const NETWORK_ID = ProtocolInfo::REQUEST_ABILITY_PACKET;
	
	public int $abilityId;
	public float|bool $abilityValue;
	
	protected function decodePayload(){
		$this->abilityId = $this->getVarInt();
		$valueType = $this->getByte();
		
		$boolValue = $this->getBool();
		$floatValue = $this->getLFloat();
		
		$this->abilityValue = match ($valueType) {
			RequestAbilityType::VALUE_TYPE_BOOL => $boolValue,
			RequestAbilityType::VALUE_TYPE_FLOAT => $floatValue,
			default => throw new RuntimeException("Unknown ability value type $valueType")
		};
	}
	
	protected function encodePayload(){
		$this->putVarInt($this->abilityId);
		
		[$valueType, $boolValue, $floatValue] = match (true) {
			is_bool($this->abilityValue) => [RequestAbilityType::VALUE_TYPE_BOOL, $this->abilityValue, 0.0],
			is_float($this->abilityValue) => [RequestAbilityType::VALUE_TYPE_FLOAT, false, $this->abilityValue],
			default => throw new LogicException("Unreachable")
		};
		$this->putByte($valueType);
		$this->putBool($boolValue);
		$this->putLFloat($floatValue);
	}
	
	public function handle(NetworkSession $session) : bool{
		return $session->handleRequestAbility($this);
	}
}