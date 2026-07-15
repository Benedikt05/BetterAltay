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

namespace pocketmine\network\mcpe\protocol\types;

use pocketmine\utils\UUID;

final class PersonaSkinPiece{
	public const PIECE_TYPE_PERSONA_SKELETON = 0;
	public const PIECE_TYPE_PERSONA_BODY = 1;
	public const PIECE_TYPE_PERSONA_SKIN = 2;
	public const PIECE_TYPE_PERSONA_BOTTOM = 3;
	public const PIECE_TYPE_PERSONA_FEET = 4;
	public const PIECE_TYPE_PERSONA_TOP = 6;
	public const PIECE_TYPE_PERSONA_FACIAL_HAIR = 10;
	public const PIECE_TYPE_PERSONA_MOUTH = 11;
	public const PIECE_TYPE_PERSONA_EYES = 12;
	public const PIECE_TYPE_PERSONA_HAIR = 13;
	public const PIECE_TYPE_DRESS = 5;
	public const PIECE_TYPE_HIGH_PANTS = 7;
	public const PIECE_TYPE_HANDS = 8;
	public const PIECE_TYPE_OUTERWEAR = 9;
	public const PIECE_TYPE_HOOD = 14;
	public const PIECE_TYPE_BACK = 15;
	public const PIECE_TYPE_FACE_ACCESSORY = 16;
	public const PIECE_TYPE_HEAD = 17;
	public const PIECE_TYPE_LEGS = 18;
	public const PIECE_TYPE_LEFT_LEG = 19;
	public const PIECE_TYPE_RIGHT_LEG = 20;
	public const PIECE_TYPE_ARMS = 21;
	public const PIECE_TYPE_LEFT_ARM = 22;
	public const PIECE_TYPE_RIGHT_ARM = 23;
	public const PIECE_TYPE_CAPES = 24;
	public const PIECE_TYPE_CLASSIC_SKIN = 25;
	public const PIECE_TYPE_EMOTE = 26;

	/** @var string */
	private $pieceId;
	/** @var string */
	private $pieceType;
	/** @var UUID */
	private $packId;
	/** @var bool */
	private $isDefaultPiece;
	/** @var string */
	private $productId;

	public function __construct(string $pieceId, int $pieceType, UUID $packId, bool $isDefaultPiece, string $productId){
		$this->pieceId = $pieceId;
		$this->pieceType = $pieceType;
		$this->packId = $packId;
		$this->isDefaultPiece = $isDefaultPiece;
		$this->productId = $productId;
	}

	public function getPieceId() : string{
		return $this->pieceId;
	}

	public function getPieceType() : int{
		return $this->pieceType;
	}

	public function getPackId() : UUID{
		return $this->packId;
	}

	public function isDefaultPiece() : bool{
		return $this->isDefaultPiece;
	}

	public function getProductId() : string{
		return $this->productId;
	}
}