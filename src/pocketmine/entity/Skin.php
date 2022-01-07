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

namespace pocketmine\entity;

use Ahc\Json\Comment as CommentedJsonDecoder;
use pocketmine\network\mcpe\protocol\types\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\PersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\SkinAnimation;
use pocketmine\network\mcpe\protocol\types\Cape;
use pocketmine\network\mcpe\protocol\types\SkinData;
use pocketmine\network\mcpe\protocol\types\SkinImage;
use pocketmine\utils\UUID;
use function implode;
use function in_array;
use function json_encode;
use function strlen;
use const INT32_MAX;

class Skin{
	public const ACCEPTED_SKIN_SIZES = [
		64 * 32 * 4,
		64 * 64 * 4,
		128 * 64 * 4,
		128 * 128 * 4,
		256 * 128 * 4,
		256 * 256 * 4,
		512 * 256 * 4,
		512 * 512 * 4
	];

	public const MIN_SKIN_SIZE = 64 * 32 * 4;
	public const MAX_SKIN_SIZE = 512 * 512 * 4;

	/** @var string */
	private $skinId;
	/** @var string */
	private $resourcePatch;
	/** @var SkinImage */
	private $skinImage;
	/** @var SkinAnimation[] */
	private $animations = [];
	/** @var string */
	private $geometryData;
	/** @var string */
	private $animationData = "";
	/** @var bool */
	private $persona = false;
	/** @var bool */
	private $premium = false;
	/** @var Cape */
	private $cape;
	/** @var string */
	private $armSize = SkinData::ARM_SIZE_WIDE;
	/** @var string */
	private $skinColor = "";
	/** @var PersonaSkinPiece[] */
	private $personaPieces = [];
	/** @var PersonaPieceTintColor[] */
	private $pieceTintColors = [];
	/** @var bool */
	private $isVerified = true;

	/** @var string */
	private $geometryName = "";

	public function __construct(string $skinId, string $skinData, string $capeData = "", string $resourcePatch = "", string $geometryData = ""){
		$this->skinId = $skinId;
		$this->skinImage = SkinImage::fromLegacy($skinData);
		$this->resourcePatch = self::generateResourcePatch($resourcePatch, $this->geometryName);
		$noCape = $capeData === "";
		$this->cape = new Cape(UUID::fromRandom()->toString(), new SkinImage($noCape ? 0 : 32, $noCape ? 0 : 64, $capeData));
		$this->geometryData = $geometryData;
	}

	private function generateResourcePatch(string $input, string &$geometryName) : string{
		$json = @json_decode($input, true) ?? [];

		if(isset($json["geometry"]["default"])){
			$geometryName = $json["geometry"]["default"];

			return $input;
		}

		$geometryName = $input;

		return json_encode([
			"geometry" => [
				"default" => $input
			]
		]);
	}

	/**
	 * @deprecated
	 */
	public function isValid() : bool{
		try{
			$this->validate();
			return true;
		}catch(InvalidSkinException $e){
			return false;
		}
	}

	private static function checkLength(string $string, string $name, int $maxLength) : void{
		if(strlen($string) > $maxLength){
			throw new InvalidSkinException("$name must be at most $maxLength bytes, but have " . strlen($string) . " bytes");
		}
	}

	/**
	 * @throws InvalidSkinException
	 */
	public function validate() : void{
		self::checkLength($this->skinId, "Skin ID", 32767);
		self::checkLength($this->geometryName, "Geometry name", 32767);
		self::checkLength($this->geometryData, "Geometry data", INT32_MAX);

		if($this->skinId === ""){
			throw new InvalidSkinException("Skin ID must not be empty");
		}
		$len = strlen($this->skinImage->getData());
		if($len < self::MIN_SKIN_SIZE or $len > self::MAX_SKIN_SIZE){
			throw new InvalidSkinException("Invalid skin data size $len bytes (allowed sizes: " . implode(", ", self::ACCEPTED_SKIN_SIZES) . ")");
		}
		$capeData = $this->cape->getImage()->getData();
		if($capeData !== "" and strlen($capeData) !== 8192){
			throw new InvalidSkinException("Invalid cape data size " . strlen($capeData) . " bytes (must be exactly 8192 bytes)");
		}
		//TODO: validate geometry
	}

