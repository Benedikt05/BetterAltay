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

namespace pocketmine;

use const PTHREADS_INHERIT_ALL;

/**
 * This class must be extended by all custom threading classes
 */
abstract class Thread extends \Thread{

	/** @var \ClassLoader|null */
	protected $classLoader;
	/** @var string|null */
	protected $composerAutoloaderPath;

	/** @var bool */
	protected $alive = true;

	/**
	 * @return \ClassLoader|null
	 */
	public function getClassLoader(){
		return $this->classLoader;
	}

	/**
	 * @return void
	 */
	public function setClassLoader(\ClassLoader $loader = null){
		$this->composerAutoloaderPath = \pocketmine\COMPOSER_AUTOLOADER_PATH;

		if($loader === null){
			$loader = Server::getInstance()->getLoader();
		}
		$this->classLoader = $loader;
	}

	/**
	 * Registers the class loader for this thread.
	 *
	 * WARNING: This method MUST be called from any descendent threads' run() method to make autoloading usable.
	 * If you do not do this, you will not be able to use new classes that were not loaded when the thread was started
	 * (unless you are using a custom autoloader).
	 *
	 * @return void
	 */
	public function registerClassLoader(){
		if($this->composerAutoloaderPath !== null){
			require_once $this->composerAutoloaderPath;
		}
		if($this->classLoader !== null){
			$this->classLoader->register(false);
		}
	}

	/**
	 * @return bool
	 */
	public function start(int $options = PTHREADS_INHERIT_ALL){
		ThreadManager::getInstance()->add($this);

		if($this->getClassLoader() === null){
			$this->setClassLoader();
		}
		return parent::start($options);
	}

	/**
	 * Stops the thread using the best way possible. Try to stop it yourself before calling this.
	 *
	 * @return void
	 */
	public function quit(){
		$this->alive = false;

		if(!$this->isJoined()){
			$this->notify();
			$this->join();
		}

		ThreadManager::getInstance()->remove($this);
	}

	public function getThreadName() : string{
		return (new \ReflectionClass($this))->getShortName();
	}
}
