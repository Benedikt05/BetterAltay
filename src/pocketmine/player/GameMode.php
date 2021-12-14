<?php

namespace pocketmine\player;

final class GameMode
{

	private static string $gamemodeName;

	public static function SURVIVAL() : int
	{return 0;}
	public static function CREATIVE() : int
	{return 1;}
	public static function ADVENTURE() : int
	{return 2;}
	public static function SPECTATOR() : int
	{return 3;}

	public static function fromString(string $str) : int
	{
		$gm = match ($str) {
			"creative", "c" => self::CREATIVE(),
			"spectator", "v", "view" => self::SPECTATOR(),
			"survival", "s" => self::SURVIVAL(),
			"adventure", "a" => self::ADVENTURE(),
			default => match ($str) {
				"creative", "c", "Creative" => self::CREATIVE(),
				"survival", "s", "Survival" => self::SURVIVAL(),
				"adventure", "a", "Adventure" => self::ADVENTURE(),
				"spectator", "v", "view", "Spectator" => self::SPECTATOR(),
				default => -1
			}
		};
		match ($str) {
			"creative", "c", "Creative", "survival", "s", "Survival", "spectator", "v", "view", "Spectator", "adventure", "a", "Adventure" => self::$gamemodeName = $str,
			default => "MINECRAFTPE"
		};
		return $gm;
	}

	public function getEnglishName() : string
	{
		return self::$gamemodeName;
	}

	/**
	 * @return string[]
	 */
	public function getAliases() : array
	{
		return ["c", "s", "v", "view", "a"];
	}

	public function getTranslatableName() : string
	{
		return $this->getEnglishName();//soon
	}
}