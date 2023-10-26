<?php

/*
 *
 *  __            _        _   _  _ _                  _  _ __
 * |  _ \ _   _| | __| ||  \/  () _   __      |  \/  |  _ \
 * | |) / _ \ / _| |/ / _ \ _| |\/| | | ' \ / _ \__| |\/| | |) |
 * |  _/ () | (_|   <  _/ || |  | | | | | |  _/__| |  | |  _/
 * ||   \_/ \_||\_\__|\||  |||| ||\__|     ||  |||
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
use function count;

/**
 * Sets the message shown on the death screen underneath "You died!"
 */
class DeathInfoPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::DEATH_INFO_PACKET;

	private string $messageTranslationKey;
	/** @var string[] */
	private array $messageParameters;

	/**
	 * @generate-create-func
	 * @param string[] $messageParameters
	 */
	public static function create(string $messageTranslationKey, array $messageParameters) : self{
		$result = new self;
		$result->messageTranslationKey = $messageTranslationKey;
		$result->messageParameters = $messageParameters;
		return $result;
	}

	public function getMessageTranslationKey() : string{ return $this->messageTranslationKey; }

	/** @return string[] */
	public function getMessageParameters() : array{ return $this->messageParameters; }

	protected function decodePayload(){
		$this->messageTranslationKey = $this->getString();

		$this->messageParameters = [];
		for($i = 0, $len = $this->getUnsignedVarInt(); $i < $len; $i++){
			$this->messageParameters[] = $this->getString();
		}
	}

	protected function encodePayload(){
		$this->putString($this->messageTranslationKey);

		$this->putUnsignedVarInt(count($this->messageParameters));
		foreach($this->messageParameters as $parameter){
			$this->putString($parameter);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleDeathInfo($this);
	}
}
