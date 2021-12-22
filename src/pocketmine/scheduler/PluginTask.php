<?php

declare(strict_types=1);

namespace pocketmine\scheduler;

use pocketmine\plugin\Plugin;

abstract class PluginTask extends Task{

	private Plugin $plugin;

	public function __construct(Plugin $plugin){
		$this->plugin = $plugin;
	}

	public function getPlugin(){
		return $this->plugin;
	}
}