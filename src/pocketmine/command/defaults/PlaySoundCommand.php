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

declare(strict_types = 1);

namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandSelector;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\TranslationContainer;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\types\CommandParameter;
use pocketmine\Player;

class PlaySoundCommand extends VanillaCommand{

	public function __construct(string $name){
		parent::__construct($name, "Plays a sound", "/playsound <sound: string> [player: target] [position: x y z] [volume: float] [pitch: float]", [], [
			[
				new CommandParameter("sound", AvailableCommandsPacket::ARG_TYPE_STRING, false),
				new CommandParameter("player", AvailableCommandsPacket::ARG_TYPE_TARGET),
				new CommandParameter("pos", AvailableCommandsPacket::ARG_TYPE_POSITION),
				new CommandParameter("volume", AvailableCommandsPacket::ARG_TYPE_FLOAT),
				new CommandParameter("pitch", AvailableCommandsPacket::ARG_TYPE_FLOAT)
			]
		]);

		$this->setPermission("altay.command.playsound");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}

		if(empty($args)){
			throw new InvalidCommandSyntaxException();
		}

		$soundName = $args[0];
		$pos = $sender instanceof Player ? $sender->asVector3() : new Vector3();

		if(count($args) > 1){
			/** @var Player[] $targets */
			$targets = CommandSelector::findTargets($sender, $args[1], Player::class, $pos);

			if(count($args) >= 5){
				$pos = new Vector3(
					$this->getRelativeDouble($pos->x, $sender, $args[2]),
					$this->getRelativeDouble($pos->y, $sender, $args[3]),
					$this->getRelativeDouble($pos->y, $sender, $args[4])
				);
			}
		}elseif($sender instanceof Player){
			$targets = [$sender];
		}else{
			throw new InvalidCommandSyntaxException();
		}

		$pk = new PlaySoundPacket();
		$pk->soundName = $soundName;
		$pk->x = $pos->x;
		$pk->y = $pos->y;
		$pk->z = $pos->z;
		$pk->volume = floatval($args[5] ?? 1.0);
		$pk->pitch = floatval($args[6] ?? 1.0);

		$sender->getServer()->broadcastPacket($targets, $pk);
		$sender->sendMessage(new TranslationContainer("commands.playsound.success", [$soundName, $sender->getName()]));

		return true;
	}
}
