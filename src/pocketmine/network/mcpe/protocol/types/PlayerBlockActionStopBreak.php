<?php

namespace pocketmine\network\mcpe\protocol\types;


use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\protocol\PlayerActionPacket as PlayerAction;

final class PlayerBlockActionStopBreak implements PlayerBlockAction{

	public function getActionType() : int{
		return PlayerAction::ACTION_STOP_BREAK;
	}

	public function write(NetworkBinaryStream $out) : void{
		//NOOP
	}
}