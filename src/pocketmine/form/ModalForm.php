<?php

/*
 *  ____       _   _                   _ _
 * |  _ \     | | | |            /\   | | |
 * | |_) | ___| |_| |_ ___ _ __ /  \  | | |_ __ _ _   _
 * |  _ < / _ \ __| __/ _ \ '__/ /\ \ | | __/ _` | | | |
 * | |_) |  __/ |_| ||  __/ | / ____ \| | || (_| | |_| |
 * |____/ \___|\__|\__\___|_|/_/    \_\_|\__\__,_|\__, |
 *                                                 __/ |
 *                                                |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author BetterAltay Team
 * @link https://github.com/Benedikt05/BetterAltay/
 */

declare(strict_types=1);

namespace pocketmine\form;

use pocketmine\Player;

use function is_bool;

class ModalForm implements Form{

	private string $title = "";

	private string $content = "";

	private string $button1 = "yes";
	private string $button2 = "no";

	private \Closure $callback;

	public function __construct(){
		$this->callback = function(Player $player, bool $data) : void{ };
	}

	public function setTitle(string $title) : self{
		$this->title = $title;
		return $this;
	}

	public function setContent(string $content) : self{
		$this->content = $content;
		return $this;
	}

	public function setButton1(string $button1) : self{
		$this->button1 = $button1;
		return $this;
	}

	public function setButton2(string $button2) : self{
		$this->button2 = $button2;
		return $this;
	}

	public function setCallback(\Closure $callback) : self{
		$this->callback = $callback;
		return $this;
	}

	public function handleResponse(Player $player, $data) : void{
		if(is_bool($data)){
			($this->callback)($player, $data);
		}
	}

	public function jsonSerialize() : array{
		return [
			"type" => "modal",
			"title" => $this->title,
			"content" => $this->content,
			"button1" => $this->button1,
			"button2" => $this->button2
		];
	}
}
