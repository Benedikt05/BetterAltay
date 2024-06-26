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

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkSession;

class CorrectPlayerMovePredictionPacket extends DataPacket/* implements ClientboundPacket*/
{
	public const NETWORK_ID = ProtocolInfo::CORRECT_PLAYER_MOVE_PREDICTION_PACKET;

	public const PREDICTION_TYPE_VEHICLE = 0;
	public const PREDICTION_TYPE_PLAYER = 1;

	private int $predictionType;
	private Vector3 $position;
	private Vector3 $delta;
	private bool $onGround;
	private int $tick;


	public static function create(int $predictionType, Vector3 $position, Vector3 $delta, bool $onGround, int $tick) : self{
		$result = new self;
		$result->predictionType = $predictionType;
		$result->position = $position;
		$result->delta = $delta;
		$result->onGround = $onGround;
		$result->tick = $tick;
		return $result;
	}

	public function getPredictionType() : int{ return $this->predictionType; }

	public function getPosition() : Vector3{ return $this->position; }

	public function getDelta() : Vector3{ return $this->delta; }

	public function isOnGround() : bool{ return $this->onGround; }

	public function getTick() : int{ return $this->tick; }

	protected function decodePayload() : void{
		$this->predictionType = $this->getByte();
		$this->position = $this->getVector3();
		$this->delta = $this->getVector3();
		$this->onGround = $this->getBool();
		$this->tick = $this->getUnsignedVarLong();
	}

	protected function encodePayload() : void{
		$this->putByte($this->predictionType);
		$this->putVector3($this->position);
		$this->putVector3($this->delta);
		$this->putBool($this->onGround);
		$this->putUnsignedVarLong($this->tick);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCorrectPlayerMovePrediction($this);
	}
}
