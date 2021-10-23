<?php

/*
 * DevTools plugin for PocketMine-MP
 * Copyright (C) 2014 PocketMine Team <https://github.com/PocketMine/DevTools>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
*/

declare(strict_types=1);

namespace DevTools\commands;

use DevTools\DevTools;
use pocketmine\command\Command;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;

abstract class DevToolsCommand extends Command implements PluginIdentifiableCommand{
	/** @var DevTools */
	private $owningPlugin;

	public function __construct(string $name, DevTools $plugin){
		parent::__construct($name);
		$this->owningPlugin = $plugin;
		$this->usageMessage = "";
	}

	/**
	 * @return DevTools
	 */
	public function getPlugin() : Plugin{
		return $this->owningPlugin;
	}
}
