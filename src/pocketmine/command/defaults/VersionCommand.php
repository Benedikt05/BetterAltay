<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\lang\TranslationContainer;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\CommandParameter;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use function count;
use function implode;
use function stripos;
use function strtolower;

class VersionCommand extends VanillaCommand{

	public function __construct(string $name){
		parent::__construct($name, "%pocketmine.command.version.description", "%pocketmine.command.version.usage", [
			"ver", "about"
		], [
			[
				new CommandParameter("plugin", AvailableCommandsPacket::ARG_TYPE_STRING)
			]
		]);
		$this->setPermission("pocketmine.command.version");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}

		if(count($args) === 0){
			$sender->sendMessage("This server is running " . $sender->getServer()->getName() . " " . $sender->getServer()->getBetterAltayVersion() . " based on PocketMine-MP " . $sender->getServer()->getPocketMineVersion() . " for Minecraft: Bedrock Edition " . $sender->getServer()->getVersion() . " (protocol version " . ProtocolInfo::CURRENT_PROTOCOL . ")");
			$sender->sendMessage("PHP version: " . PHP_VERSION);
			/*$sender->sendMessage(new TranslationContainer("pocketmine.server.info.extended", [
				$sender->getServer()->getName(),
				$sender->getServer()->getBetterAltayVersion(),
				$sender->getServer()->getPocketMineVersion(),
				$sender->getServer()->getVersion(),
				ProtocolInfo::CURRENT_PROTOCOL
			]));*/
			//TODO: Update all lang files
		}else{
			$pluginName = implode(" ", $args);
			$exactPlugin = $sender->getServer()->getPluginManager()->getPlugin($pluginName);

			if($exactPlugin instanceof Plugin){
				$this->describeToSender($exactPlugin, $sender);

				return true;
			}

			$found = false;
			$pluginName = strtolower($pluginName);
			foreach($sender->getServer()->getPluginManager()->getPlugins() as $plugin){
				if(stripos($plugin->getName(), $pluginName) !== false){
					$this->describeToSender($plugin, $sender);
					$found = true;
				}
			}

			if(!$found){
				$sender->sendMessage(new TranslationContainer("pocketmine.command.version.noSuchPlugin"));
			}
		}

		return true;
	}

	private function describeToSender(Plugin $plugin, CommandSender $sender) : void{
		$desc = $plugin->getDescription();
		$sender->sendMessage(TextFormat::DARK_GREEN . $desc->getName() . TextFormat::WHITE . " version " . TextFormat::DARK_GREEN . $desc->getVersion());

		if($desc->getDescription() !== ""){
			$sender->sendMessage($desc->getDescription());
		}

		if($desc->getWebsite() !== ""){
			$sender->sendMessage("Website: " . $desc->getWebsite());
		}

		if(count($authors = $desc->getAuthors()) > 0){
			if(count($authors) === 1){
				$sender->sendMessage("Author: " . implode(", ", $authors));
			}else{
				$sender->sendMessage("Authors: " . implode(", ", $authors));
			}
		}
	}
}
