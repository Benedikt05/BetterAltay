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

namespace pocketmine\entity\passive;

use pocketmine\entity\ClimateEntity;
use pocketmine\entity\ClimateTrait;

class Cow extends BaseCow implements ClimateEntity{
	use ClimateTrait;

	public const NETWORK_ID = self::COW;

	protected function initEntity() : void{
		parent::initEntity();
		$this->initClimateNBT();
	}

	public function saveNBT() : void{
		parent::saveNBT();
		$this->saveClimateNBT();
	}

	public function getName() : string{
		return "Cow";
	}
}