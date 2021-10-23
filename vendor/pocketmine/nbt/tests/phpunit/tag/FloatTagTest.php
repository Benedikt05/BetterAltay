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

namespace pocketmine\nbt\tag;

use PHPUnit\Framework\TestCase;
use pocketmine\nbt\LittleEndianNBTStream;
use const PHP_FLOAT_EPSILON;
use const PHP_FLOAT_MAX;
use const PHP_FLOAT_MIN;

class FloatTagTest extends TestCase{

	public function testValue() : void{
		$value = mt_rand() / mt_getrandmax();

		$tag = new FloatTag("", $value);
		self::assertSame($value, $tag->getValue());
	}

	/**
	 * @phpstan-return \Generator<int, array{float}, void, void>
	 */
	public function equalityAfterDecodeProvider() : \Generator{
		yield [0.3];
		yield [PHP_FLOAT_EPSILON];
		yield [PHP_FLOAT_MAX];
		yield [PHP_FLOAT_MIN];
	}

	/**
	 * @dataProvider equalityAfterDecodeProvider
	 */
	public function testEqualityAfterDecode(float $value) : void{
		$tag = new FloatTag("", $value);
		$serializer = new LittleEndianNBTStream();
		$tag2 = $serializer->read($serializer->write($tag));
		self::assertTrue($tag->equals($tag2));
	}
}
