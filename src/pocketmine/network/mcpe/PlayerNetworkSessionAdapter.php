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

namespace pocketmine\network\mcpe;

use InvalidArgumentException;
use pocketmine\entity\passive\AbstractHorse;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\maps\MapData;
use pocketmine\maps\MapManager;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\ActorPickRequestPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\BlockPickRequestPacket;
use pocketmine\network\mcpe\protocol\BookEditPacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\ClientboundMapItemDataPacket;
use pocketmine\network\mcpe\protocol\ClientToServerHandshakePacket;
use pocketmine\network\mcpe\protocol\CommandBlockUpdatePacket;
use pocketmine\network\mcpe\protocol\CommandRequestPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\EmoteListPacket;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ItemFrameDropItemPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacketV1;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\MapInfoRequestPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\PassengerJumpPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerHotbarPacket;
use pocketmine\network\mcpe\protocol\PlayerInputPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\RequestAbilityPacket;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\RequestNetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\ResourcePackChunkRequestPacket;
use pocketmine\network\mcpe\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\RespawnPacket;
use pocketmine\network\mcpe\protocol\ServerSettingsRequestPacket;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;
use pocketmine\network\mcpe\protocol\SetLocalPlayerAsInitializedPacket;
use pocketmine\network\mcpe\protocol\SetPlayerGameTypePacket;
use pocketmine\network\mcpe\protocol\SettingsCommandPacket;
use pocketmine\network\mcpe\protocol\ShowCreditsPacket;
use pocketmine\network\mcpe\protocol\SpawnExperienceOrbPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\TickSyncPacket;
use pocketmine\network\mcpe\protocol\types\RequestAbilityType;
use pocketmine\network\mcpe\protocol\types\SkinAdapterSingleton;
use pocketmine\network\mcpe\protocol\UpdateAdventureSettingsPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\timings\Timings;
use pocketmine\utils\UUID;
use function base64_encode;
use function bin2hex;
use function implode;
use function json_decode;
use function json_last_error_msg;
use function preg_match;
use function strlen;
use function substr;
use function trim;

class PlayerNetworkSessionAdapter extends NetworkSession{

	/** @var Server */
	private $server;
	/** @var Player */
	private $player;

	/** @var UUID[] */
	private $emoteIds = [];

	public function __construct(Server $server, Player $player){
		$this->server = $server;
		$this->player = $player;
	}

	public function handleDataPacket(DataPacket $packet){
		if($packet instanceof BatchPacket && !$this->player->isFirstBatchConfigSequenceCompleted()){
			$packet->enableCompression = false;
		}

		if(!$this->player->isConnected()){
			return;
		}

		$timings = Timings::getReceiveDataPacketTimings($packet);
		$timings->startTiming();

		try{
			$packet->decode();
		}catch(\Exception $exception){
		}

		if(!$packet->feof() and !$packet->mayHaveUnreadBytes()){
			$remains = substr($packet->buffer, $packet->offset);
			$this->server->getLogger()->debug("Still " . strlen($remains) . " bytes unread in " . $packet->getName() . ": 0x" . bin2hex($remains));
		}

		$ev = new DataPacketReceiveEvent($this->player, $packet);
		$ev->call();
		if(!$ev->isCancelled() and !$packet->handle($this)){
			$this->server->getLogger()->debug("Unhandled " . $packet->getName() . " received from " . $this->player->getName() . ": " . base64_encode($packet->buffer));
		}

		$timings->stopTiming();
	}

	public function handleLogin(LoginPacket $packet) : bool{
		return $this->player->handleLogin($packet);
	}

	public function handleClientToServerHandshake(ClientToServerHandshakePacket $packet) : bool{
		return $this->player->onEncryptionHandshake();
	}

	public function handleResourcePackClientResponse(ResourcePackClientResponsePacket $packet) : bool{
		return $this->player->handleResourcePackClientResponse($packet);
	}

	public function handleText(TextPacket $packet) : bool{
		if($packet->type === TextPacket::TYPE_CHAT){
			return $this->player->chat($packet->message);
		}

		return false;
	}

	public function handleMovePlayer(MovePlayerPacket $packet) : bool{
		return $this->player->handleMovePlayer($packet);
	}

	public function handleLevelSoundEventPacketV1(LevelSoundEventPacketV1 $packet) : bool{
		return true; //useless leftover from 1.8
	}

	public function handleActorEvent(ActorEventPacket $packet) : bool{
		return $this->player->handleEntityEvent($packet);
	}

	public function handleInventoryTransaction(InventoryTransactionPacket $packet) : bool{
		return $this->player->handleInventoryTransaction($packet);
	}

	public function handleMobEquipment(MobEquipmentPacket $packet) : bool{
		return $this->player->handleMobEquipment($packet);
	}

