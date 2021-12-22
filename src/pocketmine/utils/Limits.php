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

namespace pocketmine\utils;

final class Limits{

	private function __construct(){
		//NOOP
	}

	public const UINT8_MAX = 0xff;
	public const INT8_MIN = -0x7f - 1;
	public const INT8_MAX = 0x7f;

	public const UINT16_MAX = 0xffff;
	public const INT16_MIN = -0x7fff - 1;
	public const INT16_MAX = 0x7fff;

	public const UINT32_MAX = 0xffffffff;
	public const INT32_MIN = -0x7fffffff - 1;
	public const INT32_MAX = 0x7fffffff;

	public const UINT64_MAX = 0xffffffffffffffff;
	public const INT64_MIN = -0x7fffffffffffffff - 1;
	public const INT64_MAX = 0x7fffffffffffffff;
}