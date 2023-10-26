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

class MotionPredictionHintsPacket extends DataPacket/* implements ClientboundPacket*/
{
	public const NETWORK_ID = ProtocolInfo::MOTION_PREDICTION_HINTS_PACKET;

	/** @var int */
	private $entityRuntimeId;
	/** @var Vector3 */
	private $motion;
	/** @var bool */
	private $onGround;

	public static function create(int $entityRuntimeId, Vector3 $motion, bool $onGround) : self{
		$result = new self;
		$result->entityRuntimeId = $entityRuntimeId;
		$result->motion = $motion;
		$result->onGround = $onGround;
		return $result;
	}

	public function getEntityRuntimeIdField() : int{ return $this->entityRuntimeId; } //TODO: rename this on PM4 (crap architecture, thanks shoghi)

	public function getMotion() : Vector3{ return $this->motion; }

	public function isOnGround() : bool{ return $this->onGround; }

	protected function decodePayload() : void{
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		$this->motion = $this->getVector3();
		$this->onGround = $this->getBool();
	}

	protected function encodePayload() : void{
		$this->putEntityRuntimeId($this->entityRuntimeId);
		$this->putVector3($this->motion);
		$this->putBool($this->onGround);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleMotionPredictionHints($this);
	}
}