	public function handleMobArmorEquipment(MobArmorEquipmentPacket $packet) : bool{
		return true; //Not used
	}

	public function handleInteract(InteractPacket $packet) : bool{
		return $this->player->handleInteract($packet);
	}

	public function handleBlockPickRequest(BlockPickRequestPacket $packet) : bool{
		return $this->player->handleBlockPickRequest($packet);
	}

	public function handleActorPickRequest(ActorPickRequestPacket $packet) : bool{
		return false; //TODO
	}

	public function handlePlayerAction(PlayerActionPacket $packet) : bool{
		return $this->player->handlePlayerAction($packet);
	}

	public function handleAnimate(AnimatePacket $packet) : bool{
		return $this->player->handleAnimate($packet);
	}

	public function handleRespawn(RespawnPacket $packet) : bool{
		return $this->player->handleRespawn($packet);
	}

	public function handleContainerClose(ContainerClosePacket $packet) : bool{
		return $this->player->handleContainerClose($packet);
	}

	public function handlePlayerHotbar(PlayerHotbarPacket $packet) : bool{
		return true; //this packet is useless
	}

	public function handleUpdateAdventureSettings(UpdateAdventureSettingsPacket $packet) : bool{
		$this->player->sendAdventureSettings();

		return true;
	}

	public function handleBlockActorData(BlockActorDataPacket $packet) : bool{
		return $this->player->handleBlockEntityData($packet);
	}

	public function handlePlayerInput(PlayerInputPacket $packet) : bool{
		$this->player->setMoveForward($packet->motionY);
		$this->player->setMoveStrafing($packet->motionX);

		return true;
	}

	public function handlePassengerJump(PassengerJumpPacket $packet) : bool{
		if($this->player->isRiding()){
			$horse = $this->player->getRidingEntity();
			if($horse instanceof AbstractHorse){
				$horse->setJumpPower($packet->jumpStrength);

				return true;
			}
		}
		return false;
	}

	public function handleSetPlayerGameType(SetPlayerGameTypePacket $packet) : bool{
		return $this->player->handleSetPlayerGameType($packet);
	}

	public function handleSpawnExperienceOrb(SpawnExperienceOrbPacket $packet) : bool{
		return false; //TODO
	}

	public function handleMapInfoRequest(MapInfoRequestPacket $packet) : bool{
		$data = MapManager::getMapDataById($packet->mapId);
		if($data instanceof MapData){
			// this is for first appearance
			$pk = new ClientboundMapItemDataPacket();
			$pk->height = $pk->width = 128;
			$pk->dimensionId = $data->getDimension();
			$pk->scale = $data->getScale();
			$pk->colors = $data->getColors();
			$pk->mapId = $data->getId();
			$pk->decorations = $data->getDecorations();
			$pk->trackedEntities = $data->getTrackedObjects();
			$pk->eids[] = $data->getId();

			$this->player->sendDataPacket($pk);

			return true;
		}
		return false;
	}

	public function handleRequestChunkRadius(RequestChunkRadiusPacket $packet) : bool{
		$this->player->setViewDistance($packet->radius);

		return true;
	}

	public function handleItemFrameDropItem(ItemFrameDropItemPacket $packet) : bool{
		return $this->player->handleItemFrameDropItem($packet);
	}

	public function handleBossEvent(BossEventPacket $packet) : bool{
		return false; //TODO
	}

	public function handleShowCredits(ShowCreditsPacket $packet) : bool{
		return false; //TODO: handle resume
	}

	public function handleCommandRequest(CommandRequestPacket $packet) : bool{
		return $this->player->handleCommandRequest($packet);
	}

	public function handleCommandBlockUpdate(CommandBlockUpdatePacket $packet) : bool{
		return false; //TODO
	}

	public function handleResourcePackChunkRequest(ResourcePackChunkRequestPacket $packet) : bool{
		return $this->player->handleResourcePackChunkRequest($packet);
	}

	public function handlePlayerSkin(PlayerSkinPacket $packet) : bool{
		return $this->player->changeSkin(SkinAdapterSingleton::get()->fromSkinData($packet->skin), $packet->newSkinName, $packet->oldSkinName);
	}

	public function handleBookEdit(BookEditPacket $packet) : bool{
		return $this->player->handleBookEdit($packet);
	}

	public function handleModalFormResponse(ModalFormResponsePacket $packet) : bool{
		if($packet->cancelReason !== null){
			return $this->player->onFormSubmit($packet->formId, null);
		}else{
			return $this->player->onFormSubmit($packet->formId, self::stupid_json_decode($packet->formData ?? "", true));
		}
	}

