<?php

declare(strict_types=1);

namespace pocketmine\player;

use pocketmine\entity\Skin;
use pocketmine\Player;
use pocketmine\utils\UUID;

class PlayerInfo {

	/**
	 * @var Player $player
	 */
	private $player;
	/**
	 * @var array $extraData
	 */
	private $extraData;

	public function __construct(Player $player, array $extraData){
		$this->player = $player;
		$this->extraData = $extraData;
	}
	public function getUsername() : string{
		return $this->player->getName();
	}

	public function getUuid() : UUID {
		return $this->player->getUniqueId();
	}

	public function getSkin() : Skin{
		return $this->player->getSkin();
	}

	public function getLocale() : string{
		return $this->player->getLocale();
	}

	/**
	 * @return array
	 * @phpstan-return array<string, mixed>
	 */
	public function getExtraData() : array{
		return $this->extraData;
	}
}