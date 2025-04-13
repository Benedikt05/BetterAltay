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

use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\BitSet;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\InputMode;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequest;
use pocketmine\network\mcpe\protocol\types\ItemInteractionData;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\network\mcpe\protocol\types\PlayerBlockAction;
use pocketmine\network\mcpe\protocol\types\PlayerBlockActionStopBreak;
use pocketmine\network\mcpe\protocol\types\PlayerBlockActionWithBlockInfo;
use pocketmine\network\mcpe\protocol\types\PlayMode;
use pocketmine\network\mcpe\protocol\PlayerActionPacket as PlayerAction;
use RuntimeException;

class PlayerAuthInputPacket extends DataPacket/* implements ServerboundPacket*/
{
	public const NETWORK_ID = ProtocolInfo::PLAYER_AUTH_INPUT_PACKET;

	private Vector3 $position;
	private float $pitch;
	private float $yaw;
	private float $headYaw;
	private float $moveVecX;
	private float $moveVecZ;
	private BitSet $inputFlags;
	private int $inputMode;
	private int $playMode;
	private int $interactionMode;
	private Vector2 $interactRotation;
	private int $tick;
	private Vector3 $delta;
	private ?ItemInteractionData $itemInteractionData = null;
	private ?ItemStackRequest $itemStackRequest = null;
	/** @var PlayerBlockAction[]|null */
	private ?array $blockActions = null;
	private ?PlayerAuthInputVehicleInfo $vehicleInfo = null;
	private float $analogMoveVecX;
	private float $analogMoveVecZ;
	private Vector3 $cameraOrientation; //Todo
	private Vector2 $rawMove;


	/**
	 * @param Vector3                         $position
	 * @param float                           $pitch
	 * @param float                           $yaw
	 * @param float                           $headYaw
	 * @param float                           $moveVecX
	 * @param float                           $moveVecZ
	 * @param BitSet                          $inputFlags @see PlayerAuthInputFlags
	 * @param int                             $inputMode @see InputMode
	 * @param int                             $playMode @see PlayMode
	 * @param int                             $interactionMode @see InteractionMode
	 * @param Vector2                         $interactRotation
	 * @param int                             $tick
	 * @param Vector3                         $delta
	 * @param ItemInteractionData|null        $itemInteractionData
	 * @param ItemStackRequest|null           $itemStackRequest
	 * @param PlayerBlockAction[]|null        $blockActions Blocks that the client has interacted with
	 * @param PlayerAuthInputVehicleInfo|null $vehicleInfo
	 * @param float                           $analogMoveVecX
	 * @param float                           $analogMoveVecZ
	 * @param Vector3                         $cameraOrientation
	 *
	 * @return PlayerAuthInputPacket
	 */
	public static function create(
		Vector3 $position,
		float $pitch,
		float $yaw,
		float $headYaw,
		float $moveVecX,
		float $moveVecZ,
		BitSet $inputFlags,
		int $inputMode,
		int $playMode,
		int $interactionMode,
		Vector2 $interactRotation,
		int $tick,
		Vector3 $delta,
		?ItemInteractionData $itemInteractionData,
		?ItemStackRequest $itemStackRequest,
		?array $blockActions,
		?PlayerAuthInputVehicleInfo $vehicleInfo,
		float $analogMoveVecX,
		float $analogMoveVecZ,
		Vector3 $cameraOrientation,
	) : self{
		$result = new self;
		$result->position = $position->asVector3();
		$result->pitch = $pitch;
		$result->yaw = $yaw;
		$result->headYaw = $headYaw;
		$result->moveVecX = $moveVecX;
		$result->moveVecZ = $moveVecZ;

		if($inputFlags->getLength() !== 65){
			throw new \InvalidArgumentException("Input flags must be 65 bits long");
		}

		$inputFlags->set(PlayerAuthInputFlags::PERFORM_ITEM_STACK_REQUEST, $itemStackRequest !== null);
		$inputFlags->set(PlayerAuthInputFlags::PERFORM_ITEM_INTERACTION, $itemInteractionData !== null);
		$inputFlags->set(PlayerAuthInputFlags::PERFORM_BLOCK_ACTIONS, $blockActions !== null);
		$inputFlags->set(PlayerAuthInputFlags::IN_CLIENT_PREDICTED_VEHICLE, $vehicleInfo !== null);

		$result->inputMode = $inputMode;
		$result->playMode = $playMode;
		$result->interactionMode = $interactionMode;
		$result->interactRotation = $interactRotation;
		$result->tick = $tick;
		$result->delta = $delta;
		$result->itemInteractionData = $itemInteractionData;
		$result->itemStackRequest = $itemStackRequest;
		$result->blockActions = $blockActions;
		$result->vehicleInfo = $vehicleInfo;
		$result->analogMoveVecX = $analogMoveVecX;
		$result->analogMoveVecZ = $analogMoveVecZ;
		$result->cameraOrientation = $cameraOrientation;
		return $result;
	}

	public function getPosition() : Vector3{
		return $this->position;
	}

	public function getPitch() : float{
		return $this->pitch;
	}

	public function getYaw() : float{
		return $this->yaw;
	}

	public function getHeadYaw() : float{
		return $this->headYaw;
	}

