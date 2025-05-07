<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\protocol\PlayerActionPacket as PlayerAction;

/** This is used for PlayerAuthInput packet when the flags include PERFORM_BLOCK_ACTIONS */
final class PlayerBlockActionWithBlockInfo implements PlayerBlockAction{
	public function __construct(
		private int $actionType,
		private Vector3 $blockPosition,
		private int $face
	){
		if(!self::isValidActionType($actionType)){
			throw new \InvalidArgumentException("Invalid action type for " . self::class);
		}
	}

	public function getActionType() : int{ return $this->actionType; }

	public function getBlockPosition() : Vector3{ return $this->blockPosition; }

	public function getFace() : int{ return $this->face; }

	public static function read(NetworkBinaryStream $in, int $actionType) : self{
		$x = $y = $z = 0;
		$in->getSignedBlockPosition($x, $y, $z);
		$blockPosition = new Vector3($x, $y, $z);
		$face = $in->getVarInt();
		return new self($actionType, $blockPosition, $face);
	}

	public function write(NetworkBinaryStream $out) : void{
		$out->putSignedBlockPosition($this->blockPosition->x, $this->blockPosition->y, $this->blockPosition->z);
		$out->putVarInt($this->face);
	}

	public static function isValidActionType(int $actionType) : bool{
		return match($actionType){
			PlayerAction::ACTION_ABORT_BREAK,
			PlayerAction::ACTION_START_BREAK,
			PlayerAction::ACTION_CRACK_BREAK,
			PlayerAction::ACTION_PREDICT_DESTROY_BLOCK,
			PlayerAction::ACTION_CONTINUE_DESTROY_BLOCK => true,
			default => false
		};
	}
}
