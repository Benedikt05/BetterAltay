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

use pocketmine\network\mcpe\NetworkSession;

class CodeBuilderSourcePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CODE_BUILDER_SOURCE_PACKET;

	private int $operation;
	private int $category;
	private int $codeStatus;

	/**
	 * @generate-create-func
	 */
	public static function create(int $operation, int $category, int $codeStatus) : self{
		$result = new self;
		$result->operation = $operation;
		$result->category = $category;
		$result->codeStatus = $codeStatus;
		return $result;
	}

	public function getOperation() : int{ return $this->operation; }

	public function getCategory() : int{ return $this->category; }

	public function getCodeStatus() : int{ return $this->codeStatus; }

	protected function decodePayload() : void{
		$this->operation = $this->getByte();
		$this->category = $this->getByte();
		$this->codeStatus = $this->getByte();
	}

	protected function encodePayload() : void{
		$this->putByte($this->operation);
		$this->putByte($this->category);
		$this->putByte($this->codeStatus);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCodeBuilderSource($this);
	}
}
