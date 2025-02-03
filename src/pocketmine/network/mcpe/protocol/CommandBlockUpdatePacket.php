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

use pocketmine\network\mcpe\NetworkSession;

class CommandBlockUpdatePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::COMMAND_BLOCK_UPDATE_PACKET;

	public bool $isBlock;

	public int $x;
	public int $y;
	public int $z;
	public int $commandBlockMode;
	public bool $isRedstoneMode;
	public bool $isConditional;

	public int $minecartEid;

	public string $command;
	public string $lastOutput;
	public string $name;
	public string $filteredName;

	public bool $shouldTrackOutput;
	public int $tickDelay;
	public bool $executeOnFirstTick;

	protected function decodePayload() : void{
		$this->isBlock = $this->getBool();

		if($this->isBlock){
			$this->getBlockPosition($this->x, $this->y, $this->z);
			$this->commandBlockMode = $this->getUnsignedVarInt();
			$this->isRedstoneMode = $this->getBool();
			$this->isConditional = $this->getBool();
		}else{
			//Minecart with command block
			$this->minecartEid = $this->getEntityRuntimeId();
		}

		$this->command = $this->getString();
		$this->lastOutput = $this->getString();
		$this->name = $this->getString();
		$this->filteredName = $this->getString();

		$this->shouldTrackOutput = $this->getBool();
		$this->tickDelay = $this->getLInt();
		$this->executeOnFirstTick = $this->getBool();
	}

	protected function encodePayload() : void{
		$this->putBool($this->isBlock);

		if($this->isBlock){
			$this->putBlockPosition($this->x, $this->y, $this->z);
			$this->putUnsignedVarInt($this->commandBlockMode);
			$this->putBool($this->isRedstoneMode);
			$this->putBool($this->isConditional);
		}else{
			$this->putEntityRuntimeId($this->minecartEid);
		}

		$this->putString($this->command);
		$this->putString($this->lastOutput);
		$this->putString($this->name);
		$this->putString($this->filteredName);

		$this->putBool($this->shouldTrackOutput);
		$this->putLInt($this->tickDelay);
		$this->putBool($this->executeOnFirstTick);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCommandBlockUpdate($this);
	}
}
