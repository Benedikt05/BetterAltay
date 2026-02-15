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

namespace pocketmine\entity\utils;

use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\Player;
use InvalidArgumentException;

/*
 * This is a Helper class to create a simple Bossbar
 * Note: This is not an entity
 */

class Bossbar extends Vector3 {
    
    public const PINK = 0;
	public const BLUE = 1;
	public const RED = 2;
	public const GREEN = 3;
	public const YELLOW = 4;
	public const PURPLE = 5;
	public const REBECCA_PURPLE = 6;
	public const WHITE = 7;
	
	/** @var array */
	public $colors = [
	    self::PINK,
	    self::BLUE,
	    self::RED,
	    self::GREEN,
	    self::YELLOW,
	    self::PURPLE,
	    self::REBECCA_PURPLE,
	    self::WHITE
    ];

	/** @var int */
	protected $entityId;

	/** @var string */
	protected $title;

	/** @var float */
	protected $healthPercent;
	
	/** @var int */
	protected $color;

	/** @var Player[] */
	protected $viewers = [];

	public function __construct(string $title = "", float $hp = 1.0, int $color = self::PURPLE) {
		parent::__construct(0, 0, 0);

		$this->entityId = Entity::$entityCount++;
		$this->title = $title;
		$this->color = $color;
		$this->setHealthPercent($hp, false);
	}

	public function setTitle(string $title, bool $update = true) : void{
		$this->title = $title;

		if ($update) {
			$this->updateForAll();
		}
	}

	public function getTitle() : string{
		return $this->title;
	}

	/**
	 * @param float $hp This should be in 0.0-1.0 range
	 * @param bool  $update
	 */
	public function setHealthPercent(float $hp, bool $update = true) : void{
		$this->healthPercent = max(0, min(1.0, $hp));

		if ($update) {
			$this->updateForAll();
		}
	}

	public function getHealthPercent() : float{
		return $this->healthPercent;
	}
	
	public function setColor(int $color, bool $update = true) : void{
        $this->color = $color;

        if ($update) {
            foreach ($this->viewers as $player) {
                $this->sendBossEventPacket($player, BossEventPacket::TYPE_HIDE);
                $this->sendBossEventPacket($player, BossEventPacket::TYPE_SHOW);
            }
        }
    }
	
	public function getColor() : int{
	    return $this->color;
	}
	
	public static function getColorByName(string $name) : int{
        return match ($name) {
            "pink", "PINK" => self::PINK,
            "blue", "BLUE" => self::BLUE,
            "red", "RED" => self::RED,
            "green", "GREEN" => self::GREEN,
            "yellow", "YELLOW" => self::YELLOW,
            "purple", "PURPLE" => self::PURPLE,
            "rebecca_purple", "REBECCA_PURPLE" => self::REBECCA_PURPLE,
            "white", "WHITE" => self::WHITE,
            default => throw new InvalidArgumentException("Invalid bossbar color: " . $name),
        };
    }
    
    public static function getColorByID(int $id) : string{
        return match ($id) {
            self::PINK => "PINK",
            self::BLUE => "BLUE",
            self::RED => "RED",
            self::GREEN => "GREEN",
            self::YELLOW => "YELLOW",
            self::PURPLE => "PURPLE",
            self::REBECCA_PURPLE => "REBECCA_PURPLE",
            self::WHITE => "WHITE",
            default => throw new InvalidArgumentException("Invalid bossbar color ID: " . $id),
        };
    }

	public function showTo(Player $player, bool $isViewer = true) : void{
		$pk = new AddActorPacket();
		$pk->entityRuntimeId = $this->entityId;
		$pk->type = AddActorPacket::LEGACY_ID_MAP_BC[EntityIds::SLIME];
		$pk->metadata = [
			Entity::DATA_FLAGS => [
				Entity::DATA_TYPE_LONG,
				((1 << Entity::DATA_FLAG_INVISIBLE) | (1 << Entity::DATA_FLAG_IMMOBILE))
			],
			Entity::DATA_NAMETAG => [
				Entity::DATA_TYPE_STRING,
				$this->title
			]
		];
		$pk->position = $this;

		$player->sendDataPacket($pk);
		$this->sendBossEventPacket($player, BossEventPacket::TYPE_SHOW);

		if ($isViewer) {
			$this->viewers[spl_object_id($player)] = $player;
		}
	}

	public function hideFrom(Player $player) : void{
		$this->sendBossEventPacket($player, BossEventPacket::TYPE_HIDE);

		$pk2 = new RemoveActorPacket();
		$pk2->entityUniqueId = $this->entityId;

		$player->sendDataPacket($pk2);

		if (isset($this->viewers[spl_object_id($player)])) {
			unset($this->viewers[spl_object_id($player)]);
		}
	}

	public function updateFor(Player $player) : void{
		$this->sendBossEventPacket($player, BossEventPacket::TYPE_HEALTH_PERCENT);
		$this->sendBossEventPacket($player, BossEventPacket::TYPE_TITLE);
	}

	public function updateForAll() : void{
		foreach ($this->viewers as $player) {
			$this->updateFor($player);
		}
	}

	protected function sendBossEventPacket(Player $player, int $eventType) : void{
		$pk = new BossEventPacket();
		$pk->bossEid = $this->entityId;
		$pk->eventType = $eventType;

		switch ($eventType) {
			case BossEventPacket::TYPE_SHOW:
				$pk->title = $this->title;
				$pk->filteredTitle = $this->title;
				$pk->healthPercent = $this->healthPercent;
				$pk->color = $this->color;
				$pk->overlay = 0;
				$pk->darkenScreen = 0;
				break;
			case BossEventPacket::TYPE_REGISTER_PLAYER:
			case BossEventPacket::TYPE_UNREGISTER_PLAYER:
				$pk->playerEid = $player->getId();
				break;
			case BossEventPacket::TYPE_TITLE:
				$pk->title = $this->title;
				$pk->filteredTitle = $this->title;
				break;
			case BossEventPacket::TYPE_HEALTH_PERCENT:
				$pk->healthPercent = $this->healthPercent;
				break;
		}

		$player->sendDataPacket($pk);
	}

	public function getViewers() : array{
		return $this->viewers;
	}

	public function getEntityId() : int{
		return $this->entityId;
	}
}
