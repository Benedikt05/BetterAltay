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

namespace pocketmine\level;

use pocketmine\level\format\Chunk;
use const INT32_MAX;
use const INT32_MIN;

class SimpleChunkManager implements ChunkManager{

	/** @var Chunk[] */
	protected $chunks = [];

	/** @var int */
	protected $seed;
	/** @var int */
	protected $worldMaxHeight;
	/** @var int */
	protected $worldMinHeight;

	/**
	 * SimpleChunkManager constructor.
	 */
	public function __construct(int $seed, int $maxHeight = Level::Y_MAX, $minHeight = Level::Y_MIN){
		$this->seed = $seed;
		$this->worldMaxHeight = $maxHeight;
		$this->worldMinHeight = $minHeight;
	}

	/**
	 * Gets the runtime ID of the block at the specified coordinates.
	 *
	 * @return int
	 */
	public function getBlockIdAt(int $x, int $y, int $z, int $layer = 0) : int{
		if(($chunk = $this->getChunk($x >> 4, $z >> 4)) !== null){
			return $chunk->getBlockId($x & Chunk::COORD_MASK, $y, $z & Chunk::COORD_MASK, $layer);
		}
		return 0;
	}

	/**
	 * Sets the runtime ID of the block at the specified coordinates.
	 *
	 * @return void
	 */
	public function setBlockIdAt(int $x, int $y, int $z, int $id, int $layer = 0) : void{
		if(($chunk = $this->getChunk($x >> 4, $z >> 4)) !== null){
			$chunk->setBlockId($x & Chunk::COORD_MASK, $y, $z & Chunk::COORD_MASK, $id, $layer);
		}
	}

	public function getBlockLightAt(int $x, int $y, int $z) : int{
		if(($chunk = $this->getChunk($x >> 4, $z >> 4)) !== null){
			return $chunk->getBlockLight($x & 0xf, $y, $z & 0xf);
		}

		return 0;
	}

	public function setBlockLightAt(int $x, int $y, int $z, int $level){
		if(($chunk = $this->getChunk($x >> 4, $z >> 4)) !== null){
			$chunk->setBlockLight($x & 0xf, $y, $z & 0xf, $level);
		}
	}

	public function getBlockSkyLightAt(int $x, int $y, int $z) : int{
		if(($chunk = $this->getChunk($x >> 4, $z >> 4)) !== null){
			return $chunk->getBlockSkyLight($x & 0xf, $y, $z & 0xf);
		}

		return 0;
	}

	public function setBlockSkyLightAt(int $x, int $y, int $z, int $level){
		if(($chunk = $this->getChunk($x >> 4, $z >> 4)) !== null){
			$chunk->setBlockSkyLight($x & 0xf, $y, $z & 0xf, $level);
		}
	}

	/**
	 * @return Chunk|null
	 */
	public function getChunk(int $chunkX, int $chunkZ){
		return $this->chunks[Level::chunkHash($chunkX, $chunkZ)] ?? null;
	}

	/**
	 * @return void
	 */
	public function setChunk(int $chunkX, int $chunkZ, Chunk $chunk = null){
		if($chunk === null){
			unset($this->chunks[Level::chunkHash($chunkX, $chunkZ)]);
			return;
		}
		$this->chunks[Level::chunkHash($chunkX, $chunkZ)] = $chunk;
	}

	/**
	 * @return void
	 */
	public function cleanChunks(){
		$this->chunks = [];
	}

	/**
	 * Gets the level seed
	 */
	public function getSeed() : int{
		return $this->seed;
	}

	public function getWorldMaxHeight() : int{
		return $this->worldMaxHeight;
	}

	public function getWorldMinHeight() : int{
		return $this->worldMinHeight;
	}

	public function isInWorld(int $x, int $y, int $z) : bool{
		return (
			$x <= INT32_MAX and $x >= INT32_MIN and
			$y < $this->worldMaxHeight and $y >= 0 and
			$z <= INT32_MAX and $z >= INT32_MIN
		);
	}
}
