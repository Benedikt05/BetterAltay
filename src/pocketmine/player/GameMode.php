<?php

namespace pocketmine\player;

final class GameMode{

	private static string $engName;

	public static function SURVIVAL() : int{ return 0; }

	public static function CREATIVE() : int{ return 1; }

	public static function ADVENTURE() : int{ return 2; }

	public static function SPECTATOR() : int{ return 3; }

	public static function fromString(string $str) : int{
		switch(strtolower(trim($str))){
			case (string) self::SURVIVAL():
			case "survival":
			case "s":
				if($str === "survival" || $str === "s"){
					self::$engName = "Survival";
				}
				return self::SURVIVAL();

			case (string) self::CREATIVE():
			case "creative":
			case "c":
				if($str === "creative" || $str === "c"){
					self::$engName = "Creative";
				}
				return self::CREATIVE();

			case (string) self::ADVENTURE():
			case "adventure":
			case "a":
				if($str === "adventure" || $str === "a"){
					self::$engName = "Adventure";
				}
				return self::ADVENTURE();

			case (string) self::SPECTATOR():
			case "spectator":
			case "view":
			case "v":
				if($str === "spectator" || $str === "v" || $str === "view"){
					self::$engName = "Spectator";
				}
				return self::SPECTATOR();
		}
		return -1;
	}

	public function getEnglishName() : string{
		return self::$engName;
	}

	/**
	 * @return string[]
	 */
	public function getAliases() : array{
		return ["c", "s", "v", "view", "a"];
	}

	public function getTranslatableName() : string{
		return $this->getEnglishName();//soon
	}
}