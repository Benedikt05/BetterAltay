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

#include <rules/DataPacket.h>

use pocketmine\math\Vector3;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\BlockPaletteEntry;
use pocketmine\network\mcpe\protocol\types\EducationEditionOffer;
use pocketmine\network\mcpe\protocol\types\EducationUriResource;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\network\mcpe\protocol\types\GameRuleType;
use pocketmine\network\mcpe\protocol\types\GeneratorType;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use pocketmine\network\mcpe\protocol\types\MultiplayerGameVisibility;
use pocketmine\network\mcpe\protocol\types\PlayerMovementSettings;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;
use pocketmine\network\mcpe\protocol\types\SpawnSettings;
use function count;

class StartGamePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::START_GAME_PACKET;

	/** @var int */
	public $entityUniqueId;
	/** @var int */
	public $entityRuntimeId;
	/** @var int */
	public $playerGamemode;

	/** @var Vector3 */
	public $playerPosition;

	/** @var float */
	public $pitch;
	/** @var float */
	public $yaw;

	/** @var int */
	public $seed;
	/** @var SpawnSettings */
	public $spawnSettings;
	/** @var int */
	public $generator = GeneratorType::OVERWORLD;
	/** @var int */
	public $worldGamemode;
	/** @var int */
	public $difficulty;
	/** @var int */
	public $spawnX;
	/** @var int */
	public $spawnY;
	/** @var int */
	public $spawnZ;
	/** @var bool */
	public $hasAchievementsDisabled = true;
	/** @var int */
	public $time = -1;
	/** @var int */
	public $eduEditionOffer = EducationEditionOffer::NONE;
	/** @var bool */
	public $hasEduFeaturesEnabled = false;
	/** @var string */
	public $eduProductUUID = "";
	/** @var float */
	public $rainLevel;
	/** @var float */
	public $lightningLevel;
	/** @var bool */
	public $hasConfirmedPlatformLockedContent = false;
	/** @var bool */
	public $isMultiplayerGame = true;
	/** @var bool */
	public $hasLANBroadcast = true;
	/** @var int */
	public $xboxLiveBroadcastMode = MultiplayerGameVisibility::PUBLIC;
	/** @var int */
	public $platformBroadcastMode = MultiplayerGameVisibility::PUBLIC;
	/** @var bool */
	public $commandsEnabled;
	/** @var bool */
	public $isTexturePacksRequired = true;
	/**
	 * @var mixed[][]
	 * @phpstan-var array<string, array{0: int, 1: bool|int|float, 2: bool}>
	 */
	public $gameRules = [ //TODO: implement this
		"naturalregeneration" => [GameRuleType::BOOL, false, false] //Hack for client side regeneration
	];
	/** @var Experiments */
	public $experiments;
	/** @var bool */
	public $hasBonusChestEnabled = false;
	/** @var bool */
	public $hasStartWithMapEnabled = false;
	/** @var int */
	public $defaultPlayerPermission = PlayerPermissions::MEMBER; //TODO

	/** @var int */
	public $serverChunkTickRadius = 4; //TODO (leave as default for now)

	/** @var bool */
	public $hasLockedBehaviorPack = false;
	/** @var bool */
	public $hasLockedResourcePack = false;
	/** @var bool */
	public $isFromLockedWorldTemplate = false;
	/** @var bool */
	public $useMsaGamertagsOnly = false;
	/** @var bool */
	public $isFromWorldTemplate = false;
	/** @var bool */
	public $isWorldTemplateOptionLocked = false;
	/** @var bool */
	public $onlySpawnV1Villagers = false;
	/** @var string */
	public $vanillaVersion = "1.17.40";
	/** @var int */
	public $limitedWorldWidth = 0;
	/** @var int */
	public $limitedWorldLength = 0;
	/** @var bool */
	public $isNewNether = true;
	/** @var EducationUriResource|null */
	public $eduSharedUriResource = null;
	/** @var bool|null */
	public $experimentalGameplayOverride = null;

	/** @var string */
	public $levelId = ""; //base64 string, usually the same as world folder name in vanilla
	/** @var string */
	public $worldName;
	/** @var string */
	public $premiumWorldTemplateId = "";
	/** @var bool */
	public $isTrial = false;
	/** @var PlayerMovementSettings */
	public $playerMovementSettings;
	/** @var int */
	public $currentTick = 0; //only used if isTrial is true
	/** @var int */
	public $enchantmentSeed = 0;
	/** @var string */
	public $multiplayerCorrelationId = ""; //TODO: this should be filled with a UUID of some sort

	/**
	 * @var BlockPaletteEntry[]
	 * @phpstan-var list<BlockPaletteEntry>
	 */
	public $blockPalette = [];

	/**
	 * @var ItemTypeEntry[]
	 * @phpstan-var list<ItemTypeEntry>
	 */
	public $itemTable;
	/** @var bool */
	public $enableNewInventorySystem = false; //TODO
	/** @var string */
	public $serverSoftwareVersion;

	public int $blockPaletteChecksum;

	protected function decodePayload(){
		$this->entityUniqueId = $this->getEntityUniqueId();
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		$this->playerGamemode = $this->getVarInt();

		$this->playerPosition = $this->getVector3();

		$this->pitch = $this->getLFloat();
		$this->yaw = $this->getLFloat();

		//Level settings
		$this->seed = $this->getLLong();
		$this->spawnSettings = SpawnSettings::read($this);
		$this->generator = $this->getVarInt();
		$this->worldGamemode = $this->getVarInt();
		$this->difficulty = $this->getVarInt();
		$this->getBlockPosition($this->spawnX, $this->spawnY, $this->spawnZ);
		$this->hasAchievementsDisabled = $this->getBool();
		$this->time = $this->getVarInt();
		$this->eduEditionOffer = $this->getVarInt();
		$this->hasEduFeaturesEnabled = $this->getBool();
		$this->eduProductUUID = $this->getString();
		$this->rainLevel = $this->getLFloat();
		$this->lightningLevel = $this->getLFloat();
		$this->hasConfirmedPlatformLockedContent = $this->getBool();
		$this->isMultiplayerGame = $this->getBool();
		$this->hasLANBroadcast = $this->getBool();
		$this->xboxLiveBroadcastMode = $this->getVarInt();
		$this->platformBroadcastMode = $this->getVarInt();
		$this->commandsEnabled = $this->getBool();
		$this->isTexturePacksRequired = $this->getBool();
		$this->gameRules = $this->getGameRules();
		$this->experiments = Experiments::read($this);
		$this->hasBonusChestEnabled = $this->getBool();
		$this->hasStartWithMapEnabled = $this->getBool();
		$this->defaultPlayerPermission = $this->getVarInt();
		$this->serverChunkTickRadius = $this->getLInt();
		$this->hasLockedBehaviorPack = $this->getBool();
		$this->hasLockedResourcePack = $this->getBool();
		$this->isFromLockedWorldTemplate = $this->getBool();
		$this->useMsaGamertagsOnly = $this->getBool();
		$this->isFromWorldTemplate = $this->getBool();
		$this->isWorldTemplateOptionLocked = $this->getBool();
		$this->onlySpawnV1Villagers = $this->getBool();
		$this->vanillaVersion = $this->getString();
		$this->limitedWorldWidth = $this->getLInt();
		$this->limitedWorldLength = $this->getLInt();
		$this->isNewNether = $this->getBool();
		$this->eduSharedUriResource = EducationUriResource::read($this);
		if($this->getBool()){
			$this->experimentalGameplayOverride = $this->getBool();
		}else{
			$this->experimentalGameplayOverride = null;
		}

		$this->levelId = $this->getString();
		$this->worldName = $this->getString();
		$this->premiumWorldTemplateId = $this->getString();
		$this->isTrial = $this->getBool();
		$this->playerMovementSettings = PlayerMovementSettings::read($this);
		$this->currentTick = $this->getLLong();

		$this->enchantmentSeed = $this->getVarInt();

		$this->blockPalette = [];
		for($i = 0, $len = $this->getUnsignedVarInt(); $i < $len; ++$i){
			$blockName = $this->getString();
			$state = $this->getNbtCompoundRoot();
			$this->blockPalette[] = new BlockPaletteEntry($blockName, $state);
		}

		$this->itemTable = [];
		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			$stringId = $this->getString();
			$numericId = $this->getSignedLShort();
			$isComponentBased = $this->getBool();

			$this->itemTable[] = new ItemTypeEntry($stringId, $numericId, $isComponentBased);
		}

		$this->multiplayerCorrelationId = $this->getString();
		$this->enableNewInventorySystem = $this->getBool();
		$this->serverSoftwareVersion = $this->getString();
		$this->blockPaletteChecksum = $this->getLLong();
	}

	protected function encodePayload(){
		$this->putEntityUniqueId($this->entityUniqueId);
		$this->putEntityRuntimeId($this->entityRuntimeId);
		$this->putVarInt($this->playerGamemode);

		$this->putVector3($this->playerPosition);

		$this->putLFloat($this->pitch);
		$this->putLFloat($this->yaw);

		//Level settings
		$this->putLLong($this->seed);
		$this->spawnSettings->write($this);
		$this->putVarInt($this->generator);
		$this->putVarInt($this->worldGamemode);
		$this->putVarInt($this->difficulty);
		$this->putBlockPosition($this->spawnX, $this->spawnY, $this->spawnZ);
		$this->putBool($this->hasAchievementsDisabled);
		$this->putVarInt($this->time);
		$this->putVarInt($this->eduEditionOffer);
		$this->putBool($this->hasEduFeaturesEnabled);
		$this->putString($this->eduProductUUID);
		$this->putLFloat($this->rainLevel);
		$this->putLFloat($this->lightningLevel);
		$this->putBool($this->hasConfirmedPlatformLockedContent);
		$this->putBool($this->isMultiplayerGame);
		$this->putBool($this->hasLANBroadcast);
		$this->putVarInt($this->xboxLiveBroadcastMode);
		$this->putVarInt($this->platformBroadcastMode);
		$this->putBool($this->commandsEnabled);
		$this->putBool($this->isTexturePacksRequired);
		$this->putGameRules($this->gameRules);
		$this->experiments->write($this);
		$this->putBool($this->hasBonusChestEnabled);
		$this->putBool($this->hasStartWithMapEnabled);
		$this->putVarInt($this->defaultPlayerPermission);
		$this->putLInt($this->serverChunkTickRadius);
		$this->putBool($this->hasLockedBehaviorPack);
		$this->putBool($this->hasLockedResourcePack);
		$this->putBool($this->isFromLockedWorldTemplate);
		$this->putBool($this->useMsaGamertagsOnly);
		$this->putBool($this->isFromWorldTemplate);
		$this->putBool($this->isWorldTemplateOptionLocked);
		$this->putBool($this->onlySpawnV1Villagers);
		$this->putString($this->vanillaVersion);
		$this->putLInt($this->limitedWorldWidth);
		$this->putLInt($this->limitedWorldLength);
		$this->putBool($this->isNewNether);
		($this->eduSharedUriResource ?? new EducationUriResource("", ""))->write($this);
		$this->putBool($this->experimentalGameplayOverride !== null);
		if($this->experimentalGameplayOverride !== null){
			$this->putBool($this->experimentalGameplayOverride);
		}

		$this->putString($this->levelId);
		$this->putString($this->worldName);
		$this->putString($this->premiumWorldTemplateId);
		$this->putBool($this->isTrial);
		$this->playerMovementSettings->write($this);
		$this->putLLong($this->currentTick);

		$this->putVarInt($this->enchantmentSeed);

		$this->putUnsignedVarInt(count($this->blockPalette));
		$nbtWriter = new NetworkLittleEndianNBTStream();
		foreach($this->blockPalette as $entry){
			$this->putString($entry->getName());
			$this->put($nbtWriter->write($entry->getStates()));
		}
		$this->putUnsignedVarInt(count($this->itemTable));
		foreach($this->itemTable as $entry){
			$this->putString($entry->getStringId());
			$this->putLShort($entry->getNumericId());
			$this->putBool($entry->isComponentBased());
		}

		$this->putString($this->multiplayerCorrelationId);
		$this->putBool($this->enableNewInventorySystem);
		$this->putString($this->serverSoftwareVersion);
		$this->putLLong($this->blockPaletteChecksum);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleStartGame($this);
	}
}
