<?php

/*
 *               _ _
 *         /\   | | |
 *        /  \  | | |_ __ _ _   _
 *       / /\ \ | | __/ _` | | | |
 *      / ____ \| | || (_| | |_| |
 *     /_/    \_|_|\__\__,_|\__, |
 *                           __/ |
 *                          |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author TuranicTeam
 * @link https://github.com/TuranicTeam/Altay
 *
 */

declare(strict_types=1);

namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\TranslationContainer;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\CommandEnum;
use pocketmine\network\mcpe\protocol\types\CommandParameter;
use pocketmine\Player;

class GameRuleCommand extends VanillaCommand{

	public function __construct(string $name){
		parent::__construct($name, "%altay.command.gamerule.description", "%altay.command.gamerule.usage", [], [
			[
				new CommandParameter("rule", AvailableCommandsPacket::ARG_TYPE_STRING, false, new CommandEnum("BoolGameRule", $this->getKnownGameRules()), 1),
				new CommandParameter("value", AvailableCommandsPacket::ARG_TYPE_STRING, false, new CommandEnum("Bool", ["true", "false"])),
			],
			[
				new CommandParameter("rule", AvailableCommandsPacket::ARG_TYPE_STRING, false, new CommandEnum("IntGameRule", $this->getKnownIntGameRules()), 1),
				new CommandParameter("value", AvailableCommandsPacket::ARG_TYPE_INT),
			]
		]);

		$this->setPermission("altay.command.gamerule");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}

		if(count($args) < 2){
			throw new InvalidCommandSyntaxException();
		}

		if($sender instanceof Player){
			$level = $sender->getLevel();
		}else{
			$level = $sender->getServer()->getDefaultLevel();
		}

		$ruleName = $this->matchRuleName($level->getGameRules()->getRules(), $args[0]);

		if($level->getGameRules()->hasRule($ruleName)){
			if($level->getGameRules()->setRuleWithMatching($ruleName, $args[1])){
				$sender->sendMessage(new TranslationContainer("commands.gamerule.success", [
					$ruleName,
					$level->getGameRules()->toStringValue(($level->getGameRules()->getRuleValue($ruleName)))
				]));
			}else{
				$sender->sendMessage(new TranslationContainer("commands.generic.syntax", ["/gamerule " . $args[0] . " ", $args[1], " "]));
			}
		}else{
			$sender->sendMessage(new TranslationContainer("commands.generic.syntax", ["/gamerule ", $args[0] . " ", " " . $args[1]]));
		}

		return true;
	}

	public function getKnownGameRules() : array{
		return [
			"commandblockoutput", "dodaylightcycle", "doentitydrops", "dofiretick", "doinsomnia", "domobloot",
			"domobspawning", "dotiledrops", "doimmediaterespawn", "doweathercycle", "drowningdamage", "falldamage", "firedamage",
			"keepinventory", "mobgriefing", "naturalregeneration", "pvp",
			"sendcommandfeedback", "showcoordinates", "tntexplodes",
			"commandblocksenabled", "showdeathmessages", "recipesunlock",
		];
	}

	public function getKnownIntGameRules() : array{
		return [
			"maxcommandchainlength", "functioncommandlimit", "randomtickspeed"
		];
	}

	/**
	 * This a fix for difference between bedrock and java edition game rule name
	 *
	 * @param array  $rules
	 * @param string $input
	 *
	 * @return string
	 */
	public function matchRuleName(array $rules, string $input) : string{
		foreach($rules as $name => $d){
			if(strtolower($name) === $input){
				return $name;
			}
		}

		return $input;
	}
}
