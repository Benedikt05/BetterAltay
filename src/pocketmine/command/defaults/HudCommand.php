<?php


declare(strict_types=1);

namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandSelector;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\SetHudPacket;
use pocketmine\network\mcpe\protocol\types\CommandEnum;
use pocketmine\network\mcpe\protocol\types\CommandParameter;
use pocketmine\network\mcpe\protocol\types\hud\HudElement;
use pocketmine\network\mcpe\protocol\types\hud\HudVisibility;
use pocketmine\Player;
use function count;

class HudCommand extends VanillaCommand{

	public function __construct(string $name){
		parent::__construct(
			$name,
			"Changes the visibility of hud elements.",
			"/hud <target: target> <visible: HudVisibility> [hud_element: HudElement]", [], [
			[
				new CommandParameter("player", AvailableCommandsPacket::ARG_TYPE_TARGET, false),
				new CommandParameter("visible", AvailableCommandsPacket::ARG_TYPE_STRING, false, new CommandEnum("HudVisibility", ["hide", "reset"])),
				new CommandParameter("hud_element", AvailableCommandsPacket::ARG_TYPE_STRING, true, new CommandEnum("HudElement", ["air_bubbles", "all", "armor", "crosshair", "health", "horse_health", "hotbar", "hunger", "paperdoll", "progress_bar", "tooltips", "touch_controls"])),
			]
		]);
		$this->setPermission("altay.command.hud");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$this->testPermission($sender)){
			return true;
		}

		if(!$sender instanceof Player){
			$sender->sendMessage("This command must be executed as a player");
			return false;
		}

		if(count($args) < 2){
			throw new InvalidCommandSyntaxException();
		}

		$visibility = match ($args[1]) {
			"hide" => HudVisibility::HIDE,
			"reset" => HudVisibility::RESET,
			default => throw new InvalidCommandSyntaxException()
		};

		$hudElements = match ($args[2] ?? "all") {
			"air_bubbles" => [HudElement::AIR_BUBBLES],
			"armor" => [HudElement::ARMOR],
			"crosshair" => [HudElement::CROSSHAIR],
			"health" => [HudElement::HEALTH],
			"horse_health" => [HudElement::VEHICLE_HEALTH],
			"hotbar" => [HudElement::HOTBAR],
			"hunger" => [HudElement::FOOD],
			"paperdoll" => [HudElement::PAPER_DOLL],
			"progress_bar" => [HudElement::XP],
			"tooltips" => [HudElement::TOOLTIPS],
			"touch_controls" => [HudElement::TOUCH_CONTROLS],
			"all" => [
				HudElement::AIR_BUBBLES,
				HudElement::ARMOR,
				HudElement::CROSSHAIR,
				HudElement::HEALTH,
				HudElement::VEHICLE_HEALTH,
				HudElement::HOTBAR,
				HudElement::FOOD,
				HudElement::PAPER_DOLL,
				HudElement::XP,
				HudElement::TOOLTIPS,
				HudElement::TOUCH_CONTROLS
			],
			default => throw new InvalidCommandSyntaxException()
		};

		$pk = new SetHudPacket();
		$pk->hudElements = $hudElements;
		$pk->visibility = $visibility;
		/** @var Player[] $targets */
		$targets = CommandSelector::findTargets($sender, $args[0], Player::class);
		$sender->getServer()->broadcastPacket($targets, $pk);

		$sender->sendMessage("Hud command successfully executed");

		return true;
	}
}