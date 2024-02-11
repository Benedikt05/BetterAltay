<?php


declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types\hud;

final class HudElement{

	private function __construct(){
		//NOOP
	}

	public const PAPER_DOLL = 0;
	public const ARMOR = 1;
	public const TOOLTIPS = 2;
	public const TOUCH_CONTROLS = 3;
	public const CROSSHAIR = 4;
	public const HOTBAR = 5;
	public const HEALTH = 6;
	public const XP = 7;
	public const FOOD = 8;
	public const AIR_BUBBLES = 9;
	public const VEHICLE_HEALTH = 10;
}
