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

namespace pocketmine\nbt;

use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntArrayTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\NamedTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\Binary;
use pocketmine\utils\BinaryDataException;
use function call_user_func;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_numeric;
use function is_string;
use function strlen;
use function substr;
use function zlib_decode;
use function zlib_encode;

/**
 * Base Named Binary Tag encoder/decoder
 */
abstract class NBTStream{
	/** @var string */
	public $buffer = "";
	/** @var int */
	public $offset = 0;

	/**
	 * @param int|true $len
	 *
	 * @throws BinaryDataException if there are not enough bytes left in the buffer
	 */
	public function get($len) : string{
		if($len === 0){
			return "";
		}

		$buflen = strlen($this->buffer);
		if($len === true){
			$str = substr($this->buffer, $this->offset);
			$this->offset = $buflen;
			return $str;
		}
		if($len < 0){
			$this->offset = $buflen - 1;
			return "";
		}
		$remaining = $buflen - $this->offset;
		if($remaining < $len){
			throw new BinaryDataException("Not enough bytes left in buffer: need $len, have $remaining");
		}

		return $len === 1 ? $this->buffer[$this->offset++] : substr($this->buffer, ($this->offset += $len) - $len, $len);
	}

	public function put(string $v) : void{
		$this->buffer .= $v;
	}

	public function feof() : bool{
		return !isset($this->buffer[$this->offset]);
	}

	/**
	 * Decodes NBT from the given binary string and returns it.
	 *
	 * @param bool   $doMultiple Whether to keep reading after the first tag if there are more bytes in the buffer
	 * @param int    $offset reference parameter
	 *
	 * @return NamedTag|NamedTag[]
	 */
	public function read(string $buffer, bool $doMultiple = false, int &$offset = 0, int $maxDepth = 0){
		$this->offset = &$offset;
		$this->buffer = $buffer;
		$data = $this->readTag(new ReaderTracker($maxDepth));

		if($data === null){
			throw new \UnexpectedValueException("Found TAG_End at the start of buffer");
		}

		if($doMultiple and !$this->feof()){
			$data = [$data];
			do{
				$tag = $this->readTag(new ReaderTracker($maxDepth));
				if($tag !== null){
					$data[] = $tag;
				}
			}while(!$this->feof());
		}
		$this->buffer = "";

		return $data;
	}

	/**
	 * Decodes NBT from the given compressed binary string and returns it. Anything decodable by zlib_decode() can be
	 * processed.
	 *
	 * @return NamedTag|NamedTag[]
	 */
	public function readCompressed(string $buffer){
		$decompressed = zlib_decode($buffer);
		if($decompressed === false){
			throw new \UnexpectedValueException("Failed to decompress data");
		}
		return $this->read($decompressed);
	}

	/**
	 * @param NamedTag|NamedTag[] $data
	 *
	 * @return false|string
	 */
	public function write($data){
		$this->offset = 0;
		$this->buffer = "";

		if($data instanceof NamedTag){
			$this->writeTag($data);

			return $this->buffer;
		}elseif(is_array($data)){
			foreach($data as $tag){
				$this->writeTag($tag);
			}
			return $this->buffer;
		}

		return false;
	}

	/**
	 * @param NamedTag|NamedTag[] $data
	 *
	 * @return false|string
	 */
	public function writeCompressed($data, int $compression = ZLIB_ENCODING_GZIP, int $level = 7){
		if(($write = $this->write($data)) !== false){
			return zlib_encode($write, $compression, $level);
		}

		return false;
	}

	public function readTag(ReaderTracker $tracker) : ?NamedTag{
		$tagType = $this->getByte();
		if($tagType === NBT::TAG_End){
			return null;
		}

		$tag = NBT::createTag($tagType);
		$tag->setName($this->getString());
		$tag->read($this, $tracker);

		return $tag;
	}

	public function writeTag(NamedTag $tag) : void{
		$this->putByte($tag->getType());
		$this->putString($tag->getName());
		$tag->write($this);
	}

	public function writeEnd() : void{
		$this->putByte(NBT::TAG_End);
	}

	public function getByte() : int{
		return Binary::readByte($this->get(1));
	}

	public function getSignedByte() : int{
		return Binary::readSignedByte($this->get(1));
	}

	public function putByte(int $v) : void{
		$this->buffer .= Binary::writeByte($v);
	}

	abstract public function getShort() : int;

