<?php



declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types\hud;

final class HudVisibility{

	private function __construct(){
		//NOOP
	}

	public const HIDE = 0;
	public const RESET = 1;
}
