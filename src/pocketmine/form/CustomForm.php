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

class CustomForm implements Form {

    private string $title = "";
    
    private array $elements = [];
    
    private ?\Closure $callback = null;

    public function setTitle(string $title) : self{
        $this->title = $title;
        return $this;
    }

    public function addLabel(string $text) : self{
        $this->elements[] = ["type" => "label", "text" => $text];
        return $this;
    }

    public function addInput(string $text, string $placeholder = "", string $default = "") : self{
        $this->elements[] = [
            "type" => "input",
            "text" => $text,
            "placeholder" => $placeholder,
            "default" => $default
        ];
        return $this;
    }

    public function addToggle(string $text, bool $default = false) : self{
        $this->elements[] = [
            "type" => "toggle",
            "text" => $text,
            "default" => $default
        ];
        return $this;
    }

    public function addDropdown(string $text, array $options, int $default = 0) : self{
        $this->elements[] = [
            "type" => "dropdown",
            "text" => $text,
            "options" => $options,
            "default" => $default
        ];
        return $this;
    }

    public function addSlider(string $text, float $min, float $max, float $step = 1.0, float $default = 0) : self{
        $this->elements[] = [
            "type" => "slider",
            "text" => $text,
            "min" => $min,
            "max" => $max,
            "step" => $step,
            "default" => $default
        ];
        return $this;
    }

    public function addStepSlider(string $text, array $steps, int $default = 0) : self{
        $this->elements[] = [
            "type" => "step_slider",
            "text" => $text,
            "steps" => $steps,
            "default" => $default
        ];
        return $this;
    }

    public function setCallback(\Closure $callback) : self{
        $this->callback = $callback;
        return $this;
    }

    public function handleResponse(Player $player, $data) : void{
        if ($this->callback !== null) {
            ($this->callback)($player, $data);
        }
    }

    public function jsonSerialize() : array{
        return [
            "type" => "custom_form",
            "title" => $this->title,
            "content" => $this->elements
        ];
    }
}