	abstract public function getSignedShort() : int;

	abstract public function putShort(int $v) : void;

	abstract public function getInt() : int;

	abstract public function putInt(int $v) : void;

	abstract public function getLong() : int;

	abstract public function putLong(int $v) : void;

	abstract public function getFloat() : float;

	abstract public function putFloat(float $v) : void;

	abstract public function getDouble() : float;

	abstract public function putDouble(float $v) : void;

	/**
	 * @throws \UnexpectedValueException if a too-large string is found (length may be invalid)
	 */
	public function getString() : string{
		return $this->get(self::checkReadStringLength($this->getShort()));
	}

	/**
	 * @throws \InvalidArgumentException if the string is too long
	 */
	public function putString(string $v) : void{
		$this->putShort(self::checkWriteStringLength(strlen($v)));
		$this->put($v);
	}

	/**
	 * @throws \UnexpectedValueException
	 */
	protected static function checkReadStringLength(int $len) : int{
		if($len > 32767){
			throw new \UnexpectedValueException("NBT string length too large ($len > 32767)");
		}
		return $len;
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	protected static function checkWriteStringLength(int $len) : int{
		if($len > 32767){
			throw new \InvalidArgumentException("NBT string length too large ($len > 32767)");
		}
		return $len;
	}

	/**
	 * @return int[]
	 */
	abstract public function getIntArray() : array;

	/**
	 * @param int[] $array
	 */
	abstract public function putIntArray(array $array) : void;

	/**
	 * @return mixed[]
	 * @phpstan-return array<string, mixed>
	 */
	public static function toArray(CompoundTag $data) : array{
		$array = [];
		self::tagToArray($array, $data);
		return $array;
	}

	/**
	 * @param mixed[]                         $data
	 * @param CompoundTag|ListTag $tag
	 */
	private static function tagToArray(array &$data, NamedTag $tag) : void{
		foreach($tag as $key => $value){
			if($value instanceof CompoundTag or $value instanceof ListTag){
				$data[$key] = [];
				self::tagToArray($data[$key], $value);
			}else{
				$data[$key] = $value->getValue();
			}
		}
	}

	/**
	 * @param mixed $value
	 */
	public static function fromArrayGuesser(string $key, $value) : ?NamedTag{
		if(is_int($value)){
			return new IntTag($key, $value);
		}elseif(is_float($value)){
			return new FloatTag($key, $value);
		}elseif(is_string($value)){
			return new StringTag($key, $value);
		}elseif(is_bool($value)){
			return new ByteTag($key, $value ? 1 : 0);
		}

		return null;
	}

	/**
	 * @param mixed $value
	 * @phpstan-param callable(string $key, mixed $value) : ?NamedTag $guesser
	 */
	private static function parseArrayValue(string $name, $value, callable $guesser) : ?NamedTag{
		if(is_array($value)){
			$isNumeric = true;
			$isIntArray = true;
			foreach($value as $k => $v){
				if(!is_numeric($k)){
					$isNumeric = false;
					break;
				}elseif(!is_int($v)){
					$isIntArray = false;
				}
			}

			if($isNumeric && $isIntArray){
				return new IntArrayTag($name, $value);
			}elseif($isNumeric){
				$node = new ListTag($name, []);
				foreach($value as $v){
					$vtag = self::parseArrayValue("", $v, $guesser);
					//TODO: this will throw a TypeError if wrong tags are encountered after the first one
					if($vtag !== null){
						$node->push($vtag);
					}
				}
				return $node;
			}else{
				$node = new CompoundTag($name, []);
				foreach($value as $k => $v){
					$vtag = self::parseArrayValue((string) $k, $value, $guesser);
					if($vtag !== null){
						$node->setTag($vtag);
					}
				}
				return $node;
			}
		}else{
			return call_user_func($guesser, $name, $value);
		}
	}

	/**
	 * @param mixed[] $data
	 * @phpstan-param (callable(string $key, mixed $value) : ?NamedTag)|null $guesser
	 */
	public static function fromArray(array $data, callable $guesser = null) : CompoundTag{
		$tag = new CompoundTag("", []);
		foreach($data as $k => $v){
			$vtag = self::parseArrayValue($k, $v, $guesser ?? [self::class, "fromArrayGuesser"]);
			if($vtag !== null){
				$tag->setTag($vtag);
			}
		}
		return $tag;
	}
}
