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

namespace pocketmine\network\mcpe\protocol\types\inventory;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkBinaryStream as PacketSerializer;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\NetworkInventoryAction;

class UseItemTransactionData extends TransactionData{
	public const ACTION_CLICK_BLOCK = 0;
	public const ACTION_CLICK_AIR = 1;
	public const ACTION_BREAK_BLOCK = 2;

	public const TRIGGER_UNKNOWN = 0;
	public const TRIGGER_PLAYER_INPUT = 1;
	public const TRIGGER_SIMULATION_TICK = 2;

	public const CLIENT_PREDICTION_FAILURE = 0;
	public const CLIENT_PREDICTION_SUCCESS = 1;

	private int $actionType;
	private int $triggerType;
	private Vector3 $blockPos;
	private int $face;
	private int $hotbarSlot;
	private ItemStackWrapper $itemInHand;
	private Vector3 $playerPos;
	private Vector3 $clickPos;
	private int $blockNetworkId;
	private int $clientPrediction;

	public function getActionType() : int{
		return $this->actionType;
	}

	public function getTriggerType() : int{
		return $this->triggerType;
	}

	public function getBlockPos() : Vector3{
		return $this->blockPos;
	}

	public function getFace() : int{
		return $this->face;
	}

	public function getHotbarSlot() : int{
		return $this->hotbarSlot;
	}

	public function getItemInHand() : ItemStackWrapper{
		return $this->itemInHand;
	}

	public function getPlayerPos() : Vector3{
		return $this->playerPos;
	}

	public function getClickPos() : Vector3{
		return $this->clickPos;
	}

	public function getBlockNetworkId() : int{
		return $this->blockNetworkId;
	}

	public function getClientPrediction() : int{
		return $this->clientPrediction;
	}

	public function getTypeId() : int{
		return InventoryTransactionPacket::TYPE_USE_ITEM;
	}

	protected function decodeData(PacketSerializer $stream) : void{
		$this->actionType = $stream->getUnsignedVarInt();
		$this->triggerType = $stream->getUnsignedVarInt();
		$x = $y = $z = 0;
		$stream->getBlockPosition($x, $y, $z);
		$this->blockPos = new Vector3($x, $y, $z);
		$this->face = $stream->getVarInt();
		$this->hotbarSlot = $stream->getVarInt();
		$this->itemInHand = ItemStackWrapper::read($stream);
		$this->playerPos = $stream->getVector3();
		$this->clickPos = $stream->getVector3();
		$this->blockNetworkId = $stream->getUnsignedVarInt();
		$this->clientPrediction = $stream->getByte();
	}

	protected function encodeData(PacketSerializer $stream) : void{
		$stream->putUnsignedVarInt($this->actionType);
		$stream->putUnsignedVarInt($this->triggerType);
		$stream->putBlockPosition($this->blockPos->x, $this->blockPos->y, $this->blockPos->z);
		$stream->putVarInt($this->face);
		$stream->putVarInt($this->hotbarSlot);
		$this->itemInHand->write($stream);
		$stream->putVector3($this->playerPos);
		$stream->putVector3($this->clickPos);
		$stream->putUnsignedVarInt($this->blockNetworkId);
		$stream->putByte($this->clientPrediction);
	}

	/**
	 * @param NetworkInventoryAction[] $actions
	 */

	public static function new(array $actions, int $actionType, int $triggerType, Vector3 $blockPos, int $face, int $hotbarSlot, ItemStackWrapper $itemInHand, Vector3 $playerPos, Vector3 $clickPos, int $blockNetworkId, int $clientPrediction) : self{
		$result = new self;
		$result->actions = $actions;
		$result->actionType = $actionType;
		$result->triggerType = $triggerType;
		$result->blockPos = $blockPos;
		$result->face = $face;
		$result->hotbarSlot = $hotbarSlot;
		$result->itemInHand = $itemInHand;
		$result->playerPos = $playerPos;
		$result->clickPos = $clickPos;
		$result->blockNetworkId = $blockNetworkId;
		$result->clientPrediction = $clientPrediction;
		return $result;
	}
}
