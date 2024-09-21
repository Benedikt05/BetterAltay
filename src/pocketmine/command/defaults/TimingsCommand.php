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
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\CommandEnum;
use pocketmine\network\mcpe\protocol\types\CommandParameter;
use pocketmine\player\Player;
use pocketmine\scheduler\BulkCurlTask;
use pocketmine\Server;
use pocketmine\timings\TimingsHandler;
use pocketmine\utils\InternetException;
use function count;
use function fclose;
use function file_exists;
use function fopen;
use function fseek;
use function http_build_query;
use function is_array;
use function json_decode;
use function mkdir;
use function stream_get_contents;
use function strtolower;
use const CURLOPT_AUTOREFERER;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;

class TimingsCommand extends VanillaCommand{

	public function __construct(string $name){
		parent::__construct($name, "%pocketmine.command.timings.description", "%pocketmine.command.timings.usage", [], [
			[
				new CommandParameter("args", AvailableCommandsPacket::ARG_TYPE_STRING, false, new CommandEnum("args", [
					"on", "off", "paste", "reset", "merged", "report"
				]))
			]
		]);
		$this->setPermission("pocketmine.command.timings");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}

		if(count($args) !== 1){
			throw new InvalidCommandSyntaxException();
		}

		$mode = strtolower($args[0]);

		if($mode === "on"){
			TimingsHandler::setEnabled();
			Command::broadcastCommandMessage($sender, new TranslationContainer("pocketmine.command.timings.enable"));

			return true;
		}elseif($mode === "off"){
			TimingsHandler::setEnabled(false);
			Command::broadcastCommandMessage($sender, new TranslationContainer("pocketmine.command.timings.disable"));
			return true;
		}

		if(!TimingsHandler::isEnabled()){
			$sender->sendMessage(new TranslationContainer("pocketmine.command.timings.timingsDisabled"));

			return true;
		}

		$paste = $mode === "paste";

		if($mode === "reset"){
			TimingsHandler::reload();
			Command::broadcastCommandMessage($sender, new TranslationContainer("pocketmine.command.timings.reset"));
		}elseif($mode === "merged" or $mode === "report" or $paste){
			$timings = "";
			if($paste){
				$fileTimings = fopen("php://temp", "r+b");
			}else{
				$index = 0;
				$timingFolder = $sender->getServer()->getDataPath() . "timings/";

				if(!file_exists($timingFolder)){
					mkdir($timingFolder, 0777);
				}
				$timings = $timingFolder . "timings.txt";
				while(file_exists($timings)){
					$timings = $timingFolder . "timings" . (++$index) . ".txt";
				}

				$fileTimings = fopen($timings, "a+b");
			}
			TimingsHandler::printTimings($fileTimings);

			if($paste){
				fseek($fileTimings, 0);
				$data = [
					"browser" => $agent = $sender->getServer()->getName() . " " . $sender->getServer()->getPocketMineVersion(),
					"data" => $content = stream_get_contents($fileTimings)
				];
				fclose($fileTimings);

				$host = $sender->getServer()->getProperty("timings.host", "timings.pmmp.io");

				$sender->getServer()->getAsyncPool()->submitTask(new class($sender, $host, $agent, $data) extends BulkCurlTask{
					/** @var string */
					private $host;

					/**
					 * @param string[]                      $data
					 *
					 * @phpstan-param array<string, string> $data
					 */
					public function __construct(CommandSender $sender, string $host, string $agent, array $data){
						parent::__construct([
							[
								"page" => "https://$host?upload=true",
								"extraOpts" => [
									CURLOPT_HTTPHEADER => [
										"User-Agent: $agent",
										"Content-Type: application/x-www-form-urlencoded"
									],
									CURLOPT_POST => true,
									CURLOPT_POSTFIELDS => http_build_query($data),
									CURLOPT_AUTOREFERER => false,
									CURLOPT_FOLLOWLOCATION => false
								]
							]
						], $sender);
						$this->host = $host;
					}

					public function onCompletion(Server $server){
						/** @var CommandSender $sender */
						$sender = $this->fetchLocal();
						if($sender instanceof Player and !$sender->isOnline()){ // TODO replace with a more generic API method for checking availability of CommandSender
							return;
						}
						$result = $this->getResult()[0];
						if($result instanceof InternetException){
							$server->getLogger()->logException($result);
							return;
						}
						$response = json_decode($result[0], true);
						if(is_array($response) && isset($response["id"])){
							Command::broadcastCommandMessage($sender, new TranslationContainer("pocketmine.command.timings.timingsRead",
								["https://" . $this->host . "/?id=" . $response["id"]]));
						}else{
							Command::broadcastCommandMessage($sender, new TranslationContainer("pocketmine.command.timings.pasteError"));
						}
					}
				});
			}else{
				fclose($fileTimings);
				Command::broadcastCommandMessage($sender, new TranslationContainer("pocketmine.command.timings.timingsWrite", [$timings]));
			}
		}else{
			throw new InvalidCommandSyntaxException();
		}

		return true;
	}
}