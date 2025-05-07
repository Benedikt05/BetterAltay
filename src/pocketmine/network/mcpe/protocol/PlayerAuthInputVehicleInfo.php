<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pocketmine\network\mcpe\NetworkBinaryStream;

final class PlayerAuthInputVehicleInfo{

	public function __construct(
		private float $vehicleRotationX,
		private float $vehicleRotationZ,
		private int $predictedVehicleActorUniqueId
	){}

	public function getVehicleRotationX() : float{ return $this->vehicleRotationX; }

	public function getVehicleRotationZ() : float{ return $this->vehicleRotationZ; }

	public function getPredictedVehicleActorUniqueId() : int{ return $this->predictedVehicleActorUniqueId; }

	public static function read(NetworkBinaryStream $in) : self{
		$vehicleRotationX = $in->getLFloat();
		$vehicleRotationZ = $in->getLFloat();
		$predictedVehicleActorUniqueId = $in->getEntityUniqueId();

		return new self($vehicleRotationX, $vehicleRotationZ, $predictedVehicleActorUniqueId);
	}

	public function write(NetworkBinaryStream $out) : void{
		$out->putLFloat($this->vehicleRotationX);
		$out->putLFloat($this->vehicleRotationZ);
		$out->putEntityUniqueId($this->predictedVehicleActorUniqueId);
	}
}
