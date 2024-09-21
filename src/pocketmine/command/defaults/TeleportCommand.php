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
use pocketmine\level\Location;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\CommandEnum;
use pocketmine\network\mcpe\protocol\types\CommandParameter;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;
use function array_shift;
use function count;
use function round;

class TeleportCommand extends VanillaCommand{

	public function __construct(string $name){
		parent::__construct($name, "%pocketmine.command.tp.description", "%commands.tp.usage", ["teleport"]);
		$this->setPermission("pocketmine.command.teleport");

		$destination = new CommandParameter("destination", AvailableCommandsPacket::ARG_TYPE_POSITION, false);
		$targetDestination = new CommandParameter("destination", AvailableCommandsPacket::ARG_TYPE_TARGET, false);
		$victim = new CommandParameter("victim", AvailableCommandsPacket::ARG_TYPE_TARGET, false);
		$yRot = new CommandParameter("yRot", AvailableCommandsPacket::ARG_TYPE_VALUE);
		$xRot = new CommandParameter("xRot", AvailableCommandsPacket::ARG_TYPE_VALUE);
		$facing = new CommandParameter("facing", AvailableCommandsPacket::ARG_TYPE_STRING, false, new CommandEnum("facing", ["facing"]));
		$lookAtPosition = new CommandParameter("lookAtPosition", AvailableCommandsPacket::ARG_TYPE_POSITION, false);
		$lookAtEntity = new CommandParameter("lookAtEntity", AvailableCommandsPacket::ARG_TYPE_TARGET, false);

		$this->setParameters([
			$destination, $yRot, $xRot
		], 0);
		$this->setParameters([
			$destination, $facing, $lookAtPosition
		], 1);
		$this->setParameters([
			$destination, $facing, $lookAtEntity
		], 2);
		$this->setParameters([
			$victim, $destination, $yRot, $xRot
		], 3);
		$this->setParameters([
			$victim, $destination, $facing, $lookAtPosition
		], 4);
		$this->setParameters([
			$victim, $destination, $facing, $lookAtEntity
		], 5);
		$this->setParameters([$targetDestination], 6);
		$this->setParameters([
			$victim, $targetDestination
		], 7);
	}

	private function findPlayer(CommandSender $sender, string $playerName) : ?Player{
		$subject = $sender->getServer()->getPlayer($playerName);
		if($subject === null){
			$sender->sendMessage(TextFormat::RED . "Can't find player " . $playerName);
			return null;
		}
		return $subject;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}

		switch(count($args)){
			case 1: // /tp targetPlayer
			case 3: // /tp x y z
			case 5: // /tp x y z yaw pitch - TODO: 5 args could be target x y z yaw :(
				if(!($sender instanceof Player)){
					$sender->sendMessage(TextFormat::RED . "Please provide a player!");
					return true;
				}

				$subject = $sender;
				$targetArgs = $args;
				break;
			case 2: // /tp player1 player2
			case 4: // /tp player1 x y z - TODO: 4 args could be x y z yaw :(
			case 6: // /tp player1 x y z yaw pitch
				$subject = $this->findPlayer($sender, $args[0]);
				if($subject === null){
					return true;
				}
				$targetArgs = $args;
				array_shift($targetArgs);
				break;
			default:
				throw new InvalidCommandSyntaxException();
		}

		switch(count($targetArgs)){
			case 1:
				$targetPlayer = $this->findPlayer($sender, $targetArgs[0]);
				if($targetPlayer === null){
					return true;
				}

				$subject->teleport($targetPlayer->getLocation());
				Command::broadcastCommandMessage($sender, new TranslationContainer("commands.tp.success", [$subject->getName(), $targetPlayer->getName()]));

				return true;
			case 3:
			case 5:
				$base = $subject->getLocation();
				if(count($targetArgs) === 5){
					$yaw = (float) $targetArgs[3];
					$pitch = (float) $targetArgs[4];
				}else{
					$yaw = $base->yaw;
					$pitch = $base->pitch;
				}

				$x = $this->getRelativeDouble($base->x, $sender, $targetArgs[0]);
				$y = $this->getRelativeDouble($base->y, $sender, $targetArgs[1], 0, 256);
				$z = $this->getRelativeDouble($base->z, $sender, $targetArgs[2]);
				$targetLocation = new Location($x, $y, $z, $yaw, $pitch, $base->getLevelNonNull());

				$subject->teleport($targetLocation);
				Command::broadcastCommandMessage($sender, new TranslationContainer("commands.tp.success.coordinates", [
					$subject->getName(),
					round($targetLocation->x, 2),
					round($targetLocation->y, 2),
					round($targetLocation->z, 2)
				]));
				return true;
			default:
				throw new AssumptionFailedError("This branch should be unreachable (for now)");
		}
	}
}