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

class SimpleForm implements Form{

	private string $title = "";

	private string $content = "";

	private array $buttons = [];

	private ?\Closure $callback = null;

	public function setTitle(string $title) : self{
		$this->title = $title;
		return $this;
	}

	public function setContent(string $content) : self{
		$this->content = $content;
		return $this;
	}

	public function addButton(string $text, int $imageType = -1, string $imagePath = "") : self{
		$button = ["text" => $text];

		if($imageType !== -1){
			$button["image"] = [
				"type" => $imageType === 0 ? "path" : "url",
				"data" => $imagePath
			];
		}

		$this->buttons[] = $button;
		return $this;
	}

	public function setCallback(\Closure $callback) : self{
		$this->callback = $callback;
		return $this;
	}

	public function handleResponse(Player $player, $data) : void{
		if($this->callback !== null){
			($this->callback)($player, $data);
		}
	}

	public function jsonSerialize() : array{
		return [
			"type" => "form",
			"title" => $this->title,
			"content" => $this->content,
			"buttons" => $this->buttons
		];
	}
}