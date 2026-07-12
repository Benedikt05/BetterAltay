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

namespace pocketmine\network\mcpe\protocol;

use pocketmine\network\mcpe\NetworkSession;
use function count;
use const PHP_INT_MAX;

class LevelChunkPacket extends DataPacket/* implements ClientboundPacket*/
{
	public const NETWORK_ID = ProtocolInfo::LEVEL_CHUNK_PACKET;

	/**
	 * Client will request all subchunks as needed up to the top of the world
	 */
	private const CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT = 0xff_ff_ff_ff;
	/**
	 * Client will request subchunks as needed up to the height written in the packet, and assume that anything above
	 * that height is air (wtf mojang ...)
	 */
	private const CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT = 0xff_ff_ff_fe;

	private int $chunkX;
	private int $chunkZ;
	private int $dimensionId;
	private int $subChunkCount;
	private bool $clientSubChunkRequestsEnabled;
	protected ?int $clientRequestSubChunkLimit = null;
	/** @var int[]|null */
	private ?array $usedBlobHashes = null;
	private string $extraPayload;

	/**
	 * @generate-create-func
	 *
	 * @param int[] $usedBlobHashes
	 */
	public static function create(
		int $chunkX,
		int $chunkZ,
		int $dimensionId,
		int $subChunkCount,
		bool $clientSubChunkRequestsEnabled,
		?int $clientRequestSubChunkLimit,
		?array $usedBlobHashes,
		string $extraPayload
	): self{
		$result = new self;
		$result->chunkX = $chunkX;
		$result->chunkZ = $chunkZ;
		$result->dimensionId = $dimensionId;
		$result->subChunkCount = $subChunkCount;
		$result->clientSubChunkRequestsEnabled = $clientSubChunkRequestsEnabled;
		$result->clientRequestSubChunkLimit = $clientRequestSubChunkLimit;
		$result->usedBlobHashes = $usedBlobHashes;
		$result->extraPayload = $extraPayload;
		return $result;
	}

	public function getChunkX() : int{
		return $this->chunkX;
	}

	public function getChunkZ() : int{
		return $this->chunkZ;
	}

	public function getDimensionId() : int{
		return $this->dimensionId;
	}

	public function getSubChunkCount() : int{
		return $this->subChunkCount;
	}

	public function isClientSubChunkRequestEnabled() : bool{
		return $this->clientSubChunkRequestsEnabled;
	}

	public function isCacheEnabled() : bool{
		return $this->usedBlobHashes !== null;
	}

	/**
	 * @return int[]|null
	 */
	public function getUsedBlobHashes() : ?array{
		return $this->usedBlobHashes;
	}

	public function getExtraPayload() : string{
		return $this->extraPayload;
	}

	protected function decodePayload() : void{
		$this->chunkX = $this->getVarInt();
		$this->chunkZ = $this->getVarInt();
		$this->dimensionId = $this->getVarInt();
		$this->subChunkCount = $this->getUnsignedVarInt();

		$this->clientSubChunkRequestsEnabled = $this->getBool();
		if($this->clientSubChunkRequestsEnabled){
			$this->clientRequestSubChunkLimit = $this->getVarInt();
		}

		$cacheEnabled = $this->getBool();
		$this->usedBlobHashes = [];
		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			$this->usedBlobHashes[] = $this->getLLong();
		}

		if (!$cacheEnabled) {
			$this->usedBlobHashes = null;
		}

		$this->extraPayload = $this->getString();
	}

	protected function encodePayload() : void{
		$this->putVarInt($this->chunkX);
		$this->putVarInt($this->chunkZ);
		$this->putVarInt($this->dimensionId);
		$this->putUnsignedVarInt($this->subChunkCount);

		$this->putBool($this->clientSubChunkRequestsEnabled);
		if($this->clientSubChunkRequestsEnabled){
			$this->putVarInt($this->clientRequestSubChunkLimit);
		}

		$this->putBool($this->usedBlobHashes !== null);
		$usedBlobHashes = $this->usedBlobHashes ?? [];
		$this->putUnsignedVarInt(count($usedBlobHashes));
		foreach($usedBlobHashes as $hash){
			$this->putLLong($hash);
		}

		$this->putString($this->extraPayload);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleLevelChunk($this);
	}
}
