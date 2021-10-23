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

namespace pocketmine\snooze;

use function assert;

/**
 * Notifiable Threaded class which tracks counts of notifications it receives.
 */
class ThreadedSleeper extends \Threaded{
	/**
	 * @var int
	 */
	private $notifCount = 0;

	/**
	 * Called from the main thread to wait for notifications, or until timeout.
	 *
	 * @param int $timeout defaults to 0 (no timeout, wait indefinitely)
	 */
	public function sleep(int $timeout = 0) : void{
		$this->synchronized(function(int $timeout) : void{
			assert($this->notifCount >= 0, "notification count should be >= 0, got $this->notifCount");
			if($this->notifCount === 0){
				$this->wait($timeout);
			}
		}, $timeout);
	}

	/**
	 * @internal
	 * Called by SleeperNotifier to send a notification to the main thread.
	 * This MUST be called while synchronized with this object.
	 */
	public function wakeupNoSync() : void{
		++$this->notifCount;
		$this->notify();
	}

	/**
	 * @internal
	 * Called by SleeperNotifier to decrement refcount when notifications are cleared.
	 * This MUST be called while synchronized with this object.
	 */
	public function clearNotificationNoSync() : void{
		--$this->notifCount;
	}

	public function hasNotifications() : bool{
		//don't need to synchronize here, pthreads automatically locks/unlocks
		return $this->notifCount > 0;
	}
}
