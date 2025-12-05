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

namespace pocketmine\network\mcpe\protocol\types;

use pocketmine\utils\UUID;

class CommandOriginData{
	public const ORIGIN_PLAYER = "player";
	public const ORIGIN_AUTOMATION_PLAYER = "automationplayer";
	public const ORIGIN_TEST = "test";
	public const ORIGIN_DEV_CONSOLE = "devconsole";
	public const ORIGIN_ENTITY = "entity";
	public const ORIGIN_COMMAND_BLOCK = "commandblock";
	public const ORIGIN_MINECART_COMMAND_BLOCK = "minecartcommandblock";
	public const ORIGIN_CLIENT_AUTOMATION = "clientautomation";
	public const ORIGIN_DEDICATED_SERVER = "dedicatedserver";
	public const ORIGIN_GAME_ARGUMENT = "gameargument";
	public const ORIGIN_SCRIPTING = "scripting";
	public const ORIGIN_VIRTUAL = "virtual";
	public const ORIGIN_EXECUTE_CONTENT = "executecontext";

	public string $type;
	public UUID $uuid;
	public string $requestId;
	public int $playerEntityUniqueId;
}