	/**
	 * Hack to cut down on network overhead due to skins, by un-pretty-printing geometry JSON.
	 *
	 * Mojang, some stupid reason, send every single model for every single skin in the selected skin-pack.
	 * Not only that, they are pretty-printed.
	 * TODO: find out what model crap can be safely dropped from the packet (unless it gets fixed first)
	 */
	public function debloatGeometryData() : void{
		if($this->geometryData !== ""){
			$this->geometryData = (string) json_encode((new CommentedJsonDecoder())->decode($this->geometryData));
		}

		if($this->resourcePatch !== ""){
			$this->resourcePatch = (string) json_encode((new CommentedJsonDecoder())->decode($this->resourcePatch));
		}
	}
	public function isPremium() : bool{
		return $this->premium;
	}

	public function isPersona() : bool{
		return $this->persona;
	}

	/**
	 * @return SkinAnimation[]
	 */
	public function getAnimations() : array{
		return $this->animations;
	}

	public function getCape() : Cape{
		return $this->cape;
	}

	public function getAnimationData() : string{
		return $this->animationData;
	}

	public function getSkinImage() : SkinImage{
		return $this->skinImage;
	}

	public function getSkinId() : string{
		return $this->skinId;
	}

	public function getGeometryData() : string{
		return $this->geometryData;
	}

	public function getResourcePatch() : string{
		return $this->resourcePatch;
	}

	/**
	 * @deprecated
	 */
	public function getSkinData() : string{
		return $this->getSkinImage()->getData();
	}

	/**
	 * @deprecated
	 */
	public function getCapeData() : string{
		return $this->getCape()->getImage()->getData();
	}

	/**
	 * @deprecated
	 */
	public function getGeometryName() : string{
		return $this->geometryName;
	}

	public function setSkinImage(SkinImage $skinImage) : Skin{
		$this->skinImage = $skinImage;
		return $this;
	}

	/**
	 * @param SkinAnimation[] $animations
	 */
	public function setAnimations(array $animations) : Skin{
		$this->animations = $animations;
		return $this;
	}

	public function setAnimationData(string $animationData) : Skin{
		$this->animationData = $animationData;
		return $this;
	}

	public function setPersona(bool $persona) : Skin{
		$this->persona = $persona;
		return $this;
	}

	public function setPremium(bool $premium) : Skin{
		$this->premium = $premium;
		return $this;
	}

	public function setCape(Cape $cape) : Skin{
		$this->cape = $cape;
		return $this;
	}

	public function getArmSize() : string{
		return $this->armSize;
	}

	public function setArmSize(string $armSize) : Skin{
		$this->armSize = $armSize;
		return $this;
	}

	public function getSkinColor() : string{
		return $this->skinColor;
	}

	public function setSkinColor(string $skinColor) : Skin{
		$this->skinColor = $skinColor;
		return $this;
	}

	/**
	 * @return PersonaSkinPiece[]
	 */
	public function getPersonaPieces() : array{
		return $this->personaPieces;
	}

	/**
	 * @param PersonaSkinPiece[] $personaPieces
	 */
	public function setPersonaPieces(array $personaPieces) : Skin{
		$this->personaPieces = $personaPieces;
		return $this;
	}

	/**
	 * @return PersonaPieceTintColor[]
	 */
	public function getPieceTintColors() : array{
		return $this->pieceTintColors;
	}

	/**
	 * @param PersonaPieceTintColor[] $pieceTintColors
	 */
	public function setPieceTintColors(array $pieceTintColors) : Skin{
		$this->pieceTintColors = $pieceTintColors;
		return $this;
	}

	public function isVerified() : bool{
		return $this->isVerified;
	}

	public function setVerified(bool $isVerified) : Skin{
		$this->isVerified = $isVerified;
		return $this;
	}
}
