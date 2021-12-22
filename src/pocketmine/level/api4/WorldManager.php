<?php

declare(strict_types=1);

namespace pocketmine\level\api4;

use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\Server;

class WorldManager
{

	/** @var int */
	private $autoSaveTicks = 6000;

	/*public function getProviderManager(): LevelProviderManager
	{
	}*/

	/**
	 * @return Level[]
	 */
	public function getWorlds() : array
	{
		return Server::getInstance()->getLevels();
	}

	public function getDefaultWorld() : ?Level
	{
		return Server::getInstance()->getDefaultLevel();
	}

	/**
	 * Sets the default world to a different world
	 * This won't change the level-name property,
	 * it only affects the server on runtime
	 */
	public function setDefaultWorld(?Level $world) : void
	{
		Server::getInstance()->setDefaultLevel($world);
	}

	public function isWorldLoaded(string $name) : bool
	{
		return Server::getInstance()->isLevelLoaded($name);
	}

	public function getWorld(int $worldId) : ?Level
	{
		return Server::getInstance()->getLevel($worldId);
	}

	/**
	 * NOTE: This matches worlds based on the FOLDER name, NOT the display name.
	 */
	public function getWorldByName(string $name) : ?Level
	{
		return Server::getInstance()->getLevelByName($name);
	}

	public function unloadWorld(Level $world, bool $forceUnload = false) : bool
	{
		return Server::getInstance()->unloadLevel($world, $forceUnload);
	}

	public function loadWorld(string $name) : bool
	{
		return Server::getInstance()->loadLevel($name);
	}

	public function generateWorld(string $name, WorldCreationOptions $options, /*We Don't Use That i only added that cuz pm4 plugins*/bool $backgroundGeneration = true) : bool
	{
		return Server::getInstance()->generateLevel($name, $options->getSeed(), $options->getGeneratorClass(), [$options->getGeneratorOptions()]);
	}

	public function isWorldGenerated(string $name) : bool
	{
		return Server::getInstance()->isLevelGenerated($name);
	}

	/**
	 * Searches all worlds for the entity with the specified ID.
	 * Useful for tracking entities across multiple worlds without needing strong references.
	 */
	public function findEntity(int $entityId) : ?Entity
	{
		return Server::getInstance()->findEntity($entityId);
	}

	public function getAutoSave() : bool
	{
		return Server::getInstance()->getAutoSave();
	}

	public function setAutoSave(bool $value) : void
	{
		Server::getInstance()->setAutoSave($value);
	}

	/**
	 * Returns the period in ticks after which loaded worlds will be automatically saved to disk.
	 */
	public function getAutoSaveInterval() : int
	{
		return $this->autoSaveTicks;
	}

	public function setAutoSaveInterval(int $autoSaveTicks) : void
	{
		if($autoSaveTicks <= 0){
			throw new \InvalidArgumentException("AutoSave ticks must be positive");
		}
		$this->autoSaveTicks = $autoSaveTicks;
	}

	private function doAutoSave() : void
	{
		Server::getInstance()->doAutoSave();
	}
}