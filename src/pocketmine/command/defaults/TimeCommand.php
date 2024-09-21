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

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\TranslationContainer;
use pocketmine\level\Level;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\CommandEnum;
use pocketmine\network\mcpe\protocol\types\CommandParameter;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;

class TimeCommand extends VanillaCommand{

	public function __construct(string $name){
		parent::__construct($name, "%pocketmine.command.time.description", "%pocketmine.command.time.usage");
		$this->setPermission("pocketmine.command.time.add;pocketmine.command.time.set;pocketmine.command.time.start;pocketmine.command.time.stop");

		$amount = new CommandParameter("amount", AvailableCommandsPacket::ARG_TYPE_INT);
		$set = new CommandParameter("set", AvailableCommandsPacket::ARG_TYPE_STRING, false, new CommandEnum("set", ["set"]));

		$this->setParameters([
			new CommandParameter("add", AvailableCommandsPacket::ARG_TYPE_STRING, false, new CommandEnum("add", ["add"])),
			$amount
		], 0);
		$this->setParameters([
			$set, $amount
		], 1);
		$this->setParameters([
			$set,
			new CommandParameter("time", AvailableCommandsPacket::ARG_TYPE_STRING, false, new CommandEnum("time", [
				"day", "sunrise", "noon", "sunset", "night", "midnight"
			]))
		], 2);
		$this->setParameters([
			new CommandParameter("querySE", AvailableCommandsPacket::ARG_TYPE_STRING, false, new CommandEnum("query", ["query"])),
			new CommandParameter("query", AvailableCommandsPacket::ARG_TYPE_STRING, false, new CommandEnum("queryTime", [
				"day", "sunrise", "noon", "sunset", "night", "midnight"
			]))
		], 3);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(count($args) < 1){
			throw new InvalidCommandSyntaxException();
		}

		if($args[0] === "start"){
			if(!$sender->hasPermission("pocketmine.command.time.start")){
				$sender->sendMessage($sender->getServer()->getLanguage()->translateString(TextFormat::RED . "%commands.generic.permission"));

				return true;
			}
			foreach($sender->getServer()->getLevels() as $level){
				$level->startTime();
			}
			Command::broadcastCommandMessage($sender, "Restarted the time");
			return true;
		}elseif($args[0] === "stop"){
			if(!$sender->hasPermission("pocketmine.command.time.stop")){
				$sender->sendMessage($sender->getServer()->getLanguage()->translateString(TextFormat::RED . "%commands.generic.permission"));

				return true;
			}
			foreach($sender->getServer()->getLevels() as $level){
				$level->stopTime();
			}
			Command::broadcastCommandMessage($sender, "Stopped the time");
			return true;
		}elseif($args[0] === "query"){
			if(!$sender->hasPermission("pocketmine.command.time.query")){
				$sender->sendMessage($sender->getServer()->getLanguage()->translateString(TextFormat::RED . "%commands.generic.permission"));

				return true;
			}
			if($sender instanceof Player){
				$level = $sender->getLevelNonNull();
			}else{
				$level = $sender->getServer()->getDefaultLevel();
			}
			$sender->sendMessage($sender->getServer()->getLanguage()->translateString("commands.time.query", [$level->getTime()]));
			return true;
		}

		if(count($args) < 2){
			throw new InvalidCommandSyntaxException();
		}

		if($args[0] === "set"){
			if(!$sender->hasPermission("pocketmine.command.time.set")){
				$sender->sendMessage($sender->getServer()->getLanguage()->translateString(TextFormat::RED . "%commands.generic.permission"));

				return true;
			}

			switch($args[1]){
				case "day":
					$value = Level::TIME_DAY;
					break;
				case "noon":
					$value = Level::TIME_NOON;
					break;
				case "sunset":
					$value = Level::TIME_SUNSET;
					break;
				case "night":
					$value = Level::TIME_NIGHT;
					break;
				case "midnight":
					$value = Level::TIME_MIDNIGHT;
					break;
				case "sunrise":
					$value = Level::TIME_SUNRISE;
					break;
				default:
					$value = $this->getInteger($sender, $args[1], 0);
					break;
			}

			foreach($sender->getServer()->getLevels() as $level){
				$level->setTime($value);
			}
			Command::broadcastCommandMessage($sender, new TranslationContainer("commands.time.set", [$value]));
		}elseif($args[0] === "add"){
			if(!$sender->hasPermission("pocketmine.command.time.add")){
				$sender->sendMessage($sender->getServer()->getLanguage()->translateString(TextFormat::RED . "%commands.generic.permission"));

				return true;
			}

			$value = $this->getInteger($sender, $args[1], 0);
			foreach($sender->getServer()->getLevels() as $level){
				$level->setTime($level->getTime() + $value);
			}
			Command::broadcastCommandMessage($sender, new TranslationContainer("commands.time.added", [$value]));
		}else{
			throw new InvalidCommandSyntaxException();
		}

		return true;
	}
}