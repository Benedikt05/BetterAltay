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

use pocketmine\nbt\NBT;
use pocketmine\nbt\NBTStream;
use pocketmine\nbt\ReaderTracker;
use pocketmine\utils\Binary;

class FloatTag extends NamedTag{
	/** @var float */
	private $value;

	public function __construct(string $name = "", float $value = 0.0){
		parent::__construct($name);
		$this->value = $value;
	}

	public function getType() : int{
		return NBT::TAG_Float;
	}

	public function read(NBTStream $nbt, ReaderTracker $tracker) : void{
		$this->value = $nbt->getFloat();
	}

	public function write(NBTStream $nbt) : void{
		$nbt->putFloat($this->value);
	}

	public function getValue() : float{
		return $this->value;
	}

	protected function equalsValue(NamedTag $that) : bool{
		//the values of TAG_Float are represented in 32 bits (single precision), so we don't want extra precision given
		//by 64-bit in-memory representation to break comparison (e.g. 0.3 != decode(encode(0.3)))
		//this intentionally truncates our value so that it compares as valid
		return $that instanceof $this && Binary::writeFloat($this->value) === Binary::writeFloat($that->value);
	}
}
