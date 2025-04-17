<?php

namespace pocketmine\network\mcpe\protocol\types;

use pocketmine\network\mcpe\NetworkBinaryStream;

/** This is used for PlayerAuthInput packet when the flags include PERFORM_BLOCK_ACTIONS */
interface PlayerBlockAction{

	public function getActionType() : int;

	public function write(NetworkBinaryStream $out) : void;
}