	/**
	 * Hack to work around a stupid bug in Minecraft W10 which causes empty strings to be sent unquoted in form responses.
	 *
	 * @return mixed
	 */
	private static function stupid_json_decode(string $json, bool $assoc = false){
		if(preg_match('/^\[(.+)\]$/s', $json, $matches) > 0){
			$raw = $matches[1];
			$lastComma = -1;
			$newParts = [];
			$inQuotes = false;
			for($i = 0, $len = strlen($raw); $i <= $len; ++$i){
				if($i === $len or ($raw[$i] === "," and !$inQuotes)){
					$part = substr($raw, $lastComma + 1, $i - ($lastComma + 1));
					if(trim($part) === ""){ //regular parts will have quotes or something else that makes them non-empty
						$part = '""';
					}
					$newParts[] = $part;
					$lastComma = $i;
				}elseif($raw[$i] === '"'){
					if(!$inQuotes){
						$inQuotes = true;
					}else{
						$backslashes = 0;
						for(; $backslashes < $i && $raw[$i - $backslashes - 1] === "\\"; ++$backslashes){
						}
						if(($backslashes % 2) === 0){ //unescaped quote
							$inQuotes = false;
						}
					}
				}
			}

			$fixed = "[" . implode(",", $newParts) . "]";
			if(($ret = json_decode($fixed, $assoc)) === null){
				throw new InvalidArgumentException("Failed to fix JSON: " . json_last_error_msg() . "(original: $json, modified: $fixed)");
			}

			return $ret;
		}

		return json_decode($json, $assoc);
	}

	public function handleServerSettingsRequest(ServerSettingsRequestPacket $packet) : bool{
		return false; //TODO: GUI stuff
	}

	public function handleSetLocalPlayerAsInitialized(SetLocalPlayerAsInitializedPacket $packet) : bool{
		$this->player->doFirstSpawn();
		return true;
	}

	public function handleTickSync(TickSyncPacket $packet) : bool{
		$this->player->sendDataPacket(TickSyncPacket::response($packet->getClientSendTime(), time()));

		return true;
	}

	public function handleLevelSoundEvent(LevelSoundEventPacket $packet) : bool{
		return $this->player->handleLevelSoundEvent($packet);
	}

	public function handleMoveActorAbsolute(MoveActorAbsolutePacket $packet) : bool{
		$target = $this->player->getServer()->findEntity($packet->entityRuntimeId);
		if($target !== null){
			$target->setClientPositionAndRotation($packet->position, $packet->yRot, $packet->xRot, 3, ($packet->flags & MoveActorAbsolutePacket::FLAG_TELEPORT) !== 0);
			//$target->onGround = ($packet->flags & MoveActorAbsolutePacket::FLAG_GROUND) !== 0;

			return true;
		}

		return false;
	}

	public function handleSetActorMotion(SetActorMotionPacket $packet) : bool{
		$target = $this->player->getServer()->findEntity($packet->entityRuntimeId);
		if($target !== null){
			$target->setClientMotion($packet->motion);

			return true;
		}

		return false;
	}

	public function handleNetworkStackLatency(NetworkStackLatencyPacket $packet) : bool{
		return true; //TODO: implement this properly - this is here to silence debug spam from MCPE dev builds
	}

	public function handleSettingsCommand(SettingsCommandPacket $packet) : bool{
		// TODO: add support to suppress command message
		$this->player->chat($packet->getCommand());
		return true;
	}

	public function handleEmote(EmotePacket $packet) : bool{
		if($packet->getEntityRuntimeIdField() === $this->player->getId()){
			if(isset($this->emoteIds[$packet->getEmoteId()])){
				$this->player->level->broadcastPacketToViewers($this->player, EmotePacket::create(
					$this->player->getId(),
					$packet->getEmoteId(),
					"",
					"",
					EmotePacket::FLAG_SERVER
				));

				return true;
			}
		}

		return false;
	}

	public function handleEmoteList(EmoteListPacket $packet) : bool{
		if($packet->getPlayerEntityRuntimeId() === $this->player->getId()){
			$this->emoteIds = [];

			foreach($packet->getEmoteIds() as $emoteId){
				$this->emoteIds[$emoteId->toString()] = $emoteId;
			}

			return true;
		}

		return false;
	}

	public function handleRequestNetworkSettings(RequestNetworkSettingsPacket $packet) : bool{
		return $this->player->handleRequestNetworkSettings($packet);
	}

	public function handleRequestAbility(RequestAbilityPacket $packet) : bool{
		if($packet->abilityId === RequestAbilityType::ABILITY_FLYING){
			$isFlying = $packet->abilityValue;
			if(!is_bool($isFlying)){
				return false;
			}

			if($isFlying !== $this->player->isFlying()){
				if(!$this->player->toggleFlight($isFlying)){
					$this->player->sendAbilities();
				}
			}

			return true;
		}

		return false;
	}
}