	public function getMoveVecX() : float{
		return $this->moveVecX;
	}

	public function getMoveVecZ() : float{
		return $this->moveVecZ;
	}

	/**
	 * @see PlayerAuthInputFlags
	 */
	public function getInputFlags() : BitSet{
		return $this->inputFlags;
	}

	/**
	 * @see InputMode
	 */
	public function getInputMode() : int{
		return $this->inputMode;
	}

	/**
	 * @see PlayMode
	 */
	public function getPlayMode() : int{
		return $this->playMode;
	}

	/**
	 * @see InteractionMode
	 */
	public function getInteractionMode() : int{
		return $this->interactionMode;
	}

	public function getInteractRotation () : Vector2{
		return $this->interactRotation;
	}

	public function getTick() : int{
		return $this->tick;
	}

	public function getDelta() : Vector3{
		return $this->delta;
	}

	public function getItemInteractionData() : ?ItemInteractionData{
		return $this->itemInteractionData;
	}

	public function getItemStackRequest() : ?ItemStackRequest{
		return $this->itemStackRequest;
	}

	/**
	 * @return PlayerBlockAction[]|null
	 */
	public function getBlockActions() : ?array{
		return $this->blockActions;
	}

	public function getVehicleInfo() : ?PlayerAuthInputVehicleInfo{ return $this->vehicleInfo; }

	public function getAnalogMoveVecX() : float{ return $this->analogMoveVecX; }

	public function getAnalogMoveVecZ() : float{ return $this->analogMoveVecZ; }

	public function getRawMove() : Vector2{ return $this->rawMove; }

	protected function decodePayload() : void{
		$this->pitch = $this->getLFloat();
		$this->yaw = $this->getLFloat();
		$this->position = $this->getVector3();
		$this->moveVecX = $this->getLFloat();
		$this->moveVecZ = $this->getLFloat();
		$this->headYaw = $this->getLFloat();
		$this->inputFlags = BitSet::read($this, 65);
		$this->inputMode = $this->getUnsignedVarInt();
		$this->playMode = $this->getUnsignedVarInt();
		$this->interactionMode = $this->getUnsignedVarInt();
		$this->interactRotation = $this->getVector2();
		$this->tick = $this->getUnsignedVarLong();
		$this->delta = $this->getVector3();

		if($this->inputFlags->get(PlayerAuthInputFlags::PERFORM_ITEM_INTERACTION)){
			$this->itemInteractionData = ItemInteractionData::read($this);
		}

		if($this->inputFlags->get(PlayerAuthInputFlags::PERFORM_ITEM_STACK_REQUEST)){
			$this->itemStackRequest = ItemStackRequest::read($this);
		}

		if($this->inputFlags->get(PlayerAuthInputFlags::PERFORM_BLOCK_ACTIONS)){
			$this->blockActions = [];
			$max = $this->getVarInt();
			for($i = 0; $i < $max; ++$i){
				$actionType = $this->getVarInt();
				$this->blockActions[] = match (true) {
					PlayerBlockActionWithBlockInfo::isValidActionType($actionType) => PlayerBlockActionWithBlockInfo::read($this, $actionType),
					$actionType === PlayerAction::ACTION_STOP_BREAK => new PlayerBlockActionStopBreak(),
					default => throw new RuntimeException("Unexpected block action type $actionType")
				};
			}

		}


		if($this->inputFlags->get(PlayerAuthInputFlags::IN_CLIENT_PREDICTED_VEHICLE)){
			PlayerAuthInputVehicleInfo::read($this);
		}

		$this->analogMoveVecX = $this->getLFloat();
		$this->analogMoveVecZ = $this->getLFloat();
		$this->cameraOrientation = $this->getVector3();
		$this->rawMove = $this->getVector2();
	}

	protected function encodePayload() : void{
		$this->putLFloat($this->pitch);
		$this->putLFloat($this->yaw);
		$this->putVector3($this->position);
		$this->putLFloat($this->moveVecX);
		$this->putLFloat($this->moveVecZ);
		$this->putLFloat($this->headYaw);
		$this->inputFlags->write($this);
		$this->putUnsignedVarInt($this->inputMode);
		$this->putUnsignedVarInt($this->playMode);
		$this->putUnsignedVarInt($this->interactionMode);
		$this->putVector2($this->interactRotation);
		$this->putUnsignedVarLong($this->tick);
		$this->putVector3($this->delta);
		if($this->itemInteractionData !== null){
			$this->itemInteractionData->write($this);
		}
		if($this->itemStackRequest !== null){
			$this->itemStackRequest->write($this);
		}
		if($this->blockActions !== null){
			$this->putVarInt(count($this->blockActions));
			foreach($this->blockActions as $blockAction){
				$this->putVarInt($blockAction->getActionType());
				$blockAction->write($this);
			}
		}
		if($this->vehicleInfo !== null){
			$this->vehicleInfo->write($this);
		}
		$this->putLFloat($this->analogMoveVecX);
		$this->putLFloat($this->analogMoveVecZ);
		$this->putVector3($this->cameraOrientation);
		$this->putVector2($this->rawMove);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePlayerAuthInput($this);
	}
}
