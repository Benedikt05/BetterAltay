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
use pocketmine\network\mcpe\protocol\types\ArmorSlotAndDamagePair;
use function count;

class PlayerArmorDamagePacket extends DataPacket/* implements ClientboundPacket*/
{
	public const NETWORK_ID = ProtocolInfo::PLAYER_ARMOR_DAMAGE_PACKET;

	/**
	 * @var ArmorSlotAndDamagePair[]
	 * @phpstan-var list<ArmorSlotAndDamagePair>
	 */
	private array $armorSlotAndDamagePairs = [];

	/**
	 * @generate-create-func
	 * @param ArmorSlotAndDamagePair[] $armorSlotAndDamagePairs
	 * @phpstan-param list<ArmorSlotAndDamagePair> $armorSlotAndDamagePairs
	 */
	public static function create(array $armorSlotAndDamagePairs) : self{
		$result = new self;
		$result->armorSlotAndDamagePairs = $armorSlotAndDamagePairs;
		return $result;
	}

	/**
	 * @return ArmorSlotAndDamagePair[]
	 * @phpstan-return list<ArmorSlotAndDamagePair>
	 */
	public function getArmorSlotAndDamagePairs() : array{
		return $this->armorSlotAndDamagePairs;
	}

	protected function decodePayload() : void{
		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			$this->armorSlotAndDamagePairs[] = ArmorSlotAndDamagePair::read($this);
		}
	}

	protected function encodePayload() : void{
		$this->putUnsignedVarInt(count($this->armorSlotAndDamagePairs));
		foreach($this->armorSlotAndDamagePairs as $pair){
			$pair->write($this);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePlayerArmorDamage($this);
	}
}
