<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use LogicException;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkSession;

class CorrectPlayerMovePredictionPacket extends DataPacket/* implements ClientboundPacket*/
{
	public const NETWORK_ID = ProtocolInfo::CORRECT_PLAYER_MOVE_PREDICTION_PACKET;

	public const PREDICTION_TYPE_PLAYER = 0;
	public const PREDICTION_TYPE_VEHICLE = 1;

	private int $predictionType;
	private Vector3 $position;
	private Vector3 $delta;
	private bool $onGround;
	private int $tick;
	private ?Vector2 $vehicleRotation;
	private ?float $vehicleAngularVelocity;


	public static function create(int $predictionType, Vector3 $position, Vector3 $delta, bool $onGround, int $tick, ?Vector2 $vehicleRotation, ?float $vehicleAngularVelocity) : self{
		$result = new self;
		$result->position = $position;
		$result->delta = $delta;
		$result->onGround = $onGround;
		$result->tick = $tick;
		if($predictionType === self::PREDICTION_TYPE_VEHICLE && $vehicleRotation === null){
			throw new LogicException("CorrectPlayerMovePredictionPackets with type VEHICLE require a vehicleRotation to be provided");
		}
		$result->predictionType = $predictionType;
		$result->vehicleRotation = $vehicleRotation;
		$result->vehicleAngularVelocity = $vehicleAngularVelocity;
		return $result;
	}

	public function getPredictionType() : int{ return $this->predictionType; }

	public function getPosition() : Vector3{ return $this->position; }

	public function getDelta() : Vector3{ return $this->delta; }

	public function getVehicleRotation() : ?Vector2{ return $this->vehicleRotation; }

	public function getVehicleAngularVelocity() : ?float{ return $this->vehicleAngularVelocity; }

	public function isOnGround() : bool{ return $this->onGround; }

	public function getTick() : int{ return $this->tick; }

	protected function decodePayload() : void{
		$this->predictionType = $this->getUnsignedVarInt();
		$this->position = $this->getVector3();
		$this->delta = $this->getVector3();
		if($this->predictionType === self::PREDICTION_TYPE_VEHICLE){
			$this->vehicleRotation = $this->getVector2();
			$this->vehicleAngularVelocity = $this->getBool() ? $this->getFloat() : null;
		}
		$this->onGround = $this->getBool();
		$this->tick = $this->getUnsignedVarLong();
	}

	protected function encodePayload() : void{
		$this->putUnsignedVarInt($this->predictionType);//official docs have the wrong data type here
		$this->putVector3($this->position);
		$this->putVector3($this->delta);
		if($this->predictionType === self::PREDICTION_TYPE_VEHICLE){
			$this->putVector2($this->vehicleRotation);
			$this->putBool($hasVehicleAngularVelocity = $this->vehicleAngularVelocity !== null);
			if($hasVehicleAngularVelocity){
				$this->putFloat($this->vehicleAngularVelocity);
			}
		}
		$this->putBool($this->onGround);
		$this->putUnsignedVarLong($this->tick);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCorrectPlayerMovePrediction($this);
	}
}
