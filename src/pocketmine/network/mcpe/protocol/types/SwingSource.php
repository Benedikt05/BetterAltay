<?php

namespace pocketmine\network\mcpe\protocol\types;

final class SwingSource{

	private function __construct(){
		//NOOP
	}

	public const NONE = "none";
	public const BUILD = "build";
	public const MINE = "mine";
	public const INTERACT = "interact";
	public const ATTACK = "attack";
	public const USE_ITEM = "useitem";
	public const THROW_ITEM = "throwitem";
	public const DROP_ITEM = "dropitem";
	public const EVENT = "event";

}