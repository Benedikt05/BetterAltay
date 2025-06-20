<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\utils\UUID;

class SkinData{

	public const ARM_SIZE_SLIM = "slim";
	public const ARM_SIZE_WIDE = "wide";

	private $skinId;
	private $playFabId;
	private $resourcePatch;
	private $skinImage;
	private $animations;
	private $capeImage;
	private $geometryData;
	private string $geometryDataEngineVersion;
	private $animationData;
	private $capeId;
	private $fullSkinId;
	private $armSize;
	private $skinColor;
	private $personaPieces;
	private $pieceTintColors;
	private $isVerified;
	private $persona;
	private $premium;
	private $personaCapeOnClassic;
	private $isPrimaryUser;
	private bool $override;

	/**
	 * @param SkinAnimation[]         $animations
	 * @param PersonaSkinPiece[]      $personaPieces
	 * @param PersonaPieceTintColor[] $pieceTintColors
	 */
	public function __construct(
		string $skinId, 
		string $playFabId, 
		string $resourcePatch, 
		SkinImage $skinImage, 
		array $animations = [], 
		SkinImage $capeImage = null, 
		$geometryData = "", 
		string $geometryDataEngineVersion = ProtocolInfo::MINECRAFT_VERSION_NETWORK, 
		string $animationData = "", 
		string $capeId = "", 
		?string $fullSkinId = null, 
		string $armSize = self::ARM_SIZE_WIDE, 
		string $skinColor = "", 
		array $personaPieces = [], 
		array $pieceTintColors = [], 
		bool $isVerified = true, 
		bool $premium = false, 
		bool $persona = false, 
		bool $personaCapeOnClassic = false, 
		bool $isPrimaryUser = true, 
		bool $override = true
	){
		$this->skinId = $skinId;
		$this->playFabId = $playFabId;
		$this->resourcePatch = $resourcePatch;
		$this->skinImage = $skinImage;
		$this->animations = $animations;
	         $this->capeImage = $capeImage ?? new SkinImage(32, 64, str_repeat("\x00", 8192));
		if(!is_string($geometryData)){
			$geometryData = "";
		}
		$this->geometryData = $geometryData;
		$this->geometryDataEngineVersion = $geometryDataEngineVersion;
		$this->animationData = $animationData;
		$this->capeId = $capeId;
		$this->fullSkinId = $fullSkinId ?? UUID::fromRandom()->toString();
		$this->armSize = $armSize;
		$this->skinColor = $skinColor;
		$this->personaPieces = $personaPieces;
		$this->pieceTintColors = $pieceTintColors;
		$this->isVerified = $isVerified;
		$this->premium = $premium;
		$this->persona = $persona;
		$this->personaCapeOnClassic = $personaCapeOnClassic;
		$this->isPrimaryUser = $isPrimaryUser;
		$this->override = $override;
	}

	public function getSkinId() : string{
		return $this->skinId;
	}

	public function getPlayFabId() : string{
		return $this->playFabId;
	}

	public function getResourcePatch() : string{
		return $this->resourcePatch;
	}

	public function getSkinImage() : SkinImage{
		return $this->skinImage;
	}

	/**
	 * @return SkinAnimation[]
	 */
	public function getAnimations() : array{
		return $this->animations;
	}

	public function getCapeImage() : SkinImage{
		return $this->capeImage;
	}

	public function getGeometryData() : string{
		return $this->geometryData;
	}

	public function getGeometryDataEngineVersion() : string{
		return $this->geometryDataEngineVersion;
	}

	public function getAnimationData() : string{
		return $this->animationData;
	}

	public function getCapeId() : string{
		return $this->capeId;
	}

	public function getFullSkinId() : string{
		return $this->fullSkinId;
	}

	public function getArmSize() : string{
		return $this->armSize;
	}

	public function getSkinColor() : string{
		return $this->skinColor;
	}

	/**
	 * @return PersonaSkinPiece[]
	 */
	public function getPersonaPieces() : array{
		return $this->personaPieces;
	}

	/**
	 * @return PersonaPieceTintColor[]
	 */
	public function getPieceTintColors() : array{
		return $this->pieceTintColors;
	}

	public function isPersona() : bool{
		return $this->persona;
	}

	public function isPremium() : bool{
		return $this->premium;
	}

	public function isPersonaCapeOnClassic() : bool{
		return $this->personaCapeOnClassic;
	}

	public function isPrimaryUser() : bool{
		return $this->isPrimaryUser;
	}

	public function isOverride() : bool{
		return $this->override;
	}

	public function isVerified() : bool{
		return $this->isVerified;
	}

	/**
	 * @internal
	 */
	public function setVerified(bool $verified) : void{
		$this->isVerified = $verified;
	}
}
