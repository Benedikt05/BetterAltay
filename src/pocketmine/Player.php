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

namespace pocketmine;

use pocketmine\block\Bed;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\UnknownBlock;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Attribute;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\InvalidSkinException;
use pocketmine\entity\Living;
use pocketmine\entity\object\ItemEntity;
use pocketmine\entity\passive\AbstractHorse;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\FishingHook;
use pocketmine\entity\Skin;
use pocketmine\entity\vehicle\Boat;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\player\cheat\PlayerIllegalMoveEvent;
use pocketmine\event\player\PlayerAchievementAwardedEvent;
use pocketmine\event\player\PlayerAnimationEvent;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\player\PlayerBedLeaveEvent;
use pocketmine\event\player\PlayerBlockPickEvent;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerEditBookEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerJumpEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerToggleFlightEvent;
use pocketmine\event\player\PlayerToggleGlideEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\player\PlayerToggleSprintEvent;
use pocketmine\event\player\PlayerToggleSwimEvent;
use pocketmine\event\player\PlayerTransferEvent;
use pocketmine\event\player\PlayerInteractEntityEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\form\Form;
use pocketmine\form\FormValidationException;
use pocketmine\form\ServerSettingsForm;
use pocketmine\inventory\CraftingGrid;
use pocketmine\inventory\PlayerCursorInventory;
use pocketmine\inventory\PlayerOffHandInventory;
use pocketmine\inventory\PlayerUIInventory;
use pocketmine\inventory\transaction\action\InventoryAction;
use pocketmine\inventory\transaction\CraftingTransaction;
use pocketmine\inventory\transaction\TransactionValidationException;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Consumable;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\MeleeWeaponEnchantment;
use pocketmine\item\Item;
use pocketmine\item\MaybeConsumable;
use pocketmine\item\WritableBook;
use pocketmine\item\WrittenBook;
use pocketmine\lang\TextContainer;
use pocketmine\lang\TranslationContainer;
use pocketmine\level\ChunkLoader;
use pocketmine\level\format\Chunk;
use pocketmine\level\GameRules;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\metadata\MetadataValue;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\convert\ItemTypeDictionary;
use pocketmine\network\mcpe\encryption\EncryptionContext;
use pocketmine\network\mcpe\encryption\PrepareEncryptionTask;
use pocketmine\network\mcpe\PlayerNetworkSessionAdapter;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\BiomeDefinitionListPacket;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\BlockPickRequestPacket;
use pocketmine\network\mcpe\protocol\BookEditPacket;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\network\mcpe\protocol\ChunkRadiusUpdatedPacket;
use pocketmine\network\mcpe\protocol\CommandRequestPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ItemFrameDropItemPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\ResourcePackChunkDataPacket;
use pocketmine\network\mcpe\protocol\ResourcePackChunkRequestPacket;
use pocketmine\network\mcpe\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\ResourcePackDataInfoPacket;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\RespawnPacket;
use pocketmine\network\mcpe\protocol\ServerToClientHandshakePacket;
use pocketmine\network\mcpe\protocol\ServerSettingsResponsePacket;
use pocketmine\network\mcpe\protocol\SetPlayerGameTypePacket;
use pocketmine\network\mcpe\protocol\SetSpawnPositionPacket;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\ToastRequestPacket;
use pocketmine\network\mcpe\protocol\TransferPacket;
use pocketmine\network\mcpe\protocol\types\CommandEnum;
use pocketmine\network\mcpe\protocol\types\CommandOriginData;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UIInventorySlotOffset;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\NetworkInventoryAction;
use pocketmine\network\mcpe\protocol\types\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\PersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\PlayerMovementSettings;
use pocketmine\network\mcpe\protocol\types\PlayerMovementType;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;
use pocketmine\network\mcpe\protocol\types\SkinAdapterSingleton;
use pocketmine\network\mcpe\protocol\types\SkinAnimation;
use pocketmine\network\mcpe\protocol\types\SkinData;
use pocketmine\network\mcpe\protocol\types\SkinImage;
use pocketmine\network\mcpe\protocol\types\SpawnSettings;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\network\mcpe\VerifyLoginTask;
use pocketmine\network\SourceInterface;
use pocketmine\permission\PermissibleBase;
use pocketmine\permission\PermissionAttachment;
use pocketmine\permission\PermissionAttachmentInfo;
use pocketmine\permission\PermissionManager;
use pocketmine\plugin\Plugin;
use pocketmine\resourcepacks\ResourcePack;
use pocketmine\tile\Spawnable;
use pocketmine\tile\Tile;
use pocketmine\tile\ItemFrame;
use pocketmine\timings\Timings;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\TextFormat;
use pocketmine\utils\UUID;
use function abs;
use function array_key_exists;
use function array_merge;
use function array_values;
use function assert;
use function base64_decode;
use function ceil;
use function count;
use function explode;
use function floor;
use function fmod;
use function get_class;
use function gettype;
use function implode;
use function in_array;
use function is_infinite;
use function is_int;
use function is_nan;
use function is_object;
use function is_string;
use function json_encode;
use function json_last_error_msg;
use function lcg_value;
use function max;
use function mb_strlen;
use function microtime;
use function min;
use function preg_match;
use function round;
use function spl_object_hash;
use function sprintf;
use function sqrt;
use function strlen;
use function strpos;
use function strtolower;
use function substr;
use function trim;
use const M_PI;
use const M_SQRT3;
use const PHP_INT_MAX;

/**
 * Main class that handles networking, recovery, and packet sending to the server part
 */
class Player extends Human implements CommandSender, ChunkLoader, IPlayer{

	public const SURVIVAL = 0;
	public const CREATIVE = 1;
	public const ADVENTURE = 2;
	public const SPECTATOR = 3;
	public const VIEW = Player::SPECTATOR;

	private const MOVES_PER_TICK = 2;
	private const MOVE_BACKLOG_SIZE = 100 * self::MOVES_PER_TICK; //100 ticks backlog (5 seconds)

	private const RESOURCE_PACK_CHUNK_SIZE = 128 * 1024; //128KB

	//TODO: HACK!
	//these IDs are used for 1.16 to restore 1.14ish crafting & inventory behaviour; since they don't seem to have any
	//effect on the behaviour of inventory transactions I don't currently plan to integrate these into the main system.
	private const RESERVED_WINDOW_ID_RANGE_START = ContainerIds::LAST - 10;
	private const RESERVED_WINDOW_ID_RANGE_END = ContainerIds::LAST;
	public const HARDCODED_CRAFTING_GRID_WINDOW_ID = self::RESERVED_WINDOW_ID_RANGE_START + 1;
	public const HARDCODED_INVENTORY_WINDOW_ID = self::RESERVED_WINDOW_ID_RANGE_START + 2;

	/**
	 * Validates the given username.
	 */
	public static function isValidUserName(?string $name) : bool{
		if($name === null){
			return false;
		}

		$lname = strtolower($name);
		$len = strlen($name);
		return $lname !== "rcon" and $lname !== "console" and $len >= 1 and $len <= 16 and preg_match("/[^A-Za-z0-9_&* ]/", $name) === 0;
	}

	/** @var SourceInterface */
	protected $interface;

	/**
	 * @var PlayerNetworkSessionAdapter
	 * TODO: remove this once player and network are divorced properly
	 */
	protected $sessionAdapter;

	/** @var string */
	protected $ip;
	/** @var int */
	protected $port;

	/** @var bool[] */
	private $needACK = [];

	/** @var DataPacket[] */
	private $batchedPackets = [];
	
	private ?EncryptionContext $cipher = null;

	/**
	 * @var int
	 * Last measurement of player's latency in milliseconds.
	 */
	protected $lastPingMeasure = 1;

	/** @var float */
	public $creationTime = 0;

	/** @var bool */
	public $loggedIn = false;

	/** @var bool */
	private $seenLoginPacket = false;
	/** @var bool */
	private $awaitingEncryptionHandshake = false;
	/** @var bool */
	private $resourcePacksDone = false;

	/** @var bool */
	public $spawned = false;

	/** @var string */
	protected $username = "";
	/** @var string */
	protected $iusername = "";
	/** @var string */
	protected $displayName = "";
	/** @var int */
	protected $randomClientId;
	/** @var string */
	protected $xuid = "";

	/** @var int */
	protected $windowCnt = 2;
	/** @var int[] */
	protected $windows = [];
	/** @var Inventory[] */
	protected $windowIndex = [];
	/** @var bool[] */
	protected $permanentWindows = [];
	/** @var PlayerUIInventory */
	protected $uiInventory;
	/** @var PlayerCursorInventory */
	protected $cursorInventory;
	/** @var PlayerOffHandInventory */
	protected $offHandInventory;
	/** @var CraftingGrid */
	protected $craftingGrid;
	/** @var CraftingTransaction|null */
	protected $craftingTransaction = null;

	/**
	 * TODO: HACK! This tracks GUIs for inventories that the server considers "always open" so that the client can't
	 * open them twice. (1.16 hack)
	 * @var true[]
	 * @phpstan-var array<int, true>
	 * @internal
	 */
	public $openHardcodedWindows = [];

	/** @var int */
	protected $messageCounter = 2;
	/** @var bool */
	protected $removeFormat = true;

	/** @var bool[] name of achievement => bool */
	protected $achievements = [];
	/** @var bool */
	protected $playedBefore;
	/** @var int */
	protected $gamemode;

	/** @var int */
	private $loaderId = 0;
	/** @var bool[] chunkHash => bool (true = sent, false = needs sending) */
	public $usedChunks = [];
	/** @var bool[] chunkHash => dummy */
	protected $loadQueue = [];
	/** @var int */
	protected $nextChunkOrderRun = 5;

	/** @var int */
	protected $viewDistance = -1;
	/** @var int */
	protected $spawnThreshold;
	/** @var int */
	protected $spawnChunkLoadCount = 0;
	/** @var int */
	protected $chunksPerTick;

	/** @var bool[] map: raw UUID (string) => bool */
	protected $hiddenPlayers = [];

	/** @var float */
	protected $moveRateLimit = 10 * self::MOVES_PER_TICK;
	/** @var float|null */
	protected $lastMovementProcess = null;
	/** @var Vector3|null */
	protected $forceMoveSync = null;

	/** @var int */
	protected $inAirTicks = 0;
	/** @var float */
	protected $stepHeight = 0.6;
	/** @var bool */
	protected $allowMovementCheats = false;

	/** @var Vector3|null */
	protected $sleeping = null;
	/** @var Position|null */
	private $spawnPosition = null;

	//TODO: Abilities
	/** @var bool */
	protected $autoJump = true;
	/** @var bool */
	protected $allowFlight = false;
	/** @var bool */
	protected $flying = false;
	/** @var bool */
	protected $muted = false;

	/** @var PermissibleBase */
	private $perm;

	/** @var int|null */
	protected $lineHeight = null;
	/** @var string */
	protected $locale = "en_US";
	/** @var string $deviceModel */
	protected $deviceModel;
	/** @var int $deviceOS */
	protected $deviceOS;
	/** @var string $deviceId */
	protected $deviceId;
	/** @var int */
	protected $startAction = -1;
	/** @var int[] ID => ticks map */
	protected $usedItemsCooldown = [];

	/** @var int */
	protected $formIdCounter = 0;
	/** @var Form[] */
	protected $forms = [];

	/** @var float */
	protected $lastRightClickTime = 0.0;
	/** @var UseItemTransactionData|null */
	protected $lastRightClickData = null;
	/** @var FishingHook|null */
	protected $fishingHook = null;
	/** @var int */
	protected $commandPermission = AdventureSettingsPacket::PERMISSION_NORMAL;
	/** @var bool */
	protected $keepExperience = false;

	/**
	 * @return TranslationContainer|string
	 */
	public function getLeaveMessage(){
		if($this->spawned){
			return new TranslationContainer(TextFormat::YELLOW . "%multiplayer.player.left", [
				$this->getDisplayName()
			]);
		}

		return "";
	}

	/**
	 * This might disappear in the future. Please use getUniqueId() instead.
	 * @deprecated
	 *
	 * @return int
	 */
	public function getClientId(){
		return $this->randomClientId;
	}

	public function isBanned() : bool{
		return $this->server->getNameBans()->isBanned($this->username);
	}

	public function setBanned(bool $value){
		if($value){
			$this->server->getNameBans()->addBan($this->getName(), null, null, null);
			$this->kick("You have been banned");
		}else{
			$this->server->getNameBans()->remove($this->getName());
		}
	}

	public function isWhitelisted() : bool{
		return $this->server->isWhitelisted($this->username);
	}

	public function setWhitelisted(bool $value){
		if($value){
			$this->server->addWhitelist($this->username);
		}else{
			$this->server->removeWhitelist($this->username);
		}
	}

	public function isAuthenticated() : bool{
		return $this->xuid !== "";
	}

	/**
	 * If the player is logged into Xbox Live, returns their Xbox user ID (XUID) as a string. Returns an empty string if
	 * the player is not logged into Xbox Live.
	 */
	public function getXuid() : string{
		return $this->xuid;
	}

	/**
	 * Returns the player's UUID. This should be the preferred method to identify a player.
	 * It does not change if the player changes their username.
	 *
	 * All players will have a UUID, regardless of whether they are logged into Xbox Live or not. However, note that
	 * non-XBL players can fake their UUIDs.
	 *
	 * WARNING: DO NOT trust this before PlayerLoginEvent. Before PlayerLoginEvent, the player hasn't yet been
	 * authenticated, and any of their data might be faked.
	 */
	public function getUniqueId() : ?UUID{
		return parent::getUniqueId();
	}

	public function getPlayer(){
		return $this;
	}

	public function getFirstPlayed(){
		return $this->namedtag->getLong("firstPlayed", 0, true);
	}

	public function getLastPlayed(){
		return $this->namedtag->getLong("lastPlayed", 0, true);
	}

	public function hasPlayedBefore() : bool{
		return $this->playedBefore;
	}

	/**
	 * @return void
	 */
	public function setAllowFlight(bool $value){
		$this->allowFlight = $value;
		$this->sendSettings();
	}

	public function getAllowFlight() : bool{
		return $this->allowFlight;
	}

	/**
	 * @return void
	 */
	public function setFlying(bool $value){
		if($this->flying !== $value){
			$this->flying = $value;
			$this->resetFallDistance();
			$this->sendSettings();
		}
	}

	public function isFlying() : bool{
		return $this->flying;
	}

	public function setMuted(bool $value){
		$this->muted = $value;
		$this->sendSettings();
	}

	public function isMuted() : bool{
		return $this->muted;
	}

	/**
	 * @return void
	 */
	public function setAutoJump(bool $value){
		$this->autoJump = $value;
		$this->sendSettings();
	}

	public function hasAutoJump() : bool{
		return $this->autoJump;
	}

	/**
	 * @return null|FishingHook
	 */
	public function getFishingHook() : ?FishingHook{
		return $this->fishingHook;
	}

	/**
	 * @param null|FishingHook $fishingHook
	 */
	public function setFishingHook(?FishingHook $fishingHook) : void{
		$this->fishingHook = $fishingHook;
	}

	public function allowMovementCheats() : bool{
		return $this->allowMovementCheats;
	}

	/**
	 * @return void
	 */
	public function setAllowMovementCheats(bool $value = true){
		$this->allowMovementCheats = $value;
	}

	public function spawnTo(Player $player) : void{
		if($this->spawned and $player->spawned and $this->isAlive() and $player->isAlive() and $player->canSee($this) and !$this->isSpectator()){
			parent::spawnTo($player);
		}
	}

	/**
	 * @return Server
	 */
	public function getServer(){
		return $this->server;
	}

	public function getRemoveFormat() : bool{
		return $this->removeFormat;
	}

	/**
	 * @return void
	 */
	public function setRemoveFormat(bool $remove = true){
		$this->removeFormat = $remove;
	}

	public function getScreenLineHeight() : int{
		return $this->lineHeight ?? 7;
	}

	public function setScreenLineHeight(int $height = null){
		if($height !== null and $height < 1){
			throw new \InvalidArgumentException("Line height must be at least 1");
		}
		$this->lineHeight = $height;
	}

	public function canSee(Player $player) : bool{
		return !isset($this->hiddenPlayers[$player->getRawUniqueId()]);
	}

	/**
	 * @return void
	 */
	public function hidePlayer(Player $player){
		if($player === $this){
			return;
		}
		$this->hiddenPlayers[$player->getRawUniqueId()] = true;
		$player->despawnFrom($this);
	}

	/**
	 * @return void
	 */
	public function showPlayer(Player $player){
		if($player === $this){
			return;
		}
		unset($this->hiddenPlayers[$player->getRawUniqueId()]);
		if($player->isOnline()){
			$player->spawnTo($this);
		}
	}

	public function canCollideWith(Entity $entity) : bool{
		return $entity instanceof Living;
	}

	public function canBeCollidedWith() : bool{
		return !$this->isSpectator() and parent::canBeCollidedWith();
	}

	public function resetFallDistance() : void{
		parent::resetFallDistance();
		$this->inAirTicks = 0;
	}

	public function getViewDistance() : int{
		return $this->viewDistance;
	}

	/**
	 * @return void
	 */
	public function setViewDistance(int $distance){
		$this->viewDistance = $this->server->getAllowedViewDistance($distance);

		$this->spawnThreshold = (int) (min($this->viewDistance, $this->server->getProperty("chunk-sending.spawn-radius", 4)) ** 2 * M_PI);

		$this->nextChunkOrderRun = 0;

		$pk = new ChunkRadiusUpdatedPacket();
		$pk->radius = $this->viewDistance;
		$this->dataPacket($pk);

		$this->server->getLogger()->debug("Setting view distance for " . $this->getName() . " to " . $this->viewDistance . " (requested " . $distance . ")");
	}

	public function isOnline() : bool{
		return $this->isConnected() and $this->loggedIn;
	}

	public function isOp() : bool{
		return $this->server->isOp($this->getName());
	}

	/**
	 * @return void
	 */
	public function setOp(bool $value){
		if($value === $this->isOp()){
			return;
		}

		if($value){
			$this->server->addOp($this->getName());
		}else{
			$this->server->removeOp($this->getName());
		}

		$this->sendSettings();
	}

	/**
	 * @param permission\Permission|string $name
	 */
	public function isPermissionSet($name) : bool{
		return $this->perm->isPermissionSet($name);
	}

	/**
	 * @param permission\Permission|string $name
	 *
	 * @throws \InvalidStateException if the player is closed
	 */
	public function hasPermission($name) : bool{
		if($this->closed){
			throw new \InvalidStateException("Trying to get permissions of closed player");
		}
		return $this->perm->hasPermission($name);
	}

	public function addAttachment(Plugin $plugin, string $name = null, bool $value = null) : PermissionAttachment{
		return $this->perm->addAttachment($plugin, $name, $value);
	}

	/**
	 * @return void
	 */
	public function removeAttachment(PermissionAttachment $attachment){
		$this->perm->removeAttachment($attachment);
	}

	public function recalculatePermissions(){
		$permManager = PermissionManager::getInstance();
		$permManager->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_USERS, $this);
		$permManager->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);

		if($this->perm === null){
			return;
		}

		$this->perm->recalculatePermissions();

		if($this->spawned){
			if($this->hasPermission(Server::BROADCAST_CHANNEL_USERS)){
				$permManager->subscribeToPermission(Server::BROADCAST_CHANNEL_USERS, $this);
			}
			if($this->hasPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE)){
				$permManager->subscribeToPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);
			}

			$this->sendCommandData();
		}
	}

	/**
	 * @return PermissionAttachmentInfo[]
	 */
	public function getEffectivePermissions() : array{
		return $this->perm->getEffectivePermissions();
	}

	/**
	 * @return void
	 */
	public function sendCommandData(){
		$pk = new AvailableCommandsPacket();
		foreach($this->server->getCommandMap()->getCommands() as $command){
			if(!$command->testPermissionSilent($this) or isset($pk->commandData[$command->getName()]) or $command->getName() === "help" or !$command->testPermissionSilent($this)){
				continue;
			}

			$data = $command->getData();
			$data->commandName = strtolower($data->commandName);
			if($data->aliases instanceof CommandEnum){
				//work around a client bug which makes the original name not show when aliases are used
				$data->aliases->enumValues[] = $data->commandName;
			}

			$pk->commandData[$data->commandName] = $data;
		}

		$this->dataPacket($pk);

	}

	public function __construct(SourceInterface $interface, string $ip, int $port){
		$this->interface = $interface;
		$this->perm = new PermissibleBase($this);
		$this->namedtag = new CompoundTag();
		$this->server = Server::getInstance();
		$this->ip = $ip;
		$this->port = $port;
		$this->loaderId = Level::generateChunkLoaderId($this);
		$this->chunksPerTick = (int) $this->server->getProperty("chunk-sending.per-tick", 4);
		$this->spawnThreshold = (int) (($this->server->getProperty("chunk-sending.spawn-radius", 4) ** 2) * M_PI);
		$this->gamemode = $this->server->getGamemode();
		$this->setLevel($this->server->getDefaultLevel());
		$this->boundingBox = new AxisAlignedBB(0, 0, 0, 0, 0, 0);

		$this->creationTime = microtime(true);

		$this->allowMovementCheats = (bool) $this->server->getProperty("player.anti-cheat.allow-movement-cheats", false);

		$this->sessionAdapter = new PlayerNetworkSessionAdapter($this->server, $this);
	}

	public function isConnected() : bool{
		return $this->sessionAdapter !== null;
	}

	/**
	 * Gets the username
	 */
	public function getName() : string{
		return $this->username;
	}

	public function getLowerCaseName() : string{
		return $this->iusername;
	}

	/**
	 * Returns the "friendly" display name of this player to use in the chat.
	 */
	public function getDisplayName() : string{
		return $this->displayName;
	}

	/**
	 * @return void
	 */
	public function setDisplayName(string $name){
		$this->displayName = $name;
		if($this->spawned){
			$this->server->updatePlayerListData($this->getUniqueId(), $this->getId(), $this->getDisplayName(), $this->getSkin(), $this->getXuid());
		}
	}

	/**
	 * Returns the player's locale, e.g. en_US.
	 */
	public function getLocale() : string{
		return $this->locale;
	}

	/**
	 * Sets player locale, e.g. en_US
	 *
	 * @param string $locale
	 */
	public function setLocale(string $locale) : void{
		$this->locale = $locale;
	}

	/**
	 * Called when a player changes their skin.
	 * Plugin developers should not use this, use setSkin() and sendSkin() instead.
	 */
	public function changeSkin(Skin $skin, string $newSkinName, string $oldSkinName) : bool{
		if(!$skin->isValid()){
			return false;
		}

		$ev = new PlayerChangeSkinEvent($this, $this->getSkin(), $skin);
		$ev->call();

		if($ev->isCancelled()){
			$this->sendSkin([$this]);
			return true;
		}

		$this->setSkin($ev->getNewSkin());
		$this->sendSkin($this->server->getOnlinePlayers());
		return true;
	}

	/**
	 * {@inheritdoc}
	 *
	 * If null is given, will additionally send the skin to the player itself as well as its viewers.
	 */
	public function sendSkin(?array $targets = null) : void{
		parent::sendSkin($targets ?? $this->server->getOnlinePlayers());
	}

	/**
	 * Gets the player IP address
	 */
	public function getAddress() : string{
		return $this->ip;
	}

	public function getPort() : int{
		return $this->port;
	}

	/**
	 * Returns the last measured latency for this player, in milliseconds. This is measured automatically and reported
	 * back by the network interface.
	 */
	public function getPing() : int{
		return $this->lastPingMeasure;
	}

	/**
	 * Updates the player's last ping measurement.
	 *
	 * @internal Plugins should not use this method.
	 *
	 * @return void
	 */
	public function updatePing(int $pingMS){
		$this->lastPingMeasure = $pingMS;
	}

	/**
	 * @deprecated
	 */
	public function getNextPosition() : Position{
		return $this->getPosition();
	}

	public function getInAirTicks() : int{
		return $this->inAirTicks;
	}

	/**
	 * Returns whether the player is currently using an item (right-click and hold).
	 */
	public function isUsingItem() : bool{
		return $this->getGenericFlag(self::DATA_FLAG_ACTION) and $this->startAction > -1;
	}

	/**
	 * @return void
	 */
	public function setUsingItem(bool $value){
		$this->startAction = $value ? $this->server->getTick() : -1;
		$this->setGenericFlag(self::DATA_FLAG_ACTION, $value);
	}

	/**
	 * Returns how long the player has been using their currently-held item for. Used for determining arrow shoot force
	 * for bows.
	 */
	public function getItemUseDuration() : int{
		return $this->startAction === -1 ? -1 : ($this->server->getTick() - $this->startAction);
	}

	/**
	 * Returns whether the player has a cooldown period left before it can use the given item again.
	 */
	public function hasItemCooldown(Item $item) : bool{
		$this->checkItemCooldowns();
		return isset($this->usedItemsCooldown[$item->getId()]);
	}

	/**
	 * Resets the player's cooldown time for the given item back to the maximum.
	 */
	public function resetItemCooldown(Item $item, ?int $ticks = null) : void{
		$ticks = $ticks ?? $item->getCooldownTicks();
		if($ticks > 0){
			$this->usedItemsCooldown[$item->getId()] = $this->server->getTick() + $ticks;
		}
	}

	protected function checkItemCooldowns() : void{
		$serverTick = $this->server->getTick();
		foreach($this->usedItemsCooldown as $itemId => $cooldownUntil){
			if($cooldownUntil <= $serverTick){
				unset($this->usedItemsCooldown[$itemId]);
			}
		}
	}

	public function getCommandPermission() : int{
		return $this->commandPermission;
	}

	public function setCommandPermission(int $commandPermission) : void{
		$this->commandPermission = $commandPermission;
	}

	public function changeDimension(int $dimension, Vector3 $position = null, bool $respawn = false){
		$pk = new ChangeDimensionPacket();
		$pk->dimension = $dimension;
		$pk->position = $position ?? $this;
		$pk->respawn = $respawn;
		$this->sendDataPacket($pk);
	}

	public function getMaxInPortalTime() : int{
		return $this->isCreative() ? 0 : 80;
	}

	public function getPortalCooldown() : int{
		return 10;
	}

	protected function switchLevel(Level $targetLevel) : bool{
		$oldLevel = $this->level;
		if(parent::switchLevel($targetLevel)){
			if($oldLevel !== null){
				foreach($this->usedChunks as $index => $d){
					Level::getXZ($index, $X, $Z);
					$this->unloadChunk($X, $Z, $oldLevel);
				}
			}

			$this->usedChunks = [];
			$this->loadQueue = [];
			$this->level->sendTime($this);
			$this->level->sendGameRules([$this]);
			$this->level->sendDifficulty($this);

			if($oldLevel->getDimension() !== $targetLevel->getDimension()){
				$this->changeDimension($targetLevel->getDimension(), $this, !$this->isAlive());
			}

			return true;
		}

		return false;
	}

	/**
	 * @return void
	 */
	protected function unloadChunk(int $x, int $z, Level $level = null){
		$level = $level ?? $this->level;
		$index = Level::chunkHash($x, $z);
		if(isset($this->usedChunks[$index])){
			foreach($level->getChunkEntities($x, $z) as $entity){
				if($entity !== $this){
					$entity->despawnFrom($this);
				}
			}

			unset($this->usedChunks[$index]);
		}
		$level->unregisterChunkLoader($this, $x, $z);
		unset($this->loadQueue[$index]);
	}

	/**
	 * @return void
	 */
	public function sendChunk(int $x, int $z, BatchPacket $payload){
		if(!$this->isConnected()){
			return;
		}

		$this->usedChunks[Level::chunkHash($x, $z)] = true;
		$this->dataPacket($payload);

		if($this->spawned){
			foreach($this->level->getChunkEntities($x, $z) as $entity){
				if($entity !== $this and !$entity->isClosed() and $entity->isAlive()){
					$entity->spawnTo($this);
				}
			}
		}

		if($this->spawnChunkLoadCount !== -1 and ++$this->spawnChunkLoadCount >= $this->spawnThreshold){
			$this->sendPlayStatus(PlayStatusPacket::PLAYER_SPAWN);
			$this->spawnChunkLoadCount = -1;
		}
	}

	/**
	 * @return void
	 */
	protected function sendNextChunk(){
		if(!$this->isConnected()){
			return;
		}

		Timings::$playerChunkSendTimer->startTiming();

		$count = 0;
		foreach($this->loadQueue as $index => $distance){
			if($count >= $this->chunksPerTick){
				break;
			}

			$X = null;
			$Z = null;
			Level::getXZ($index, $X, $Z);
			assert(is_int($X) and is_int($Z));

			++$count;

			$this->usedChunks[$index] = false;
			$this->level->registerChunkLoader($this, $X, $Z, false);

			if(!$this->level->populateChunk($X, $Z)){
				continue;
			}

			unset($this->loadQueue[$index]);
			$this->level->requestChunk($X, $Z, $this);
		}

		Timings::$playerChunkSendTimer->stopTiming();
	}

	/**
	 * @return void
	 */
	public function doFirstSpawn(){
		if($this->spawned || !$this->constructed){
			return; //avoid player spawning twice (this can only happen on 3.x with a custom malicious client)
		}
		$this->spawned = true;
		$this->setImmobile(false);

		if($this->hasPermission(Server::BROADCAST_CHANNEL_USERS)){
			PermissionManager::getInstance()->subscribeToPermission(Server::BROADCAST_CHANNEL_USERS, $this);
		}
		if($this->hasPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE)){
			PermissionManager::getInstance()->subscribeToPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);
		}

		$ev = new PlayerJoinEvent($this,
			new TranslationContainer(TextFormat::YELLOW . "%multiplayer.player.joined", [
				$this->getDisplayName()
			])
		);
		$ev->call();
		if(strlen(trim((string) $ev->getJoinMessage())) > 0){
			$this->server->broadcastMessage($ev->getJoinMessage());
		}

		$this->noDamageTicks = 60;

		foreach($this->usedChunks as $index => $hasSent){
			if(!$hasSent){
				continue; //this will happen when the chunk is ready to send
			}
			Level::getXZ($index, $chunkX, $chunkZ);
			foreach($this->level->getChunkEntities($chunkX, $chunkZ) as $entity){
				if($entity !== $this and !$entity->isClosed() and $entity->isAlive() and !$entity->isFlaggedForDespawn()){
					$entity->spawnTo($this);
				}
			}
		}

		$this->spawnToAll();

		if($this->getHealth() <= 0){
			$this->actuallyRespawn();
		}
	}

	/**
	 * @return void
	 */
	protected function sendRespawnPacket(Vector3 $pos, int $respawnState = RespawnPacket::SEARCHING_FOR_SPAWN){
		$pk = new RespawnPacket();
		$pk->position = $pos->add(0, $this->baseOffset, 0);
		$pk->respawnState = $respawnState;
		$pk->entityRuntimeId = $this->getId();

		$this->dataPacket($pk);
	}

	protected function orderChunks() : void{
		if(!$this->isConnected() or $this->viewDistance === -1){
			return;
		}

		Timings::$playerChunkOrderTimer->startTiming();

		$radius = $this->server->getAllowedViewDistance($this->viewDistance);
		$radiusSquared = $radius ** 2;

		$newOrder = [];
		$unloadChunks = $this->usedChunks;

		$centerX = $this->getFloorX() >> 4;
		$centerZ = $this->getFloorZ() >> 4;

		for($x = 0; $x < $radius; ++$x){
			for($z = 0; $z <= $x; ++$z){
				if(($x ** 2 + $z ** 2) > $radiusSquared){
					break; //skip to next band
				}

				//If the chunk is in the radius, others at the same offsets in different quadrants are also guaranteed to be.

				/* Top right quadrant */
				if(!isset($this->usedChunks[$index = Level::chunkHash($centerX + $x, $centerZ + $z)]) or $this->usedChunks[$index] === false){
					$newOrder[$index] = true;
				}
				unset($unloadChunks[$index]);

				/* Top left quadrant */
				if(!isset($this->usedChunks[$index = Level::chunkHash($centerX - $x - 1, $centerZ + $z)]) or $this->usedChunks[$index] === false){
					$newOrder[$index] = true;
				}
				unset($unloadChunks[$index]);

				/* Bottom right quadrant */
				if(!isset($this->usedChunks[$index = Level::chunkHash($centerX + $x, $centerZ - $z - 1)]) or $this->usedChunks[$index] === false){
					$newOrder[$index] = true;
				}
				unset($unloadChunks[$index]);

				/* Bottom left quadrant */
				if(!isset($this->usedChunks[$index = Level::chunkHash($centerX - $x - 1, $centerZ - $z - 1)]) or $this->usedChunks[$index] === false){
					$newOrder[$index] = true;
				}
				unset($unloadChunks[$index]);

				if($x !== $z){
					/* Top right quadrant mirror */
					if(!isset($this->usedChunks[$index = Level::chunkHash($centerX + $z, $centerZ + $x)]) or $this->usedChunks[$index] === false){
						$newOrder[$index] = true;
					}
					unset($unloadChunks[$index]);

					/* Top left quadrant mirror */
					if(!isset($this->usedChunks[$index = Level::chunkHash($centerX - $z - 1, $centerZ + $x)]) or $this->usedChunks[$index] === false){
						$newOrder[$index] = true;
					}
					unset($unloadChunks[$index]);

					/* Bottom right quadrant mirror */
					if(!isset($this->usedChunks[$index = Level::chunkHash($centerX + $z, $centerZ - $x - 1)]) or $this->usedChunks[$index] === false){
						$newOrder[$index] = true;
					}
					unset($unloadChunks[$index]);

					/* Bottom left quadrant mirror */
					if(!isset($this->usedChunks[$index = Level::chunkHash($centerX - $z - 1, $centerZ - $x - 1)]) or $this->usedChunks[$index] === false){
						$newOrder[$index] = true;
					}
					unset($unloadChunks[$index]);
				}
			}
		}

		foreach($unloadChunks as $index => $bool){
			Level::getXZ($index, $X, $Z);
			$this->unloadChunk($X, $Z);
		}

		$this->loadQueue = $newOrder;
		if(count($this->loadQueue) > 0 or count($unloadChunks) > 0){
			$pk = new NetworkChunkPublisherUpdatePacket();
			$pk->x = $this->getFloorX();
			$pk->y = $this->getFloorY();
			$pk->z = $this->getFloorZ();
			$pk->radius = $this->viewDistance * 16; //blocks, not chunks >.>
			$this->dataPacket($pk);
		}

		Timings::$playerChunkOrderTimer->stopTiming();
	}

	/**
	 * @return Position
	 */
	public function getSpawn(){
		if($this->hasValidSpawnPosition()){
			return $this->spawnPosition;
		}else{
			$level = $this->server->getDefaultLevel();

			return $level->getSafeSpawn();
		}
	}

	public function hasValidSpawnPosition() : bool{
		return $this->spawnPosition !== null and $this->spawnPosition->isValid();
	}

	/**
	 * Sets the spawnpoint of the player (and the compass direction) to a Vector3, or set it on another world with a
	 * Position object
	 *
	 * @param Vector3|Position $pos
	 *
	 * @return void
	 */
	public function setSpawn(Vector3 $pos){
		if(!($pos instanceof Position)){
			$level = $this->level;
		}else{
			$level = $pos->getLevelNonNull();
		}
		$this->spawnPosition = new Position($pos->x, $pos->y, $pos->z, $level);
		$pk = new SetSpawnPositionPacket();
		$pk->x = $pk->x2 = $this->spawnPosition->getFloorX();
		$pk->y = $pk->y2 = $this->spawnPosition->getFloorY();
		$pk->z = $pk->z2 = $this->spawnPosition->getFloorZ();
		$pk->dimension = DimensionIds::OVERWORLD;
		$pk->spawnType = SetSpawnPositionPacket::TYPE_PLAYER_SPAWN;

		$this->dataPacket($pk);
	}

	public function isSleeping() : bool{
		return $this->sleeping !== null;
	}

	public function sleepOn(Vector3 $pos) : bool{
		if(!$this->isOnline()){
			return false;
		}

		$pos = $pos->floor();
		$b = $this->level->getBlock($pos);

		$ev = new PlayerBedEnterEvent($this, $b);
		$ev->call();
		if($ev->isCancelled()){
			return false;
		}

		if($b instanceof Bed){
			$b->setOccupied();
		}

		$this->sleeping = clone $pos;

		$this->propertyManager->setBlockPos(self::DATA_PLAYER_BED_POSITION, $pos);
		$this->setPlayerFlag(self::DATA_PLAYER_FLAG_SLEEP, true);

		$this->setSpawn($pos);

		$this->level->setSleepTicks(60);
		$this->updateBoundingBox(0.2, 0.2);

		return true;
	}

	/**
	 * @return void
	 */
	public function stopSleep(){
		if($this->sleeping instanceof Vector3){
			$b = $this->level->getBlock($this->sleeping);
			if($b instanceof Bed){
				$b->setOccupied(false);
			}
			(new PlayerBedLeaveEvent($this, $b))->call();

			$this->sleeping = null;
			$this->updateBoundingBox(1.8, 0.6);
			$this->propertyManager->setBlockPos(self::DATA_PLAYER_BED_POSITION, null);
			$this->setPlayerFlag(self::DATA_PLAYER_FLAG_SLEEP, false);

			$this->level->setSleepTicks(0);

			$pk = new AnimatePacket();
			$pk->entityRuntimeId = $this->id;
			$pk->action = AnimatePacket::ACTION_STOP_SLEEP;
			$this->dataPacket($pk);
		}
	}

	public function setSneaking(bool $value = true) : void{
		parent::setSneaking($value);

		if($value){
			$this->updateBoundingBox(1.65, 0.6);
		}else{
			$this->updateBoundingBox(1.8, 0.6);
		}
	}

	public function setGliding(bool $value = true) : void{
		parent::setGliding($value);

		if($value){
			$this->updateBoundingBox(0.6, 0.6);
		}else{
			$this->updateBoundingBox(1.8, 0.6);
		}
	}

	public function setSwimming(bool $value = true) : void{
		parent::setSwimming($value);

		if($value){
			$this->updateBoundingBox(0.6, 0.6);
		}else{
			$this->updateBoundingBox(1.8, 0.6);
		}
	}

	public function hasAchievement(string $achievementId) : bool{
		if(!isset(Achievement::$list[$achievementId])){
			return false;
		}

		return $this->achievements[$achievementId] ?? false;
	}

	public function awardAchievement(string $achievementId) : bool{
		if(isset(Achievement::$list[$achievementId]) and !$this->hasAchievement($achievementId)){
			foreach(Achievement::$list[$achievementId]["requires"] as $requirementId){
				if(!$this->hasAchievement($requirementId)){
					return false;
				}
			}
			$ev = new PlayerAchievementAwardedEvent($this, $achievementId);
			$ev->call();
			if(!$ev->isCancelled()){
				$this->achievements[$achievementId] = true;
				Achievement::broadcast($this, $achievementId);

				return true;
			}else{
				return false;
			}
		}

		return false;
	}

	/**
	 * @return void
	 */
	public function removeAchievement(string $achievementId){
		if($this->hasAchievement($achievementId)){
			$this->achievements[$achievementId] = false;
		}
	}

	public function getGamemode() : int{
		return $this->gamemode;
	}

	/**
	 * @internal
	 *
	 * Returns a client-friendly gamemode of the specified real gamemode
	 * This function takes care of handling gamemodes known to MCPE (as of 1.1.0.3, that includes Survival, Creative and Adventure)
	 *
	 * TODO: remove this when Spectator Mode gets added properly to MCPE
	 */
	public static function getClientFriendlyGamemode(int $gamemode) : int{
		static $map = [
			self::SURVIVAL => GameMode::SURVIVAL,
			self::CREATIVE => GameMode::CREATIVE,
			self::ADVENTURE => GameMode::ADVENTURE,
			self::SPECTATOR => GameMode::CREATIVE
		];
		return $map[$gamemode & 0x3];
	}

	/**
	 * Sets the gamemode, and if needed, kicks the Player.
	 *
	 * @param bool $client if the client made this change in their GUI
	 */
	public function setGamemode(int $gm, bool $client = false) : bool{
		if($gm < 0 or $gm > 3 or $this->gamemode === $gm){
			return false;
		}

		$ev = new PlayerGameModeChangeEvent($this, $gm);
		$ev->call();
		if($ev->isCancelled()){
			if($client){ //gamemode change by client in the GUI
				$this->sendGamemode();
			}
			return false;
		}

		$this->gamemode = $gm;

		$this->allowFlight = $this->isCreative();
		if($this->isSpectator()){
			$this->setFlying(true);
			$this->keepMovement = true;
			$this->onGround = false;

			//TODO: HACK! this syncs the onground flag with the client so that flying works properly
			//this is a yucky hack but we don't have any other options :(
			$this->sendPosition($this, null, null, MovePlayerPacket::MODE_TELEPORT);

			$this->despawnFromAll();
		}else{
			$this->keepMovement = $this->allowMovementCheats;
			$this->checkGroundState(0, 0, 0, 0, 0, 0);
			if($this->isSurvival()){
				$this->setFlying(false);
			}
			$this->spawnToAll();
		}

		$this->namedtag->setInt("playerGameType", $this->gamemode);
		if(!$client){ //Gamemode changed by server, do not send for client changes
			$this->sendGamemode();
		}else{
			Command::broadcastCommandMessage($this, new TranslationContainer("commands.gamemode.success.self", [Server::getGamemodeString($gm)]));
		}

		$this->sendSettings();
		$this->inventory->sendCreativeContents();

		return true;
	}

	/**
	 * @internal
	 * Sends the player's gamemode to the client.
	 *
	 * @return void
	 */
	public function sendGamemode(){
		$pk = new SetPlayerGameTypePacket();
		$pk->gamemode = Player::getClientFriendlyGamemode($this->gamemode);
		$this->dataPacket($pk);
	}

	/**
	 * Sends all the option flags
	 *
	 * @return void
	 */
	public function sendSettings(){
		$pk = new AdventureSettingsPacket();

		$pk->setFlag(AdventureSettingsPacket::WORLD_IMMUTABLE, $this->isSpectator());
		$pk->setFlag(AdventureSettingsPacket::NO_PVP, $this->isSpectator());
		$pk->setFlag(AdventureSettingsPacket::AUTO_JUMP, $this->autoJump);
		$pk->setFlag(AdventureSettingsPacket::ALLOW_FLIGHT, $this->allowFlight);
		$pk->setFlag(AdventureSettingsPacket::NO_CLIP, $this->isSpectator());
		$pk->setFlag(AdventureSettingsPacket::FLYING, $this->flying);
		$pk->setFlag(AdventureSettingsPacket::MUTED, $this->muted);

		$pk->commandPermission = ($this->isOp() ? AdventureSettingsPacket::PERMISSION_OPERATOR : AdventureSettingsPacket::PERMISSION_NORMAL);
		$this->commandPermission = $pk->commandPermission;
		$pk->playerPermission = ($this->isOp() ? PlayerPermissions::OPERATOR : PlayerPermissions::MEMBER);
		$pk->entityUniqueId = $this->getId();

		$this->dataPacket($pk);
	}

	/**
	 * NOTE: Because Survival and Adventure Mode share some similar behaviour, this method will also return true if the player is
	 * in Adventure Mode. Supply the $literal parameter as true to force a literal Survival Mode check.
	 *
	 * @param bool $literal whether a literal check should be performed
	 */
	public function isSurvival(bool $literal = false) : bool{
		if($literal){
			return $this->gamemode === Player::SURVIVAL;
		}else{
			return ($this->gamemode & 0x01) === 0;
		}
	}

	/**
	 * NOTE: Because Creative and Spectator Mode share some similar behaviour, this method will also return true if the player is
	 * in Spectator Mode. Supply the $literal parameter as true to force a literal Creative Mode check.
	 *
	 * @param bool $literal whether a literal check should be performed
	 */
	public function isCreative(bool $literal = false) : bool{
		if($literal){
			return $this->gamemode === Player::CREATIVE;
		}else{
			return ($this->gamemode & 0x01) === 1;
		}
	}

	/**
	 * NOTE: Because Adventure and Spectator Mode share some similar behaviour, this method will also return true if the player is
	 * in Spectator Mode. Supply the $literal parameter as true to force a literal Adventure Mode check.
	 *
	 * @param bool $literal whether a literal check should be performed
	 */
	public function isAdventure(bool $literal = false) : bool{
		if($literal){
			return $this->gamemode === Player::ADVENTURE;
		}else{
			return ($this->gamemode & 0x02) > 0;
		}
	}

	public function isSpectator() : bool{
		return $this->gamemode === Player::SPECTATOR;
	}

	public function isFireProof() : bool{
		return $this->isCreative();
	}

	public function getDrops() : array{
		if(!$this->isCreative()){
			return parent::getDrops();
		}

		return [];
	}

	public function getXpDropAmount() : int{
		if(!$this->server->keepExperience and !$this->isCreative() and !$this->keepExperience){
			return parent::getXpDropAmount();
		}

		return 0;
	}

	protected function checkGroundState(float $movX, float $movY, float $movZ, float $dx, float $dy, float $dz) : void{
		if($this->isSpectator()){
			$this->onGround = false;
		}else{
			$bb = clone $this->boundingBox;
			$bb->minY = $this->y - 0.2;
			$bb->maxY = $this->y + 0.2;

			$this->onGround = $this->isCollided = count($this->level->getCollisionBlocks($bb, true)) > 0;
		}
	}

	public function canBeMovedByCurrents() : bool{
		return false; //currently has no server-side movement
	}

	/**
	 * @return void
	 */
	protected function checkNearEntities(){
		foreach($this->level->getNearbyEntities($this->boundingBox->expandedCopy(1, 0.5, 1), $this) as $entity){
			$entity->scheduleUpdate();

			if(!$entity->isAlive() or $entity->isFlaggedForDespawn()){
				continue;
			}

			$entity->onCollideWithPlayer($this);
		}
	}

	protected function handleMovement(Vector3 $newPos) : void{
		$this->moveRateLimit--;
		if($this->moveRateLimit < 0){
			return;
		}

		$oldPos = $this->asLocation();
		$distanceSquared = $newPos->distanceSquared($oldPos);

		$revert = false;

		if($distanceSquared > 100){
			//TODO: this is probably too big if we process every movement
			/* !!! BEWARE YE WHO ENTER HERE !!!
			 *
			 * This is NOT an anti-cheat check. It is a safety check.
			 * Without it hackers can teleport with freedom on their own and cause lots of undesirable behaviour, like
			 * freezes, lag spikes and memory exhaustion due to sync chunk loading and collision checks across large distances.
			 * Not only that, but high-latency players can trigger such behaviour innocently.
			 *
			 * If you must tamper with this code, be aware that this can cause very nasty results. Do not waste our time
			 * asking for help if you suffer the consequences of messing with this.
			 */
			$this->server->getLogger()->debug($this->getName() . " moved too fast, reverting movement");
			$this->server->getLogger()->debug("Old position: " . $this->asVector3() . ", new position: " . $newPos);
			$revert = true;
		}elseif(!$this->level->isInLoadedTerrain($newPos) or !$this->level->isChunkGenerated($newPos->getFloorX() >> 4, $newPos->getFloorZ() >> 4)){
			$revert = true;
			$this->nextChunkOrderRun = 0;
		}

		if(!$revert and $distanceSquared != 0){
			$dx = $newPos->x - $this->x;
			$dy = $newPos->y - $this->y;
			$dz = $newPos->z - $this->z;

			//the client likes to clip into blocks like stairs, but we do full server-side prediction of that without
			//help from the client's position changes, so we deduct the expected clip height from the moved distance.
			$expectedClipDistance = $this->ySize * (1 - self::STEP_CLIP_MULTIPLIER);
			$dy -= $expectedClipDistance;
			$this->move($dx, $dy, $dz);

			$diff = $this->distanceSquared($newPos);

			//TODO: Explore lowering this threshold now that stairs work properly.
			if($this->isSurvival() and $diff > 0.0625){
				$ev = new PlayerIllegalMoveEvent($this, $newPos, new Vector3($this->lastX, $this->lastY, $this->lastZ));
				$ev->setCancelled($this->allowMovementCheats);

				$ev->call();

				if(!$ev->isCancelled()){
					$revert = true;
					$this->server->getLogger()->debug($this->getServer()->getLanguage()->translateString("pocketmine.player.invalidMove", [$this->getName()]));
					$this->server->getLogger()->debug("Old position: " . $this->asVector3() . ", new position: " . $newPos . ", expected clip distance: $expectedClipDistance");
				}
			}

			if($diff > 0 and !$revert){
				$this->setPosition($newPos);
			}
		}

		if($revert){
			$this->revertMovement($oldPos);
		}
	}

	/**
	 * Fires movement events and synchronizes player movement, every tick.
	 */
	protected function processMostRecentMovements() : void{
		$now = microtime(true);
		$multiplier = $this->lastMovementProcess !== null ? ($now - $this->lastMovementProcess) * 20 : 1;
		$exceededRateLimit = $this->moveRateLimit < 0;
		$this->moveRateLimit = min(self::MOVE_BACKLOG_SIZE, max(0, $this->moveRateLimit) + self::MOVES_PER_TICK * $multiplier);
		$this->lastMovementProcess = $now;

		$from = new Location($this->lastX, $this->lastY, $this->lastZ, $this->lastYaw, $this->lastPitch, $this->level);
		$to = $this->getLocation();

		$delta = (($this->lastX - $to->x) ** 2) + (($this->lastY - $to->y) ** 2) + (($this->lastZ - $to->z) ** 2);
		$deltaAngle = abs($this->lastYaw - $to->yaw) + abs($this->lastPitch - $to->pitch);

		if($delta > 0.0001 or $deltaAngle > 1.0){
			$ev = new PlayerMoveEvent($this, $from, $to);

			$ev->call();

			if($ev->isCancelled()){
				$this->revertMovement($from);
				return;
			}

			if($to->distanceSquared($ev->getTo()) > 0.01){ //If plugins modify the destination
				$this->teleport($ev->getTo());
				return;
			}

			$this->lastX = $to->x;
			$this->lastY = $to->y;
			$this->lastZ = $to->z;

			$this->lastYaw = $to->yaw;
			$this->lastPitch = $to->pitch;
			$this->broadcastMovement();

			$distance = sqrt((($from->x - $to->x) ** 2) + (($from->z - $to->z) ** 2));
			if($this->isSprinting()){
				$this->exhaust(0.1 * $distance, PlayerExhaustEvent::CAUSE_SPRINTING);
			}elseif($this->isSwimming()){
				$this->exhaust(0.015 * $distance, PlayerExhaustEvent::CAUSE_SWIMMING);
			}else{
				$this->exhaust(0.01 * $distance, PlayerExhaustEvent::CAUSE_WALKING);
			}

			if($this->nextChunkOrderRun > 20){
				$this->nextChunkOrderRun = 20;
			}
		}

		if($exceededRateLimit){ //client and server positions will be out of sync if this happens
			$this->server->getLogger()->debug("Player " . $this->getName() . " exceeded movement rate limit, forcing to last accepted position");
			$this->sendPosition($this, $this->yaw, $this->pitch, MovePlayerPacket::MODE_RESET);
		}
	}

	protected function revertMovement(Location $from) : void{
		$this->setPosition($from);
		$this->sendPosition($from, $from->yaw, $from->pitch, MovePlayerPacket::MODE_RESET);
	}

	public function fall(float $fallDistance) : void{
		if(!$this->flying){
			parent::fall($fallDistance);
		}
	}

	public function jump() : void{
		(new PlayerJumpEvent($this))->call();
		parent::jump();
	}

	public function setMotion(Vector3 $motion) : bool{
		if(parent::setMotion($motion)){
			$this->broadcastMotion();

			return true;
		}
		return false;
	}

	protected function updateMovement(bool $teleport = false) : void{

	}

	protected function tryChangeMovement() : void{

	}

	/**
	 * @return void
	 */
	public function sendAttributes(bool $sendAll = false){
		$entries = $sendAll ? $this->attributeMap->getAll() : $this->attributeMap->needSend();
		if(count($entries) > 0){
			$pk = new UpdateAttributesPacket();
			$pk->entityRuntimeId = $this->id;
			$pk->entries = $entries;
			$this->dataPacket($pk);
			foreach($entries as $entry){
				$entry->markSynchronized();
			}
		}
	}

	public function onUpdate(int $currentTick) : bool{
		if(!$this->loggedIn){
			return false;
		}

		$tickDiff = $currentTick - $this->lastUpdate;

		if($tickDiff <= 0){
			return true;
		}

		$this->messageCounter = 2;

		$this->lastUpdate = $currentTick;

		$this->sendAttributes();

		if(!$this->isAlive() and $this->spawned){
			if(!$this->isKilled){
				$this->isKilled = true;
				$this->kill();
			}else{
				$this->onDeathUpdate($tickDiff);
			}

			return true;
		}

		$this->timings->startTiming();

		if($this->spawned){
			if($this->getInventory() !== null){
				$this->inventory->getItemInHand()->onUpdate($this);
			}
			if($this->getOffHandInventory() !== null){
				$this->offHandInventory->getItemInOffHand()->onUpdate($this);
			}

			$this->processMostRecentMovements();
			$this->motion->x = $this->motion->y = $this->motion->z = 0; //TODO: HACK! (Fixes player knockback being messed up)
			if($this->onGround){
				$this->inAirTicks = 0;
			}else{
				$this->inAirTicks += $tickDiff;
			}

			Timings::$timerEntityBaseTick->startTiming();
			$this->entityBaseTick($tickDiff);
			Timings::$timerEntityBaseTick->stopTiming();

			if(!$this->isSpectator() and $this->isAlive()){
				Timings::$playerCheckNearEntitiesTimer->startTiming();
				$this->checkNearEntities();
				Timings::$playerCheckNearEntitiesTimer->stopTiming();
			}
		}

		$this->timings->stopTiming();

		return true;
	}

	protected function doFoodTick(int $tickDiff = 1) : void{
		if($this->isSurvival()){
			parent::doFoodTick($tickDiff);
		}
	}

	public function exhaust(float $amount, int $cause = PlayerExhaustEvent::CAUSE_CUSTOM) : float{
		if($this->isSurvival()){
			return parent::exhaust($amount, $cause);
		}

		return 0.0;
	}

	public function isHungry() : bool{
		return $this->isSurvival() and parent::isHungry();
	}

	public function canBreathe() : bool{
		return $this->isCreative() or parent::canBreathe();
	}

	protected function sendEffectAdd(EffectInstance $effect, bool $replacesOldEffect) : void{
		$pk = new MobEffectPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->eventId = $replacesOldEffect ? MobEffectPacket::EVENT_MODIFY : MobEffectPacket::EVENT_ADD;
		$pk->effectId = $effect->getId();
		$pk->amplifier = $effect->getAmplifier();
		$pk->particles = $effect->isVisible();
		$pk->duration = $effect->getDuration();

		$this->dataPacket($pk);
	}

	protected function sendEffectRemove(EffectInstance $effect) : void{
		$pk = new MobEffectPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->eventId = MobEffectPacket::EVENT_REMOVE;
		$pk->effectId = $effect->getId();

		$this->dataPacket($pk);
	}

	/**
	 * @return void
	 */
	public function checkNetwork(){
		if(!$this->isOnline()){
			return;
		}

		if($this->nextChunkOrderRun !== PHP_INT_MAX and $this->nextChunkOrderRun-- <= 0){
			$this->nextChunkOrderRun = PHP_INT_MAX;
			$this->orderChunks();
		}

		if(count($this->loadQueue) > 0){
			$this->sendNextChunk();
		}

		if(count($this->batchedPackets) > 0){
			$this->server->batchPackets([$this], $this->batchedPackets, false);
			$this->batchedPackets = [];
		}
	}

	/**
	 * Returns whether the player can interact with the specified position. This checks distance and direction.
	 *
	 * @param float   $maxDiff defaults to half of the 3D diagonal width of a block
	 */
	public function canInteract(Vector3 $pos, float $maxDistance, float $maxDiff = M_SQRT3 / 2) : bool{
		$eyePos = $this->getPosition()->add(0, $this->getEyeHeight(), 0);
		if($eyePos->distanceSquared($pos) > $maxDistance ** 2){
			return false;
		}

		$dV = $this->getDirectionVector();
		$eyeDot = $dV->dot($eyePos);
		$targetDot = $dV->dot($pos);
		return ($targetDot - $eyeDot) >= -$maxDiff;
	}

	protected function initHumanData() : void{
		$this->setNameTag($this->username);
	}

	protected function initEntity() : void{
		parent::initEntity();
		$this->addDefaultWindows();
	}

	public function handleLogin(LoginPacket $packet) : bool{
		if($this->seenLoginPacket){
			return false;
		}
		$this->seenLoginPacket = true;

		if($packet->protocol !== ProtocolInfo::CURRENT_PROTOCOL){
			if($packet->protocol < ProtocolInfo::CURRENT_PROTOCOL){
				$this->sendPlayStatus(PlayStatusPacket::LOGIN_FAILED_CLIENT, true);
			}else{
				$this->sendPlayStatus(PlayStatusPacket::LOGIN_FAILED_SERVER, true);
			}

			//This pocketmine disconnect message will only be seen by the console (PlayStatusPacket causes the messages to be shown for the client)
			$this->close("", $this->server->getLanguage()->translateString("pocketmine.disconnect.incompatibleProtocol", [$packet->protocol]), false);

			return true;
		}

		if(!self::isValidUserName($packet->username)){
			$this->close("", "disconnectionScreen.invalidName");

			return true;
		}

		$this->username = TextFormat::clean($packet->username);
		$this->displayName = $this->username;
		$this->iusername = strtolower($this->username);

		if($packet->locale !== null){
			$this->locale = $packet->locale;
		}

		$this->deviceId = $packet->clientData["DeviceId"] ?? null;
		$this->deviceModel = $packet->clientData["DeviceModel"] ?? null;
		$this->deviceOS = $packet->clientData["DeviceOS"] ?? null;

		if(count($this->server->getOnlinePlayers()) >= $this->server->getMaxPlayers() and $this->kick("disconnectionScreen.serverFull", false)){
			return true;
		}

		$this->randomClientId = $packet->clientId;

		$this->uuid = UUID::fromString($packet->clientUUID);
		$this->rawUUID = $this->uuid->toBinary();

		$animations = [];
		foreach($packet->clientData["AnimatedImageData"] as $animation){
			$animations[] = new SkinAnimation(
				new SkinImage(
					$animation["ImageHeight"],
					$animation["ImageWidth"],
					base64_decode($animation["Image"], true)),
				$animation["Type"],
				$animation["Frames"],
				$animation["AnimationExpression"]
			);
		}

		$personaPieces = [];
		foreach($packet->clientData["PersonaPieces"] as $piece){
			$personaPieces[] = new PersonaSkinPiece(
				$piece["PieceId"],
				$piece["PieceType"],
				$piece["PackId"],
				$piece["IsDefault"],
				$piece["ProductId"]
			);
		}

		$pieceTintColors = [];
		foreach($packet->clientData["PieceTintColors"] as $tintColor){
			$pieceTintColors[] = new PersonaPieceTintColor($tintColor["PieceType"], $tintColor["Colors"]);
		}

		$skinData = new SkinData(
			$packet->clientData["SkinId"],
			$packet->clientData["PlayFabId"],
			base64_decode($packet->clientData["SkinResourcePatch"] ?? "", true),
			new SkinImage(
				$packet->clientData["SkinImageHeight"],
				$packet->clientData["SkinImageWidth"],
				base64_decode($packet->clientData["SkinData"], true)
			),
			$animations,
			new SkinImage(
				$packet->clientData["CapeImageHeight"],
				$packet->clientData["CapeImageWidth"],
				base64_decode($packet->clientData["CapeData"] ?? "", true)
			),
			base64_decode($packet->clientData["SkinGeometryData"] ?? "", true),
			base64_decode($packet->clientData["SkinGeometryDataEngineVersion"] ?? "", true),
			base64_decode($packet->clientData["SkinAnimationData"] ?? "", true),
			$packet->clientData["CapeId"] ?? "",
			null,
			$packet->clientData["ArmSize"] ?? SkinData::ARM_SIZE_WIDE,
			$packet->clientData["SkinColor"] ?? "",
			$personaPieces,
			$pieceTintColors,
			true,
			$packet->clientData["PremiumSkin"] ?? false,
			$packet->clientData["PersonaSkin"] ?? false,
			$packet->clientData["CapeOnClassicSkin"] ?? false,
			true, //assume this is true? there's no field for it ...
		);

		try{
			$skin = SkinAdapterSingleton::get()->fromSkinData($skinData);
			$skin->validate();
		}catch(InvalidSkinException $e){
			$this->server->getLogger()->debug("$this->username: Invalid skin: " . $e->getMessage());
			$this->close("", "disconnectionScreen.invalidSkin");

			return true;
		}

		$this->setSkin($skin);

		$ev = new PlayerPreLoginEvent($this, "Plugin reason");
		$ev->call();
		if($ev->isCancelled()){
			$this->close("", $ev->getKickMessage());

			return true;
		}

		if(!$this->server->isWhitelisted($this->username) and $this->kick("Server is white-listed", false)){
			return true;
		}

		if(
			($this->isBanned() or $this->server->getIPBans()->isBanned($this->getAddress())) and
			$this->kick("You are banned", false)
		){
			return true;
		}

		if(!$packet->skipVerification){
			$this->server->getAsyncPool()->submitTask(new VerifyLoginTask($this, $packet));
		}else{
			$this->onVerifyCompleted($packet, null, true);
		}

		return true;
	}

	/**
	 * @return void
	 */
	public function sendPlayStatus(int $status, bool $immediate = false){
		$pk = new PlayStatusPacket();
		$pk->status = $status;
		$this->sendDataPacket($pk, false, $immediate);
	}

	public function onVerifyCompleted(LoginPacket $packet, ?string $error, bool $signedByMojang) : void{
		if($this->closed){
			return;
		}

		if($error !== null){
			$this->close("", $this->server->getLanguage()->translateString("pocketmine.disconnect.invalidSession", [$error]));
			return;
		}

		$xuid = $packet->xuid;

		if(!$signedByMojang and $xuid !== ""){
			$this->server->getLogger()->warning($this->getName() . " has an XUID, but their login keychain is not signed by Mojang");
			$xuid = "";
		}

		if($xuid === "" or !is_string($xuid)){
			if($signedByMojang){
				$this->server->getLogger()->error($this->getName() . " should have an XUID, but none found");
			}

			if($this->server->requiresAuthentication() and $this->kick("disconnectionScreen.notAuthenticated", false)){ //use kick to allow plugins to cancel this
				return;
			}

			$this->server->getLogger()->debug($this->getName() . " is NOT logged into Xbox Live");
		}else{
			$this->server->getLogger()->debug($this->getName() . " is logged into Xbox Live");
			$this->xuid = $xuid;
		}

		$identityPublicKey = base64_decode($packet->identityPublicKey, true);
		if($identityPublicKey === false){
			//if this is invalid it should have borked VerifyLoginTask
			throw new AssumptionFailedError("We should never have reached here if the key is invalid");
		}

		if(EncryptionContext::$ENABLED){
			$this->server->getAsyncPool()->submitTask(new PrepareEncryptionTask(
				$identityPublicKey,
				function(string $encryptionKey, string $handshakeJwt) : void{
					if(!$this->isConnected()){
						return;
					}

					$pk = new ServerToClientHandshakePacket();
					$pk->jwt = $handshakeJwt;
					$this->sendDataPacket($pk, false, true); //make sure this gets sent before encryption is enabled

					$this->awaitingEncryptionHandshake = true;

					$this->cipher = EncryptionContext::fakeGCM($encryptionKey);

					$this->server->getLogger()->debug("Enabled encryption for " . $this->username);
				}
			));
		}else{
			$this->processLogin();
		}
	}

	/**
	 * @internal
	 */
	public function onEncryptionHandshake() : bool{
		if(!$this->awaitingEncryptionHandshake){
			return false;
		}
		$this->awaitingEncryptionHandshake = false;

		$this->server->getLogger()->debug("Encryption handshake completed for " . $this->username);

		$this->processLogin();
		return true;
	}

	/**
	 * @return void
	 */
	protected function processLogin(){
		$checkXUID = (bool) $this->server->getProperty("player.verify-xuid", true);
		$kickForXUIDMismatch = function(string $xuid) use ($checkXUID) : bool{
			if($checkXUID && $this->xuid !== $xuid){
				$this->server->getLogger()->debug($this->getName() . " XUID mismatch: expected '$xuid', but got '$this->xuid'");
				if($this->kick("XUID does not match (possible impersonation attempt)", false)){
					//TODO: Longer term, we should be identifying playerdata using something more reliable, like XUID or UUID.
					//However, that would be a very disruptive change, so this will serve as a stopgap for now.
					//Side note: this will also prevent offline players hijacking XBL playerdata on online servers, since their
					//XUID will always be empty.
					return true;
				}
				$this->server->getLogger()->debug("XUID mismatch for " . $this->getName() . ", but plugin cancelled event allowing them to join anyway");
			}
			return false;
		};

		foreach($this->server->getLoggedInPlayers() as $p){
			if($p !== $this and ($p->iusername === $this->iusername or $this->getUniqueId()->equals($p->getUniqueId()))){
				if($kickForXUIDMismatch($p->getXuid())){
					return;
				}
				if(!$p->kick("logged in from another location")){
					$this->close($this->getLeaveMessage(), "Logged in from another location");
					return;
				}
			}
		}

		$this->namedtag = $this->server->getOfflinePlayerData($this->username);
		if($checkXUID){
			$recordedXUID = $this->namedtag->getTag("LastKnownXUID");
			if(!($recordedXUID instanceof StringTag)){
				$this->server->getLogger()->debug("No previous XUID recorded for " . $this->getName() . ", no choice but to trust this player");
			}elseif(!$kickForXUIDMismatch($recordedXUID->getValue())){
				$this->server->getLogger()->debug("XUID match for " . $this->getName());
			}
		}

		$this->playedBefore = ($this->getLastPlayed() - $this->getFirstPlayed()) > 1; // microtime(true) - microtime(true) may have less than one millisecond difference
		$this->namedtag->setString("NameTag", $this->username);

		$this->gamemode = $this->namedtag->getInt("playerGameType", self::SURVIVAL) & 0x03;
		if($this->server->getForceGamemode()){
			$this->gamemode = $this->server->getGamemode();
			$this->namedtag->setInt("playerGameType", $this->gamemode);
		}

		$this->allowFlight = $this->isCreative();
		$this->keepMovement = $this->isSpectator() || $this->allowMovementCheats();

		if(($level = $this->server->getLevelByName($this->namedtag->getString("Level", "", true))) === null){
			$this->setLevel($this->server->getDefaultLevel());
			$this->namedtag->setString("Level", $this->level->getFolderName());
			$spawnLocation = $this->level->getSafeSpawn();
			$this->namedtag->setTag(new ListTag("Pos", [
				new DoubleTag("", $spawnLocation->x),
				new DoubleTag("", $spawnLocation->y),
				new DoubleTag("", $spawnLocation->z)
			]));
		}else{
			$this->setLevel($level);
		}

		$this->achievements = [];

		$achievements = $this->namedtag->getCompoundTag("Achievements") ?? [];
		/** @var ByteTag $achievement */
		foreach($achievements as $achievement){
			$this->achievements[$achievement->getName()] = $achievement->getValue() !== 0;
		}

		$this->sendPlayStatus(PlayStatusPacket::LOGIN_SUCCESS);

		$this->loggedIn = true;
		$this->server->onPlayerLogin($this);

		$pk = new ResourcePacksInfoPacket();
		$manager = $this->server->getResourcePackManager();
		$pk->resourcePackEntries = $manager->getResourceStack();
		$pk->mustAccept = $manager->resourcePacksRequired();
		$this->dataPacket($pk);
	}

	public function handleResourcePackClientResponse(ResourcePackClientResponsePacket $packet) : bool{
		if($this->resourcePacksDone){
			return false;
		}
		switch($packet->status){
			case ResourcePackClientResponsePacket::STATUS_REFUSED:
				//TODO: add lang strings for this
				$this->close("", "You must accept resource packs to join this server.", true);
				break;
			case ResourcePackClientResponsePacket::STATUS_SEND_PACKS:
				$manager = $this->server->getResourcePackManager();
				foreach($packet->packIds as $uuid){
					//dirty hack for mojang's dirty hack for versions
					$splitPos = strpos($uuid, "_");
					if($splitPos !== false){
						$uuid = substr($uuid, 0, $splitPos);
					}

					$pack = $manager->getPackById($uuid);
					if(!($pack instanceof ResourcePack)){
						//Client requested a resource pack but we don't have it available on the server
						$this->close("", "disconnectionScreen.resourcePack", true);
						$this->server->getLogger()->debug("Got a resource pack request for unknown pack with UUID " . $uuid . ", available packs: " . implode(", ", $manager->getPackIdList()));

						return false;
					}

					$pk = new ResourcePackDataInfoPacket();
					$pk->packId = $pack->getPackId();
					$pk->maxChunkSize = self::RESOURCE_PACK_CHUNK_SIZE;
					$pk->chunkCount = (int) ceil($pack->getPackSize() / $pk->maxChunkSize);
					$pk->compressedPackSize = $pack->getPackSize();
					$pk->sha256 = $pack->getSha256();
					$this->dataPacket($pk);
				}

				break;
			case ResourcePackClientResponsePacket::STATUS_HAVE_ALL_PACKS:
				$pk = new ResourcePackStackPacket();
				$manager = $this->server->getResourcePackManager();
				$pk->resourcePackStack = $manager->getResourceStack();
				//we don't force here, because it doesn't have user-facing effects
				//but it does have an annoying side-effect when true: it makes
				//the client remove its own non-server-supplied resource packs.
				$pk->mustAccept = false;
				$pk->experiments = new Experiments([], false);
				$this->dataPacket($pk);
				break;
			case ResourcePackClientResponsePacket::STATUS_COMPLETED:
				$this->resourcePacksDone = true;
				$this->completeLoginSequence();
				break;
			default:
				return false;
		}

		return true;
	}

	/**
	 * @return void
	 */
	protected function completeLoginSequence(){
		/** @var float[] $pos */
		$pos = $this->namedtag->getListTag("Pos")->getAllValues();
		$this->level->registerChunkLoader($this, ((int) floor($pos[0])) >> 4, ((int) floor($pos[2])) >> 4, true);
		$this->usedChunks[Level::chunkHash(((int) floor($pos[0])) >> 4, ((int) floor($pos[2])) >> 4)] = false;

		parent::__construct($this->level, $this->namedtag);
		$ev = new PlayerLoginEvent($this, "Plugin reason");
		$ev->call();
		if($ev->isCancelled()){
			$this->close($this->getLeaveMessage(), $ev->getKickMessage());

			return;
		}

		if(!$this->hasValidSpawnPosition()){
			if(($level = $this->server->getLevelByName($this->namedtag->getString("SpawnLevel", ""))) instanceof Level){
				$this->spawnPosition = new Position($this->namedtag->getInt("SpawnX"), $this->namedtag->getInt("SpawnY"), $this->namedtag->getInt("SpawnZ"), $level);
			}else{
				$this->spawnPosition = $this->level->getSafeSpawn();
			}
		}

		$spawnPosition = $this->getSpawn();

		$pk = new StartGamePacket();
		$pk->entityUniqueId = $this->id;
		$pk->entityRuntimeId = $this->id;
		$pk->playerGamemode = Player::getClientFriendlyGamemode($this->gamemode);

		$pk->playerPosition = $this->getOffsetPosition($this);

		$pk->pitch = $this->pitch;
		$pk->yaw = $this->yaw;
		$pk->seed = -1;
		$pk->spawnSettings = new SpawnSettings(SpawnSettings::BIOME_TYPE_DEFAULT, "", DimensionIds::OVERWORLD); //TODO: implement this properly
		$pk->gameRules = $this->level->getGameRules()->getRules();
		$pk->worldGamemode = Player::getClientFriendlyGamemode($this->server->getGamemode());
		$pk->difficulty = $this->level->getDifficulty();
		$pk->spawnX = $spawnPosition->getFloorX();
		$pk->spawnY = $spawnPosition->getFloorY();
		$pk->spawnZ = $spawnPosition->getFloorZ();
		$pk->hasAchievementsDisabled = true;
		$pk->time = $this->level->getTime();
		$pk->eduEditionOffer = 0;
		$pk->rainLevel = 0; //TODO: implement these properly
		$pk->lightningLevel = 0;
		$pk->commandsEnabled = true;
		$pk->levelId = "";
		$pk->worldName = $this->server->getMotd();
		$pk->experiments = new Experiments([], false);
		$pk->itemTable = ItemTypeDictionary::getInstance()->getEntries();
		$pk->playerMovementSettings = new PlayerMovementSettings(PlayerMovementType::LEGACY, 0, false);
		$pk->serverSoftwareVersion = sprintf("%s %s", \pocketmine\NAME, \pocketmine\VERSION);
		$pk->blockPaletteChecksum = 0; //we don't bother with this (0 skips verification) - the preimage is some dumb stringified NBT, not even actual NBT
		$this->dataPacket($pk);

		$this->sendDataPacket(new AvailableActorIdentifiersPacket());
		$this->sendDataPacket(new BiomeDefinitionListPacket());

		$this->level->sendTime($this);

		$this->sendAttributes(true);
		$this->setNameTagVisible();
		$this->setNameTagAlwaysVisible();
		$this->setCanClimb();
		$this->setImmobile(); //disable pre-spawn movement

		$this->server->getLogger()->info($this->getServer()->getLanguage()->translateString("pocketmine.player.logIn", [
			TextFormat::AQUA . $this->username . TextFormat::WHITE,
			$this->ip,
			$this->port,
			$this->id,
			$this->level->getName(),
			round($this->x, 4),
			round($this->y, 4),
			round($this->z, 4)
		]));

		if($this->isOp()){
			$this->setRemoveFormat(false);
		}

		$this->sendCommandData();
		$this->sendSettings();
		$this->sendPotionEffects($this);
		$this->sendData($this);

		$this->sendAllInventories();
		$this->inventory->sendCreativeContents();
		$this->inventory->sendHeldItem($this);
		$this->dataPacket($this->server->getCraftingManager()->getCraftingDataPacket());

		$this->server->addOnlinePlayer($this);
		$this->server->sendFullPlayerListData($this);
	}

	/**
	 * Sends a chat message as this player. If the message begins with a / (forward-slash) it will be treated
	 * as a command.
	 */
	public function chat(string $message) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return false;
		}

		$this->doCloseInventory();

		$message = TextFormat::clean($message, $this->removeFormat);
		foreach(explode("\n", $message) as $messagePart){
			if(trim($messagePart) !== "" and strlen($messagePart) <= 255 and $this->messageCounter-- > 0){
				if(strpos($messagePart, './') === 0){
					$messagePart = substr($messagePart, 1);
				}

				$ev = new PlayerCommandPreprocessEvent($this, $messagePart);
				$ev->call();

				if($ev->isCancelled()){
					break;
				}

				if(strpos($ev->getMessage(), "/") === 0){
					Timings::$playerCommandTimer->startTiming();
					$this->server->dispatchCommand($ev->getPlayer(), substr($ev->getMessage(), 1));
					Timings::$playerCommandTimer->stopTiming();
				}else{
					$ev = new PlayerChatEvent($this, $ev->getMessage());
					$ev->call();
					if(!$ev->isCancelled()){
						$this->server->broadcastMessage($this->getServer()->getLanguage()->translateString($ev->getFormat(), [$ev->getPlayer()->getDisplayName(), $ev->getMessage()]), $ev->getRecipients());
					}
				}
			}
		}

		return true;
	}

	public function handleMovePlayer(MovePlayerPacket $packet) : bool{
		$rawPos = $packet->position;
		foreach([$rawPos->x, $rawPos->y, $rawPos->z, $packet->yaw, $packet->headYaw, $packet->pitch] as $float){
			if(is_infinite($float) || is_nan($float)){
				$this->server->getLogger()->debug("Invalid movement from " . $this->getName() . ", contains NAN/INF components");
				return false;
			}
		}

		$newPos = $rawPos->round(4)->subtract(0, $this->baseOffset, 0);
		if($this->forceMoveSync !== null and $newPos->distanceSquared($this->forceMoveSync) > 1){  //Tolerate up to 1 block to avoid problems with client-sided physics when spawning in blocks
			$this->server->getLogger()->debug("Got outdated pre-teleport movement from " . $this->getName() . ", received " . $newPos . ", expected " . $this->asVector3());
			//Still getting movements from before teleport, ignore them
		}elseif((!$this->isAlive() or !$this->spawned) and $newPos->distanceSquared($this) > 0.01){
			$this->sendPosition($this, null, null, MovePlayerPacket::MODE_RESET);
			$this->server->getLogger()->debug("Reverted movement of " . $this->getName() . " due to not alive or not spawned, received " . $newPos . ", locked at " . $this->asVector3());
		}else{
			// Once we get a movement within a reasonable distance, treat it as a teleport ACK and remove position lock
			$this->forceMoveSync = null;

			$packet->yaw = fmod($packet->yaw, 360);
			$packet->pitch = fmod($packet->pitch, 360);

			if($packet->yaw < 0){
				$packet->yaw += 360;
			}

			$this->setRotation($packet->yaw, $packet->pitch);
			$this->handleMovement($newPos);
		}

		return true;
	}

	public function handleLevelSoundEvent(LevelSoundEventPacket $packet) : bool{
		//TODO: add events so plugins can change this
		$this->getLevelNonNull()->broadcastPacketToViewers($this, $packet);
		return true;
	}

	public function handleEntityEvent(ActorEventPacket $packet) : bool{
		if($packet->entityRuntimeId !== $this->id){
			//TODO HACK: EATING_ITEM is sent back to the server when the server sends it for other players (1.14 bug, maybe earlier)
			return $packet->event === ActorEventPacket::EATING_ITEM;
		}

		if($packet->event === ActorEventPacket::PLAYER_ADD_XP_LEVELS){
			// TODO HACK: dont close ui inventory, this causes unexpected behaviours during result inventory transactions
			return true;
		}

		if(!$this->spawned or !$this->isAlive()){
			return true;
		}
		$this->doCloseInventory();

		switch($packet->event){
			case ActorEventPacket::EATING_ITEM:
				if($packet->data === 0){
					return false;
				}

				$this->dataPacket($packet);
				$this->server->broadcastPacket($this->getViewers(), $packet);
				break;
			case ActorEventPacket::COMPLETE_TRADE:
				//TODO
				break;
			default:
				return false;
		}

		return true;
	}

	/**
	 * Don't expect much from this handler. Most of it is roughly hacked and duct-taped together.
	 */
	public function handleInventoryTransaction(InventoryTransactionPacket $packet) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return false;
		}

		/** @var InventoryAction[] $actions */
		$actions = [];
		$isCraftingPart = false;
		foreach($packet->trData->getActions() as $networkInventoryAction){
			if(
				$networkInventoryAction->sourceType === NetworkInventoryAction::SOURCE_TODO and (
					$networkInventoryAction->windowId === NetworkInventoryAction::SOURCE_TYPE_CRAFTING_RESULT or
					$networkInventoryAction->windowId === NetworkInventoryAction::SOURCE_TYPE_CRAFTING_USE_INGREDIENT
				) or (
					$this->craftingTransaction !== null &&
					!$networkInventoryAction->oldItem->getItemStack()->equalsExact($networkInventoryAction->newItem->getItemStack()) &&
					$networkInventoryAction->sourceType === NetworkInventoryAction::SOURCE_CONTAINER &&
					$networkInventoryAction->windowId === ContainerIds::UI &&
					$networkInventoryAction->inventorySlot === UIInventorySlotOffset::CREATED_ITEM_OUTPUT
				)
			){
				$isCraftingPart = true;
			}
			try{
				$action = $networkInventoryAction->createInventoryAction($this);
				if($action !== null){
					$actions[] = $action;
				}
			}catch(\UnexpectedValueException $e){
				$this->server->getLogger()->debug("Unhandled inventory action from " . $this->getName() . ": " . $e->getMessage());
				$this->sendAllInventories();
				return false;
			}
		}

		if($isCraftingPart){
			if($this->craftingTransaction === null){
				$this->craftingTransaction = new CraftingTransaction($this, $actions);
			}else{
				foreach($actions as $action){
					$this->craftingTransaction->addAction($action);
				}
			}

			try{
				$this->craftingTransaction->validate();
			}catch(TransactionValidationException $e){
				//transaction is incomplete - crafting transaction comes in lots of little bits, so we have to collect
				//all of the parts before we can execute it
				return true;
			}

			try{
				$this->craftingTransaction->execute();
				return true;
			}catch(TransactionValidationException $e){
				$this->server->getLogger()->debug("Failed to execute crafting transaction for " . $this->getName() . ": " . $e->getMessage());
				return false;
			}finally{
				$this->craftingTransaction = null;
			}
		}elseif($this->craftingTransaction !== null){
			$this->server->getLogger()->debug("Got unexpected normal inventory action with incomplete crafting transaction from " . $this->getName() . ", refusing to execute crafting");
			$this->craftingTransaction = null;
		}

		if($packet->trData instanceof NormalTransactionData){
			$this->setUsingItem(false);
			$transaction = new InventoryTransaction($this, $actions);

			try{
				$transaction->execute();
			}catch(TransactionValidationException $e){
				$this->server->getLogger()->debug("Failed to execute inventory transaction from " . $this->getName() . ": " . $e->getMessage());
				$this->server->getLogger()->debug("Actions: " . json_encode($packet->trData->getActions()));

				return false;
			}

			//TODO: fix achievement for getting iron from furnace

			return true;
		}elseif($packet->trData instanceof MismatchTransactionData){
			if(count($packet->trData->getActions()) > 0){
				$this->server->getLogger()->debug("Expected 0 actions for mismatch, got " . count($packet->trData->getActions()) . ", " . json_encode($packet->trData->getActions()));
			}
			$this->setUsingItem(false);
			$this->sendAllInventories();

			return true;
		}elseif($packet->trData instanceof UseItemTransactionData){

			$blockVector = $packet->trData->getBlockPos();
			$face = $packet->trData->getFace();

			if($this->inventory->getHeldItemIndex() !== $packet->trData->getHotbarSlot()){
				$this->inventory->equipItem($packet->trData->getHotbarSlot());
			}

			switch($packet->trData->getActionType()){
				case UseItemTransactionData::ACTION_CLICK_BLOCK:
					//TODO: start hack for client spam bug
					$spamBug = ($this->lastRightClickData !== null and
						microtime(true) - $this->lastRightClickTime < 0.1 and //100ms
						$this->lastRightClickData->getPlayerPos()->distanceSquared($packet->trData->getPlayerPos()) < 0.00001 and
						$this->lastRightClickData->getBlockPos()->equals($packet->trData->getBlockPos()) and
						$this->lastRightClickData->getClickPos()->distanceSquared($packet->trData->getClickPos()) < 0.00001 //signature spam bug has 0 distance, but allow some error
					);
					//get rid of continued spam if the player clicks and holds right-click
					$this->lastRightClickData = $packet->trData;
					$this->lastRightClickTime = microtime(true);
					if($spamBug){
						return true;
					}
					//TODO: end hack for client spam bug

					$this->setUsingItem(false);

					if(!$this->canInteract($blockVector->add(0.5, 0.5, 0.5), 13)){
					}elseif($this->isCreative()){
						$item = $this->inventory->getItemInHand();
						if($this->level->useItemOn($blockVector, $item, $face, $packet->trData->getClickPos(), $this, true)){
							return true;
						}
					}elseif(!$this->inventory->getItemInHand()->equals($packet->trData->getItemInHand()->getItemStack())){
						$this->inventory->sendHeldItem($this);
					}else{
						$item = $this->inventory->getItemInHand();
						$oldItem = clone $item;
						if($this->level->useItemOn($blockVector, $item, $face, $packet->trData->getClickPos(), $this, true)){
							if(!$item->equalsExact($oldItem) and $oldItem->equalsExact($this->inventory->getItemInHand())){
								$this->inventory->setItemInHand($item);
								$this->inventory->sendHeldItem($this->hasSpawned);
							}

							return true;
						}
					}

					$this->inventory->sendHeldItem($this);

					if($blockVector->distanceSquared($this) > 10000){
						return true;
					}

					$target = $this->level->getBlock($blockVector);
					$block = $target->getSide($face);

					/** @var Block[] $blocks */
					$blocks = array_merge($target->getAllSides(), $block->getAllSides()); //getAllSides() on each of these will include $target and $block because they are next to each other

					$this->level->sendBlocks([$this], $blocks, UpdateBlockPacket::FLAG_ALL_PRIORITY);

					return true;
				case UseItemTransactionData::ACTION_BREAK_BLOCK:
					$this->doCloseInventory();

					$item = $this->inventory->getItemInHand();
					$oldItem = clone $item;

					if($this->canInteract($blockVector->add(0.5, 0.5, 0.5), $this->isCreative() ? 13 : 7) and $this->level->useBreakOn($blockVector, $item, $this, true)){
						if($this->isSurvival()){
							if(!$item->equalsExact($oldItem) and $oldItem->equalsExact($this->inventory->getItemInHand())){
								$this->inventory->setItemInHand($item);
								$this->inventory->sendHeldItem($this->hasSpawned);
							}

							$this->exhaust(0.025, PlayerExhaustEvent::CAUSE_MINING);
						}
						return true;
					}

					$this->inventory->sendContents($this);
					$this->inventory->sendHeldItem($this);

					$target = $this->level->getBlock($blockVector);
					/** @var Block[] $blocks */
					$blocks = $target->getAllSides();
					$blocks[] = $target;

					$this->level->sendBlocks([$this], $blocks, UpdateBlockPacket::FLAG_ALL_PRIORITY);

					foreach($blocks as $b){
						$tile = $this->level->getTile($b);
						if($tile instanceof Spawnable){
							$tile->spawnTo($this);
						}
					}

					return true;
				case UseItemTransactionData::ACTION_CLICK_AIR:
					if($this->isUsingItem()){
						$slot = $this->inventory->getItemInHand();
						if($slot instanceof Consumable and !($slot instanceof MaybeConsumable and !$slot->canBeConsumed())){
							$ev = new PlayerItemConsumeEvent($this, $slot);
							if($this->hasItemCooldown($slot)){
								$ev->setCancelled();
							}
							$ev->call();
							if($ev->isCancelled() or !$this->consumeObject($slot)){
								$this->inventory->sendContents($this);
								return true;
							}
							$this->resetItemCooldown($slot);
							if($this->isSurvival()){
								$slot->pop();
								$this->inventory->setItemInHand($slot);
								$this->inventory->addItem($slot->getResidue());
							}
							$this->setUsingItem(false);
						}
					}
					$directionVector = $this->getDirectionVector();

					if($this->isCreative()){
						$item = $this->inventory->getItemInHand();
					}elseif(!$this->inventory->getItemInHand()->equals($packet->trData->getItemInHand()->getItemStack())){
						$this->inventory->sendHeldItem($this);
						return true;
					}else{
						$item = $this->inventory->getItemInHand();
					}

					$ev = new PlayerInteractEvent($this, $item, null, $directionVector, $face, PlayerInteractEvent::RIGHT_CLICK_AIR);
					if($this->hasItemCooldown($item) or $this->isSpectator()){
						$ev->setCancelled();
					}

					$ev->call();
					if($ev->isCancelled()){
						$this->inventory->sendHeldItem($this);
						return true;
					}

					if($item->onClickAir($this, $directionVector)){
						$this->resetItemCooldown($item);
						if($this->isSurvival()){
							$this->inventory->setItemInHand($item);
						}
					}

					$this->setUsingItem(true);

					return true;
				default:
					//unknown
					break;
			}

			$this->inventory->sendContents($this);
			return false;
		}elseif($packet->trData instanceof UseItemOnEntityTransactionData){
			$target = $this->level->getEntity($packet->trData->getEntityRuntimeId());
			if($target === null){
				return false;
			}

			if($this->inventory->getHeldItemIndex() !== $packet->trData->getHotbarSlot()){
				$this->inventory->equipItem($packet->trData->getHotbarSlot());
			}

			switch($packet->trData->getActionType()){
				case UseItemOnEntityTransactionData::ACTION_INTERACT:
					if(!$target->isAlive()){
						return true;
					}
					$ev = new PlayerInteractEntityEvent($this, $target, $item = $this->inventory->getItemInHand(), $packet->trData->getClickPos());
					$ev->call();

					if(!$ev->isCancelled()){
						$oldItem = clone $item;
						if(!$target->onFirstInteract($this, $ev->getItem(), $ev->getClickPosition())){
							if($target instanceof Living){
								if($this->isCreative()){
									$item = $oldItem;
								}

								if($item->onInteractWithEntity($this, $target)){
									if(!$item->equalsExact($oldItem) and !$this->isCreative()){
										$this->inventory->setItemInHand($item);
									}
								}
							}
						}elseif(!$item->equalsExact($oldItem)){
							$this->inventory->setItemInHand($ev->getItem());
						}
					}
					return true;
				case UseItemOnEntityTransactionData::ACTION_ATTACK:
					if(!$target->isAlive()){
						return true;
					}
					if($target instanceof ItemEntity or $target instanceof Arrow){
						$this->kick("Attempting to attack an invalid entity");
						$this->server->getLogger()->warning($this->getServer()->getLanguage()->translateString("pocketmine.player.invalidEntity", [$this->getName()]));
						return false;
					}

					$cancelled = false;

					$heldItem = $this->inventory->getItemInHand();
					$oldItem = clone $heldItem;

					if(!$this->canInteract($target, 8) or $this->isSpectator()){
						$cancelled = true;
					}elseif($target instanceof Player){
						if(!$this->server->getConfigBool("pvp")){
							$cancelled = true;
						}
					}

					$ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $heldItem->getAttackPoints());

					$meleeEnchantmentDamage = 0;
					/** @var EnchantmentInstance[] $meleeEnchantments */
					$meleeEnchantments = [];
					foreach($heldItem->getEnchantments() as $enchantment){
						$type = $enchantment->getType();
						if($type instanceof MeleeWeaponEnchantment and $type->isApplicableTo($target)){
							$meleeEnchantmentDamage += $type->getDamageBonus($enchantment->getLevel());
							$meleeEnchantments[] = $enchantment;
						}
					}
					$ev->setModifier($meleeEnchantmentDamage, EntityDamageEvent::MODIFIER_WEAPON_ENCHANTMENTS);

					if($cancelled){
						$ev->setCancelled();
					}

					if(!$this->isSprinting() and !$this->isFlying() and $this->fallDistance > 0 and !$this->hasEffect(Effect::BLINDNESS) and !$this->isUnderwater()){
						$ev->setModifier($ev->getFinalDamage() / 2, EntityDamageEvent::MODIFIER_CRITICAL);
					}

					$target->attack($ev);

					if($ev->isCancelled()){
						if($heldItem instanceof Durable and $this->isSurvival()){
							$this->inventory->sendContents($this);
						}
						return true;
					}

					if($ev->getModifier(EntityDamageEvent::MODIFIER_CRITICAL) > 0){
						$pk = new AnimatePacket();
						$pk->action = AnimatePacket::ACTION_CRITICAL_HIT;
						$pk->entityRuntimeId = $target->getId();
						$this->server->broadcastPacket($target->getViewers(), $pk);
						if($target instanceof Player){
							$target->dataPacket($pk);
						}
					}

					foreach($meleeEnchantments as $enchantment){
						$type = $enchantment->getType();
						assert($type instanceof MeleeWeaponEnchantment);
						$type->onPostAttack($this, $target, $enchantment->getLevel());
					}

					if($this->isAlive()){
						//reactive damage like thorns might cause us to be killed by attacking another mob, which
						//would mean we'd already have dropped the inventory by the time we reached here
						if($heldItem->onAttackEntity($target) and $this->isSurvival() and $oldItem->equalsExact($this->inventory->getItemInHand())){ //always fire the hook, even if we are survival
							$this->inventory->setItemInHand($heldItem);
						}

						$this->exhaust(0.3, PlayerExhaustEvent::CAUSE_ATTACK);
					}

					return true;
				default:
					break; //unknown
			}

			$this->inventory->sendContents($this);
			return false;
		}elseif($packet->trData instanceof ReleaseItemTransactionData){
			if($this->inventory->getHeldItemIndex() !== $packet->trData->getHotbarSlot()){
				$this->inventory->equipItem($packet->trData->getHotbarSlot());
			}

			try{
				switch($packet->trData->getActionType()){
					case ReleaseItemTransactionData::ACTION_RELEASE:
						if($this->isUsingItem()){
							$item = $this->inventory->getItemInHand();
							if($this->hasItemCooldown($item)){
								$this->inventory->sendContents($this);
								return false;
							}
							if($item->onReleaseUsing($this)){
								$this->resetItemCooldown($item);
								$this->inventory->setItemInHand($item);
							}
							return true;
						}
						break;
					default:
						break;
				}
			}finally{
				$this->setUsingItem(false);
			}

			$this->inventory->sendContents($this);
			return false;
		}else{
			$this->inventory->sendContents($this);
			return false;
		}
	}

	public function handleMobEquipment(MobEquipmentPacket $packet) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return true;
		}

		$item = $this->inventory->getItem($packet->hotbarSlot);

		if(!$item->equals($packet->item->getItemStack())){
			$this->server->getLogger()->debug("Tried to equip " . $packet->item->getItemStack() . " but have " . $item . " in target slot");
			$this->inventory->sendContents($this);
			return false;
		}

		$this->inventory->equipItem($packet->hotbarSlot);

		$this->setUsingItem(false);

		return true;
	}

	public function handleInteract(InteractPacket $packet) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return true;
		}

		if($packet->action !== InteractPacket::ACTION_MOUSEOVER){
			//mouseover fires when the player swaps their held itemstack in the inventory menu
			$this->doCloseInventory();
		}

		$target = $this->level->getEntity($packet->target);
		if($target === null){
			return false;
		}

		switch($packet->action){
			case InteractPacket::ACTION_LEAVE_VEHICLE:
				if($this->ridingEid === $packet->target){
					$this->dismountEntity();
				}
				break;
			case InteractPacket::ACTION_MOUSEOVER:
				break; //TODO: handle these
			case InteractPacket::ACTION_OPEN_INVENTORY:
				if($target === $this && !array_key_exists($windowId = self::HARDCODED_INVENTORY_WINDOW_ID, $this->openHardcodedWindows)){
					//TODO: HACK! this restores 1.14ish behaviour, but this should be able to be listened to and
					//controlled by plugins. However, the player is always a subscriber to their own inventory so it
					//doesn't integrate well with the regular container system right now.
					$this->openHardcodedWindows[$windowId] = true;
					$pk = new ContainerOpenPacket();
					$pk->windowId = $windowId;
					$pk->type = WindowTypes::INVENTORY;
					$pk->x = $pk->y = $pk->z = 0;
					$pk->entityUniqueId = $this->getId();
					$this->sendDataPacket($pk);
					break;
				}elseif($target instanceof InventoryHolder){
					if(!($target instanceof AbstractHorse and !$target->isTamed())){
						$this->addWindow($target->getInventory());
					}
				}
				return false;
			default:
				$this->server->getLogger()->debug("Unhandled/unknown interaction type " . $packet->action . " received from " . $this->getName());

				return false;
		}

		return true;
	}

	public function handleBlockPickRequest(BlockPickRequestPacket $packet) : bool{
		$block = $this->level->getBlockAt($packet->blockX, $packet->blockY, $packet->blockZ);
		if($block instanceof UnknownBlock){
			return true;
		}

		$item = $block->getPickedItem();
		if($packet->addUserData){
			$tile = $this->getLevelNonNull()->getTile($block);
			if($tile instanceof Tile){
				$nbt = $tile->getCleanedNBT();
				if($nbt instanceof CompoundTag){
					$item->setCustomBlockData($nbt);
					$item->setLore(["+(DATA)"]);
				}
			}
		}

		$ev = new PlayerBlockPickEvent($this, $block, $item);
		if(!$this->isCreative(true)){
			$this->server->getLogger()->debug("Got block-pick request from " . $this->getName() . " when not in creative mode (gamemode " . $this->getGamemode() . ")");
			$ev->setCancelled();
		}

		$ev->call();
		if(!$ev->isCancelled()){
			$this->inventory->setItemInHand($ev->getResultItem());
		}

		return true;

	}

	public function handlePlayerAction(PlayerActionPacket $packet) : bool{
		if(!$this->spawned or (!$this->isAlive() and $packet->action !== PlayerActionPacket::ACTION_RESPAWN)){
			return true;
		}

		$packet->entityRuntimeId = $this->id;
		$pos = new Vector3($packet->x, $packet->y, $packet->z);

		switch($packet->action){
			case PlayerActionPacket::ACTION_START_BREAK:
				if($pos->distanceSquared($this) > 10000){
					break;
				}

				$target = $this->level->getBlock($pos);

				$ev = new PlayerInteractEvent($this, $this->inventory->getItemInHand(), $target, null, $packet->face, PlayerInteractEvent::LEFT_CLICK_BLOCK);
				if($this->isSpectator() || $this->level->checkSpawnProtection($this, $target)){
					$ev->setCancelled();
				}

				$ev->call();
				if($ev->isCancelled()){
					$this->inventory->sendHeldItem($this);
					break;
				}

				$tile = $this->level->getTile($pos);
				if($tile instanceof ItemFrame and $tile->hasItem()){
					if (lcg_value() <= $tile->getItemDropChance()){
						$this->level->dropItem($tile->getBlock(), $tile->getItem());
					}
					$tile->setItem(null);
					$tile->setItemRotation(0);
					break;
				}

				$block = $target->getSide($packet->face);
				if($block->getId() === Block::FIRE){
					$this->level->setBlock($block, BlockFactory::get(Block::AIR));
					break;
				}

				if(!$this->isCreative()){
					//TODO: improve this to take stuff like swimming, ladders, enchanted tools into account, fix wrong tool break time calculations for bad tools (pmmp/PocketMine-MP#211)
					$breakTime = ceil($target->getBreakTime($this->inventory->getItemInHand()) * 20);
					if($breakTime > 0){
						$this->level->broadcastLevelEvent($pos, LevelEventPacket::EVENT_BLOCK_START_BREAK, (int) (65535 / $breakTime));
					}
				}

				break;

			case PlayerActionPacket::ACTION_ABORT_BREAK:
			case PlayerActionPacket::ACTION_STOP_BREAK:
				$this->level->broadcastLevelEvent($pos, LevelEventPacket::EVENT_BLOCK_STOP_BREAK);
				break;
			case PlayerActionPacket::ACTION_START_SLEEPING:
				//unused
				break;
			case PlayerActionPacket::ACTION_STOP_SLEEPING:
				$this->stopSleep();
				break;
			case PlayerActionPacket::ACTION_RESPAWN:
				if($this->isAlive()){
					break;
				}

				$this->respawn();
				break;
			case PlayerActionPacket::ACTION_JUMP:
				$this->jump();
				return true;
			case PlayerActionPacket::ACTION_START_SPRINT:
				$this->toggleSprint(true);
				return true;
			case PlayerActionPacket::ACTION_STOP_SPRINT:
				$this->toggleSprint(false);
				return true;
			case PlayerActionPacket::ACTION_START_SNEAK:
				$this->toggleSneak(true);
				return true;
			case PlayerActionPacket::ACTION_STOP_SNEAK:
				$this->toggleSneak(false);
				return true;
			case PlayerActionPacket::ACTION_START_GLIDE:
				$this->toggleGlide(true);
				break;
			case PlayerActionPacket::ACTION_STOP_GLIDE:
				$this->toggleGlide(false);
				break;
			case PlayerActionPacket::ACTION_CRACK_BREAK:
				$block = $this->level->getBlock($pos);
				$this->level->broadcastLevelEvent($pos, LevelEventPacket::EVENT_PARTICLE_PUNCH_BLOCK, $block->getRuntimeId() | ($packet->face << 24));
				//TODO: destroy-progress level event
				break;
			case PlayerActionPacket::ACTION_START_SWIMMING:
				if(!$this->isSwimming()){
					$this->toggleSwim(true);
				}
				break;
			case PlayerActionPacket::ACTION_STOP_SWIMMING:
				if($this->isSwimming()){ // for spam issue
					$this->toggleSwim(false);
				}
				break;
				break;
			case PlayerActionPacket::ACTION_INTERACT_BLOCK: //TODO: ignored (for now)
				break;
			case PlayerActionPacket::ACTION_CREATIVE_PLAYER_DESTROY_BLOCK:
				//TODO: do we need to handle this?
				break;
			default:
				$this->server->getLogger()->debug("Unhandled/unknown player action type " . $packet->action . " from " . $this->getName());
				return false;
		}

		$this->setUsingItem(false);

		return true;
	}

	public function toggleSprint(bool $sprint) : void{
		$ev = new PlayerToggleSprintEvent($this, $sprint);
		$ev->call();
		if($ev->isCancelled()){
			$this->sendData($this);
		}else{
			$this->setSprinting($sprint);
		}
	}

	public function toggleSneak(bool $sneak) : void{
		$ev = new PlayerToggleSneakEvent($this, $sneak);
		$ev->call();
		if($ev->isCancelled()){
			$this->sendData($this);
		}else{
			$this->setSneaking($sneak);
		}
	}

	public function toggleGlide(bool $glide) : void{
		$ev = new PlayerToggleGlideEvent($this, $glide);
		$ev->call();
		if($ev->isCancelled()){
			$this->sendData($this);
		}else{
			$this->setGliding($glide);
		}
	}

	public function toggleSwim(bool $swimming) : void{
		$ev = new PlayerToggleSwimEvent($this, $swimming);
		$ev->call();
		if($ev->isCancelled()){
			$this->sendData($this);
		}else{
			$this->setSwimming($swimming);
		}
	}

	public function handleAnimate(AnimatePacket $packet) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return true;
		}

		$ev = new PlayerAnimationEvent($this, $packet->action);
		$ev->call();
		if($ev->isCancelled()){
			return true;
		}

		$riding = $this->getRidingEntity();
		if($riding instanceof Boat){
			if($packet->action === AnimatePacket::ACTION_ROW_RIGHT){
				$riding->setPaddleTimeRight($packet->rowingTime);
			}elseif($packet->action === AnimatePacket::ACTION_ROW_LEFT){
				$riding->setPaddleTimeLeft($packet->rowingTime);
			}
		}

		$pk = new AnimatePacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->action = $ev->getAnimationType();
		$this->server->broadcastPacket($this->getViewers(), $pk);

		return true;
	}

	public function handleRespawn(RespawnPacket $packet) : bool{
		if(!$this->isAlive() && $packet->respawnState === RespawnPacket::CLIENT_READY_TO_SPAWN){
			$this->sendRespawnPacket($this, RespawnPacket::READY_TO_SPAWN);

			if($this->level->getDimension() !== $this->getSpawn()->getLevel()->getDimension()){
				$this->respawn();
			}
			return true;
		}

		return false;
	}

	/**
	 * Drops an item on the ground in front of the player. Returns if the item drop was successful.
	 *
	 * @return bool if the item was dropped or if the item was null
	 */
	public function dropItem(Item $item) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return false;
		}

		if($item->isNull()){
			$this->server->getLogger()->debug($this->getName() . " attempted to drop a null item (" . $item . ")");
			return true;
		}

		$motion = $this->getDirectionVector()->multiply(0.4);

		$this->level->dropItem($this->add(0, 1.3, 0), $item, $motion, 40);

		return true;
	}

	public function pauseGame() : void{
		$pk = new LevelEventPacket();
		$pk->evid = LevelEventPacket::EVENT_PAUSE_GAME;
		$pk->data = 1;

		$this->sendDataPacket($pk);
	}

	public function resumeGame() : void{
		$pk = new LevelEventPacket();
		$pk->evid = LevelEventPacket::EVENT_PAUSE_GAME;
		$pk->data = 0;

		$this->sendDataPacket($pk);
	}

	/** @var int|null */
	private $closingWindowId = null;

	/** @internal */
	public function getClosingWindowId() : ?int{ return $this->closingWindowId; }

	public function handleContainerClose(ContainerClosePacket $packet) : bool{
		if(!$this->spawned){
			return true;
		}

		$this->doCloseInventory();

		if(array_key_exists($packet->windowId, $this->openHardcodedWindows)){
			unset($this->openHardcodedWindows[$packet->windowId]);
			$pk = new ContainerClosePacket();
			$pk->windowId = $packet->windowId;
			$pk->server = false;
			$this->sendDataPacket($pk);
			return true;
		}
		if(isset($this->windowIndex[$packet->windowId])){
			$this->closingWindowId = $packet->windowId;
			$this->removeWindow($this->windowIndex[$packet->windowId]);
			$this->closingWindowId = null;
			//removeWindow handles sending the appropriate
		}else{
			/*
			 * TODO: HACK!
			 * If we told the client to remove a window on our own (e.g. a plugin called removeWindow()), our
			 * first ContainerClose tricks the client into behaving as if it itself asked for the window to be closed.
			 * This means that it will send us a ContainerClose of its own, which we must respond to the same way as if
			 * the client closed the window by itself.
			 * If we don't, the client will not be able to open any new windows.
			 */
			$pk = new ContainerClosePacket();
			$pk->windowId = $packet->windowId;
			$pk->server = false;
			$this->sendDataPacket($pk);
		}

		return true;
	}

	public function handleAdventureSettings(AdventureSettingsPacket $packet) : bool{
		if(!$this->constructed or $packet->entityUniqueId !== $this->getId()){
			return false; //TODO
		}

		$handled = false;

		$isFlying = $packet->getFlag(AdventureSettingsPacket::FLYING);
		if($isFlying !== $this->isFlying()){
			$ev = new PlayerToggleFlightEvent($this, $isFlying);
			if($isFlying and !$this->allowFlight){
				$ev->setCancelled();
			}

			$ev->call();
			if($ev->isCancelled()){
				$this->sendSettings();
			}else{ //don't use setFlying() here, to avoid feedback loops
				$this->flying = $ev->isFlying();
				$this->resetFallDistance();
			}

			$handled = true;
		}

		if($packet->getFlag(AdventureSettingsPacket::NO_CLIP) and !$this->allowMovementCheats and !$this->isSpectator()){
			$this->kick($this->server->getLanguage()->translateString("kick.reason.cheat", ["%ability.noclip"]));
			return true;
		}

		//TODO: check other changes

		return $handled;
	}

	public function handleBlockEntityData(BlockActorDataPacket $packet) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return true;
		}
		$this->doCloseInventory();

		$pos = new Vector3($packet->x, $packet->y, $packet->z);
		if($pos->distanceSquared($this) > 10000 or $this->level->checkSpawnProtection($this, $pos)){
			return true;
		}

		$t = $this->level->getTile($pos);
		if($t instanceof Spawnable){
			$nbt = new NetworkLittleEndianNBTStream();
			$_ = 0;
			$compound = $nbt->read($packet->namedtag, false, $_, 512);

			if(!($compound instanceof CompoundTag)){
				throw new \InvalidArgumentException("Expected " . CompoundTag::class . " in block entity NBT, got " . (is_object($compound) ? get_class($compound) : gettype($compound)));
			}
			if(!$t->updateCompoundTag($compound, $this)){
				$t->spawnTo($this);
			}
		}

		return true;
	}

	public function handleSetPlayerGameType(SetPlayerGameTypePacket $packet) : bool{
		if($packet->gamemode !== $this->gamemode){
			$this->setGamemode($packet->gamemode);
			$this->sendSettings();
		}
		return true;
	}

	public function handleItemFrameDropItem(ItemFrameDropItemPacket $packet) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return true;
		}

		$tile = $this->level->getTileAt($packet->x, $packet->y, $packet->z);
		if($tile instanceof ItemFrame){
			$ev = new PlayerInteractEvent($this, $this->inventory->getItemInHand(), $tile->getBlock(), null, 5 - $tile->getBlock()->getDamage(), PlayerInteractEvent::LEFT_CLICK_BLOCK);
			if($this->isSpectator() or $this->level->checkSpawnProtection($this, $tile)){
				$ev->setCancelled();
			}

			$ev->call();
			if($ev->isCancelled()){
				$tile->spawnTo($this);
				return true;
			}

			if(lcg_value() <= $tile->getItemDropChance()){
				$this->level->dropItem($tile->getBlock(), $tile->getItem());
			}
			$tile->setItem(null);
			$tile->setItemRotation(0);
		}

		return true;
	}

	public function handleCommandRequest(CommandRequestPacket $packet) : bool{
		if($packet->originData->type !== CommandOriginData::ORIGIN_PLAYER) return false;

		$command = $packet->command;
		if($command[0] != "/") return false;

		$ev = new PlayerCommandPreprocessEvent($this, $command);
		$ev->call();
		if($ev->isCancelled()){
			return true;
		}

		Timings::$playerCommandTimer->startTiming();
		$this->server->dispatchCommand($ev->getPlayer(), substr($ev->getMessage(), 1));
		Timings::$playerCommandTimer->stopTiming();

		return true;
	}

	public function handleResourcePackChunkRequest(ResourcePackChunkRequestPacket $packet) : bool{
		if($this->resourcePacksDone){
			return false;
		}
		$manager = $this->server->getResourcePackManager();
		$pack = $manager->getPackById($packet->packId);
		if(!($pack instanceof ResourcePack)){
			$this->close("", "disconnectionScreen.resourcePack", true);
			$this->server->getLogger()->debug("Got a resource pack chunk request for unknown pack with UUID " . $packet->packId . ", available packs: " . implode(", ", $manager->getPackIdList()));

			return false;
		}

		$pk = new ResourcePackChunkDataPacket();
		$pk->packId = $pack->getPackId();
		$pk->chunkIndex = $packet->chunkIndex;
		$pk->data = $pack->getPackChunk(self::RESOURCE_PACK_CHUNK_SIZE * $packet->chunkIndex, self::RESOURCE_PACK_CHUNK_SIZE);
		$pk->progress = (self::RESOURCE_PACK_CHUNK_SIZE * $packet->chunkIndex);
		$this->dataPacket($pk);
		return true;
	}

	/**
	 * @throws \UnexpectedValueException
	 */
	private function checkBookText(string $string, string $fieldName, int $softLimit, int $hardLimit, bool &$cancel) : string{
		if(strlen($string) > $hardLimit){
			throw new \UnexpectedValueException(sprintf("Book %s must be at most %d bytes, but have %d bytes", $fieldName, $hardLimit, strlen($string)));
		}

		$result = TextFormat::clean($string, false);
		//strlen() is O(1), mb_strlen() is O(n)
		if(strlen($result) > $softLimit * 4 || mb_strlen($result, 'UTF-8') > $softLimit){
			$cancel = true;
			$this->server->getLogger()->debug(sprintf("Cancelled book edit by %s due to %s exceeded soft limit of %d chars", $this->getName(), $fieldName, $softLimit));
		}

		return $result;
	}

	public function handleBookEdit(BookEditPacket $packet) : bool{
		/** @var WritableBook $oldBook */
		$oldBook = $this->inventory->getItem($packet->inventorySlot);
		if($oldBook->getId() !== Item::WRITABLE_BOOK){
			return false;
		}

		$newBook = clone $oldBook;
		$modifiedPages = [];
		$cancel = false;
		switch($packet->type){
			case BookEditPacket::TYPE_REPLACE_PAGE:
				$text = self::checkBookText($packet->text, "page text", 256, 0x7fff, $cancel);
				$newBook->setPageText($packet->pageNumber, $text);
				$modifiedPages[] = $packet->pageNumber;
				break;
			case BookEditPacket::TYPE_ADD_PAGE:
				if(!$newBook->pageExists($packet->pageNumber)){
					//this may only come before a page which already exists
					//TODO: the client can send insert-before actions on trailing client-side pages which cause odd behaviour on the server
					return false;
				}
				$text = self::checkBookText($packet->text, "page text", 256, 0x7fff, $cancel);
				$newBook->insertPage($packet->pageNumber, $text);
				$modifiedPages[] = $packet->pageNumber;
				break;
			case BookEditPacket::TYPE_DELETE_PAGE:
				if(!$newBook->pageExists($packet->pageNumber)){
					return false;
				}
				$newBook->deletePage($packet->pageNumber);
				$modifiedPages[] = $packet->pageNumber;
				break;
			case BookEditPacket::TYPE_SWAP_PAGES:
				if(!$newBook->pageExists($packet->pageNumber) or !$newBook->pageExists($packet->secondaryPageNumber)){
					//the client will create pages on its own without telling us until it tries to switch them
					$newBook->addPage(max($packet->pageNumber, $packet->secondaryPageNumber));
				}
				$newBook->swapPages($packet->pageNumber, $packet->secondaryPageNumber);
				$modifiedPages = [$packet->pageNumber, $packet->secondaryPageNumber];
				break;
			case BookEditPacket::TYPE_SIGN_BOOK:
				$title = self::checkBookText($packet->title, "title", 16, 0x7fff, $cancel);
				//this one doesn't have a limit in vanilla, so we have to improvise
				$author = self::checkBookText($packet->author, "author", 256, 0x7fff, $cancel);

				/** @var WrittenBook $newBook */
				$newBook = Item::get(Item::WRITTEN_BOOK, 0, 1, $newBook->getNamedTag());
				$newBook->setAuthor($author);
				$newBook->setTitle($title);
				$newBook->setGeneration(WrittenBook::GENERATION_ORIGINAL);
				break;
			default:
				return false;
		}

		/*
		 * Plugins may have created books with more than 50 pages; we allow plugins to do this, but not players.
		 * Don't allow the page count to grow past 50, but allow deleting, swapping or altering text of existing pages.
		 */
		$oldPageCount = count($oldBook->getPages());
		$newPageCount = count($newBook->getPages());
		if(($newPageCount > $oldPageCount && $newPageCount > 50)){
			$this->server->getLogger()->debug("Cancelled book edit by " . $this->getName() . " due to adding too many pages (new page count would be $newPageCount)");
			$cancel = true;
		}

		$event = new PlayerEditBookEvent($this, $oldBook, $newBook, $packet->type, $modifiedPages);
		if($cancel){
			$event->setCancelled();
		}

		$event->call();
		if($event->isCancelled()){
			return true;
		}

		$this->getInventory()->setItem($packet->inventorySlot, $event->getNewBook());

		return true;
	}

	/**
	 * Called when a packet is received from the client. This method will call DataPacketReceiveEvent.
	 *
	 * @return void
	 */
	public function handleDataPacket(DataPacket $packet){
		if($this->sessionAdapter !== null){
			$this->sessionAdapter->handleDataPacket($packet);
		}
	}

	/**
	 * Batch a Data packet into the channel list to send at the end of the tick
	 */
	public function batchDataPacket(DataPacket $packet) : bool{
		if(!$this->isConnected()){
			return false;
		}

		$timings = Timings::getSendDataPacketTimings($packet);
		$timings->startTiming();
		$ev = new DataPacketSendEvent($this, $packet);
		$ev->call();
		if($ev->isCancelled()){
			$timings->stopTiming();
			return false;
		}

		$this->batchedPackets[] = clone $packet;
		$timings->stopTiming();
		return true;
	}

	/**
	 * @return bool|int
	 */
	public function sendDataPacket(DataPacket $packet, bool $needACK = false, bool $immediate = false){
		if(!$this->isConnected()){
			return false;
		}

		//Basic safety restriction. TODO: improve this
		if(!$this->loggedIn and !$packet->canBeSentBeforeLogin()){
			throw new \InvalidArgumentException("Attempted to send " . get_class($packet) . " to " . $this->getName() . " too early");
		}

		$timings = Timings::getSendDataPacketTimings($packet);
		$timings->startTiming();
		try{
			$ev = new DataPacketSendEvent($this, $packet);
			$ev->call();
			if($ev->isCancelled()){
				return false;
			}

			$identifier = $this->interface->putPacket($this, $packet, $needACK, $immediate);

			if($needACK and $identifier !== null){
				$this->needACK[$identifier] = false;
				return $identifier;
			}

			return true;
		}finally{
			$timings->stopTiming();
		}
	}

	/**
	 * @internal
	 */
	public function getCipher() : ?EncryptionContext{
        return $this->cipher;
    }

    /**
	 * @return bool|int
	 */
	public function dataPacket(DataPacket $packet, bool $needACK = false){
		return $this->sendDataPacket($packet, $needACK, false);
	}

	/**
	 * @return bool|int
	 */
	public function directDataPacket(DataPacket $packet, bool $needACK = false){
		return $this->sendDataPacket($packet, $needACK, true);
	}

	/**
	 * Transfers a player to another server.
	 *
	 * @param string $address The IP address or hostname of the destination server
	 * @param int    $port The destination port, defaults to 19132
	 * @param string $message Message to show in the console when closing the player
	 *
	 * @return bool if transfer was successful.
	 */
	public function transfer(string $address, int $port = 19132, string $message = "transfer") : bool{
		$ev = new PlayerTransferEvent($this, $address, $port, $message);
		$ev->call();
		if(!$ev->isCancelled()){
			$pk = new TransferPacket();
			$pk->address = $ev->getAddress();
			$pk->port = $ev->getPort();
			$this->sendDataPacket($pk, false, true);

			return true;
		}

		return false;
	}

	/**
	 * Kicks a player from the server
	 *
	 * @param TextContainer|string $quitMessage
	 */
	public function kick(string $reason = "", bool $isAdmin = true, $quitMessage = null) : bool{
		$ev = new PlayerKickEvent($this, $reason, $quitMessage ?? $this->getLeaveMessage());
		$ev->call();
		if(!$ev->isCancelled()){
			$reason = $ev->getReason();
			$message = $reason;
			if($isAdmin){
				if(!$this->isBanned()){
					$message = "Kicked by admin." . ($reason !== "" ? " Reason: " . $reason : "");
				}
			}else{
				if($reason === ""){
					$message = "disconnectionScreen.noReason";
				}
			}
			$this->close($ev->getQuitMessage(), $message);

			return true;
		}

		return false;
	}

	/**
	 * @deprecated
	 * @see Player::sendTitle()
	 *
	 * @param int    $fadeIn Duration in ticks for fade-in. If -1 is given, client-sided defaults will be used.
	 * @param int    $stay Duration in ticks to stay on screen for
	 * @param int    $fadeOut Duration in ticks for fade-out.
	 *
	 * @return void
	 */
	public function addTitle(string $title, string $subtitle = "", int $fadeIn = -1, int $stay = -1, int $fadeOut = -1){
		$this->sendTitle($title, $subtitle, $fadeIn, $stay, $fadeOut);
	}

	/**
	 * Adds a title text to the user's screen, with an optional subtitle.
	 *
	 * @param int    $fadeIn Duration in ticks for fade-in. If -1 is given, client-sided defaults will be used.
	 * @param int    $stay Duration in ticks to stay on screen for
	 * @param int    $fadeOut Duration in ticks for fade-out.
	 */
	public function sendTitle(string $title, string $subtitle = "", int $fadeIn = -1, int $stay = -1, int $fadeOut = -1) : void{
		$this->setTitleDuration($fadeIn, $stay, $fadeOut);
		if($subtitle !== ""){
			$this->sendSubTitle($subtitle);
		}
		$this->sendTitleText($title, SetTitlePacket::TYPE_SET_TITLE);
	}

	/**
	 * @deprecated
	 * @see Player::sendSubTitle()
	 *
	 * @return void
	 */
	public function addSubTitle(string $subtitle){
		$this->sendSubTitle($subtitle);
	}

	/**
	 * Sets the subtitle message, without sending a title.
	 */
	public function sendSubTitle(string $subtitle) : void{
		$this->sendTitleText($subtitle, SetTitlePacket::TYPE_SET_SUBTITLE);
	}

	/**
	 * @deprecated
	 * @see Player::sendActionBarMessage()
	 *
	 * @return void
	 */
	public function addActionBarMessage(string $message){
		$this->sendActionBarMessage($message);
	}

	/**
	 * Adds small text to the user's screen.
	 */
	public function sendActionBarMessage(string $message) : void{
		$this->sendTitleText($message, SetTitlePacket::TYPE_SET_ACTIONBAR_MESSAGE);
	}

	/**
	 * Removes the title from the client's screen.
	 *
	 * @return void
	 */
	public function removeTitles(){
		$pk = new SetTitlePacket();
		$pk->type = SetTitlePacket::TYPE_CLEAR_TITLE;
		$this->dataPacket($pk);
	}

	/**
	 * Resets the title duration settings to defaults and removes any existing titles.
	 *
	 * @return void
	 */
	public function resetTitles(){
		$pk = new SetTitlePacket();
		$pk->type = SetTitlePacket::TYPE_RESET_TITLE;
		$this->dataPacket($pk);
	}

	/**
	 * Sets the title duration.
	 *
	 * @param int $fadeIn Title fade-in time in ticks.
	 * @param int $stay Title stay time in ticks.
	 * @param int $fadeOut Title fade-out time in ticks.
	 *
	 * @return void
	 */
	public function setTitleDuration(int $fadeIn, int $stay, int $fadeOut){
		if($fadeIn >= 0 and $stay >= 0 and $fadeOut >= 0){
			$pk = new SetTitlePacket();
			$pk->type = SetTitlePacket::TYPE_SET_ANIMATION_TIMES;
			$pk->fadeInTime = $fadeIn;
			$pk->stayTime = $stay;
			$pk->fadeOutTime = $fadeOut;
			$this->dataPacket($pk);
		}
	}

	/**
	 * Internal function used for sending titles.
	 *
	 * @return void
	 */
	protected function sendTitleText(string $title, int $type){
		$pk = new SetTitlePacket();
		$pk->type = $type;
		$pk->text = $title;
		$this->dataPacket($pk);
	}

	/**
	 * Sends a direct chat message to a player
	 *
	 * @param TextContainer|string $message
	 *
	 * @return void
	 */
	public function sendMessage($message){
		if($message instanceof TextContainer){
			if($message instanceof TranslationContainer){
				$this->sendTranslation($message->getText(), $message->getParameters());
				return;
			}
			$message = $message->getText();
		}

		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_RAW;
		$pk->message = $this->server->getLanguage()->translateString($message);
		$this->dataPacket($pk);
	}

	/**
	 * @param string[] $parameters
	 *
	 * @return void
	 */
	public function sendTranslation(string $message, array $parameters = []){
		$pk = new TextPacket();
		if(!$this->server->isLanguageForced()){
			$pk->type = TextPacket::TYPE_TRANSLATION;
			$pk->needsTranslation = true;
			$pk->message = $this->server->getLanguage()->translateString($message, $parameters, "pocketmine.");
			foreach($parameters as $i => $p){
				$parameters[$i] = $this->server->getLanguage()->translateString($p, [], "pocketmine.");
			}
			$pk->parameters = $parameters;
		}else{
			$pk->type = TextPacket::TYPE_RAW;
			$pk->message = $this->server->getLanguage()->translateString($message, $parameters);
		}
		$this->dataPacket($pk);
	}

	/**
	 * Sends a popup message to the player
	 *
	 * TODO: add translation type popups
	 *
	 * @param string $subtitle @deprecated
	 *
	 * @return void
	 */
	public function sendPopup(string $message, string $subtitle = ""){
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_POPUP;
		$pk->message = $message;
		$this->dataPacket($pk);
	}

	/**
	 * @return void
	 */
	public function sendTip(string $message){
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_TIP;
		$pk->message = $message;
		$this->dataPacket($pk);
	}

	/**
	 * @return void
	 */
	public function sendWhisper(string $sender, string $message){
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_WHISPER;
		$pk->sourceName = $sender;
		$pk->message = $message;
		$this->dataPacket($pk);
	}
	
	/**
	 * Sends Toast Notification to player.
	 *
	 * @param string $title
	 * @param string $content
	 */
	public function sendToast(string $title, string $content) : void{
		$pk = new ToastRequestPacket();
		$pk->title = $title;
		$pk->content = $content;
		$this->dataPacket($pk);
	}

	/**
	 * Sends a Form to the player, or queue to send it if a form is already open.
	 */
	public function sendForm(Form $form) : void{
		$formData = json_encode($form);
		if($formData === false){
			throw new \InvalidArgumentException("Failed to encode form JSON: " . json_last_error_msg());
		}
		$id = $this->formIdCounter++;
		$pk = new ModalFormRequestPacket();
		$pk->formId = $id;
		$pk->formData = $formData;
		if($this->dataPacket($pk) !== false){
			$this->forms[$id] = $form;
		}
	}

	/**
	 * @param mixed $responseData
	 */
	public function onFormSubmit(int $formId, $responseData) : bool{
		if(!isset($this->forms[$formId])){
			$this->server->getLogger()->debug("Got unexpected response for form $formId");
			return false;
		}

		try{
			$this->forms[$formId]->handleResponse($this, $responseData);
		}catch(FormValidationException $e){
			$this->server->getLogger()->critical("Failed to validate form " . get_class($this->forms[$formId]) . ": " . $e->getMessage());
			$this->server->getLogger()->logException($e);
		}finally{
			unset($this->forms[$formId]);
		}

		return true;
	}

	public function sendServerSettings(ServerSettingsForm $form){
		$id = $this->formIdCounter++;
		$pk = new ServerSettingsResponsePacket();
		$pk->formId = $id;
		$pk->formData = json_encode($form);
		if($this->sendDataPacket($pk)){
			$this->forms[$id] = $form;
		}
	}

	/**
	 * Note for plugin developers: use kick() with the isAdmin
	 * flag set to kick without the "Kicked by admin" part instead of this method.
	 *
	 * @param TextContainer|string $message Message to be broadcasted
	 * @param string               $reason Reason showed in console
	 */
	final public function close($message = "", string $reason = "generic reason", bool $notify = true) : void{
		if($this->isConnected() and !$this->closed){
			if($notify and strlen($reason) > 0){
				$pk = new DisconnectPacket();
				$pk->message = $reason;
				$this->directDataPacket($pk);
			}
			$this->interface->close($this, $notify ? $reason : "");
			$this->sessionAdapter = null;

			PermissionManager::getInstance()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_USERS, $this);
			PermissionManager::getInstance()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);

			$this->stopSleep();

			if($this->spawned){
				$this->doCloseInventory();

				$ev = new PlayerQuitEvent($this, $message, $reason);
				$ev->call();
				if($ev->getQuitMessage() != ""){
					$this->server->broadcastMessage($ev->getQuitMessage());
				}

				$this->save();
			}

			if($this->isValid()){
				foreach($this->usedChunks as $index => $d){
					Level::getXZ($index, $chunkX, $chunkZ);
					$this->level->unregisterChunkLoader($this, $chunkX, $chunkZ);
					foreach($this->level->getChunkEntities($chunkX, $chunkZ) as $entity){
						$entity->despawnFrom($this);
					}
					unset($this->usedChunks[$index]);
				}
			}
			$this->usedChunks = [];
			$this->loadQueue = [];

			if($this->loggedIn){
				$this->server->onPlayerLogout($this);
				foreach($this->server->getOnlinePlayers() as $player){
					if(!$player->canSee($this)){
						$player->showPlayer($this);
					}
				}
				$this->hiddenPlayers = [];
			}

			$this->removeAllWindows(true);
			$this->windows = [];
			$this->windowIndex = [];
			$this->uiInventory = null;
			$this->craftingGrid = null;

			if($this->constructed){
				parent::close();
			}
			$this->spawned = false;

			if($this->loggedIn){
				$this->loggedIn = false;
				$this->server->removeOnlinePlayer($this);
			}

			$this->server->removePlayer($this);

			$this->server->getLogger()->info($this->getServer()->getLanguage()->translateString("pocketmine.player.logOut", [
				TextFormat::AQUA . $this->getName() . TextFormat::WHITE,
				$this->ip,
				$this->port,
				$this->getServer()->getLanguage()->translateString($reason)
			]));

			$this->spawnPosition = null;

			if($this->perm !== null){
				$this->perm->clearPermissions();
				$this->perm = null;
			}
		}
	}

	/**
	 * @return mixed[]
	 */
	public function __debugInfo(){
		return [];
	}

	public function canSaveWithChunk() : bool{
		return false;
	}

	public function setCanSaveWithChunk(bool $value) : void{
		throw new \BadMethodCallException("Players can't be saved with chunks");
	}

	/**
	 * Handles player data saving
	 *
	 * @throws \InvalidStateException if the player is closed
	 *
	 * @return void
	 */
	public function save(){
		if($this->closed){
			throw new \InvalidStateException("Tried to save closed player");
		}

		parent::saveNBT();

		$this->namedtag->setString("LastKnownXUID", $this->xuid);

		if($this->isValid()){
			$this->namedtag->setString("Level", $this->level->getFolderName());
		}

		if($this->hasValidSpawnPosition()){
			$this->namedtag->setString("SpawnLevel", $this->spawnPosition->getLevelNonNull()->getFolderName());
			$this->namedtag->setInt("SpawnX", $this->spawnPosition->getFloorX());
			$this->namedtag->setInt("SpawnY", $this->spawnPosition->getFloorY());
			$this->namedtag->setInt("SpawnZ", $this->spawnPosition->getFloorZ());

			if(!$this->isAlive()){
				//hack for respawn after quit
				$this->namedtag->setTag(new ListTag("Pos", [
					new DoubleTag("", $this->spawnPosition->x),
					new DoubleTag("", $this->spawnPosition->y),
					new DoubleTag("", $this->spawnPosition->z)
				]));
			}
		}

		$achievements = new CompoundTag("Achievements");
		foreach($this->achievements as $achievement => $status){
			$achievements->setByte($achievement, $status ? 1 : 0);
		}
		$this->namedtag->setTag($achievements);

		$this->namedtag->setInt("playerGameType", $this->gamemode);
		$this->namedtag->setLong("lastPlayed", (int) floor(microtime(true) * 1000));

		if($this->username != ""){
			$this->server->saveOfflinePlayerData($this->username, $this->namedtag);
		}
	}

	public function kill() : void{
		if(!$this->spawned){
			return;
		}

		parent::kill();

		$this->sendRespawnPacket($this->getSpawn());
	}

	protected function onDeath() : void{
		//Crafting grid must always be evacuated even if keep-inventory is true. This dumps the contents into the
		//main inventory and drops the rest on the ground.
		$this->doCloseInventory();

		$ev = new PlayerDeathEvent($this, $this->getDrops(), null, $this->getXpDropAmount());
		$ev->setKeepInventory($this->server->keepInventory or $this->level->getGameRules()->getBool(GameRules::RULE_KEEP_INVENTORY));
		$ev->setKeepExperience($this->server->keepExperience);
		$ev->call();

		$this->keepExperience = $ev->getKeepExperience();

		if(!$ev->getKeepInventory()){
			foreach($ev->getDrops() as $item){
				$this->level->dropItem($this, $item);
			}

			if($this->inventory !== null){
				$this->inventory->setHeldItemIndex(0);
				$this->inventory->clearAll();
			}
			if($this->armorInventory !== null){
				$this->armorInventory->clearAll();
			}
			if($this->uiInventory !== null){
				$this->uiInventory->clearAll();
			}
            if($this->offHandInventory !== null){
                $this->offHandInventory->clearAll();
            }
		}

		if(!$ev->getKeepExperience()){
			$this->level->dropExperience($this, $ev->getXpDropAmount());
			$this->setXpAndProgress(0, 0);
		}

		if($ev->getDeathMessage() != ""){
			$this->server->broadcastMessage($ev->getDeathMessage());
		}
	}

	protected function onDeathUpdate(int $tickDiff) : bool{
		parent::onDeathUpdate($tickDiff);
		return false; //never flag players for despawn
	}

	protected function respawn() : void{
		if($this->server->isHardcore()){
			$this->setBanned(true);
			return;
		}

		$this->actuallyRespawn();
	}

	protected function actuallyRespawn() : void{
		$ev = new PlayerRespawnEvent($this, $this->getSpawn());
		$ev->call();

		$realSpawn = Position::fromObject($ev->getRespawnPosition()->add(0.5, 0, 0.5), $ev->getRespawnPosition()->getLevelNonNull());
		$this->teleport($realSpawn);

		$this->setSprinting(false);
		$this->setSneaking(false);

		$this->extinguish();
		$this->setAirSupplyTicks($this->getMaxAirSupplyTicks());
		$this->deadTicks = 0;
		$this->noDamageTicks = 60;

		$this->removeAllEffects();
		$this->setHealth($this->getMaxHealth());

		foreach($this->attributeMap->getAll() as $attr){
			if($this->keepExperience and ($attr->getId() === Attribute::EXPERIENCE or $attr->getId() === Attribute::EXPERIENCE_LEVEL)){
				continue;
			}
			$attr->resetToDefault();
		}

		$this->sendData($this);
		$this->sendData($this->getViewers());
		$this->sendAttributes(true);

		$this->sendSettings();
		$this->sendAllInventories();

		$this->spawnToAll();
		$this->scheduleUpdate();
	}

	protected function applyPostDamageEffects(EntityDamageEvent $source) : void{
		parent::applyPostDamageEffects($source);

		foreach(($item = $this->getInventory()->getItemInHand())->getEnchantments() as $enchantmentInstance){
			$enchantmentInstance->getType()->onHurtEntity($this, $source->getEntity(), $item, $enchantmentInstance->getLevel());
		}

		$this->getInventory()->setItemInHand($item);

		$this->exhaust(0.3, PlayerExhaustEvent::CAUSE_DAMAGE);
	}

	public function attack(EntityDamageEvent $source) : void{
		if(!$this->isAlive()){
			return;
		}

		if($this->isCreative()
			and $source->getCause() !== EntityDamageEvent::CAUSE_SUICIDE
			and $source->getCause() !== EntityDamageEvent::CAUSE_VOID
		){
			$source->setCancelled();
		}elseif($this->allowFlight and $source->getCause() === EntityDamageEvent::CAUSE_FALL){
			$source->setCancelled();
		}

		parent::attack($source);
	}

	public function broadcastEntityEvent(int $eventId, ?int $eventData = null, ?array $players = null) : void{
		if($this->spawned and $players === null){
			$players = $this->getViewers();
			$players[] = $this;
		}
		parent::broadcastEntityEvent($eventId, $eventData, $players);
	}

	public function getOffsetPosition(Vector3 $vector3) : Vector3{
		$result = parent::getOffsetPosition($vector3);
		$result->y += 0.001; //Hack for MCPE falling underground for no good reason (TODO: find out why it's doing this)
		return $result;
	}

	/**
	 * @param Player[]|null $targets
	 *
	 * @return void
	 */
	public function sendPosition(Vector3 $pos, float $yaw = null, float $pitch = null, int $mode = MovePlayerPacket::MODE_NORMAL, array $targets = null){
		$yaw = $yaw ?? $this->yaw;
		$pitch = $pitch ?? $this->pitch;

		$pk = new MovePlayerPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->position = $this->getOffsetPosition($pos);
		$pk->pitch = $pitch;
		$pk->headYaw = $yaw;
		$pk->yaw = $yaw;
		$pk->mode = $mode;
		$pk->onGround = $this->onGround;
		$pk->ridingEid = intval($this->ridingEid);

		if($targets !== null){
			if(in_array($this, $targets, true)){
				$this->forceMoveSync = $pos->asVector3();
				$this->ySize = 0;
			}
			$this->server->broadcastPacket($targets, $pk);
		}else{
			$this->forceMoveSync = $pos->asVector3();
			$this->ySize = 0;
			$this->dataPacket($pk);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function teleport(Vector3 $pos, float $yaw = null, float $pitch = null) : bool{
		if(parent::teleport($pos, $yaw, $pitch)){

			$this->removeAllWindows();

			$this->sendPosition($this, $this->yaw, $this->pitch, MovePlayerPacket::MODE_TELEPORT);
			$this->sendPosition($this, $this->yaw, $this->pitch, MovePlayerPacket::MODE_TELEPORT, $this->getViewers());

			$this->spawnToAll();

			$this->resetFallDistance();
			$this->nextChunkOrderRun = 0;
			if($this->spawnChunkLoadCount !== -1){
				$this->spawnChunkLoadCount = 0;
			}
			$this->stopSleep();

			//TODO: workaround for player last pos not getting updated
			//Entity::updateMovement() normally handles this, but it's overridden with an empty function in Player
			$this->resetLastMovements();

			return true;
		}

		return false;
	}

	/**
	 * @return void
	 */
	protected function addDefaultWindows(){
		$this->addWindow($this->getInventory(), ContainerIds::INVENTORY, true);
		$this->addWindow($this->getOffHandInventory(), ContainerIds::OFFHAND, true);
		$this->addWindow($this->getArmorInventory(), ContainerIds::ARMOR, true);

		$this->cursorInventory = new PlayerCursorInventory($this);
		$this->uiInventory = new PlayerUIInventory($this);
		$this->addWindow($this->uiInventory, ContainerIds::UI, true);

		$this->craftingGrid = new CraftingGrid($this, CraftingGrid::SIZE_SMALL);

		//TODO: more windows
	}

	/**
	 * @deprecated
	 * @return PlayerCursorInventory
	 */
	public function getCursorInventory() : PlayerCursorInventory{
		return $this->cursorInventory;
	}

	public function getUIInventory() : PlayerUIInventory{
		return $this->uiInventory;
	}

	public function getCraftingGrid() : CraftingGrid{
		return $this->craftingGrid;
	}

	public function setCraftingGrid(CraftingGrid $grid) : void{
		$this->craftingGrid = $grid;
	}

	public function doCloseInventory() : void{
		$contents = $this->craftingGrid->getContents();
		if(count($contents) > 0){
			$drops = $this->inventory->addItem(...$contents);
			foreach($drops as $drop){
				$this->dropItem($drop);
			}

			$this->craftingGrid->clearAll();
		}

		if(!$this->uiInventory->isSlotEmpty(UIInventorySlotOffset::CURSOR)){
			if($this->inventory->canAddItem($item = $this->uiInventory->getItem(UIInventorySlotOffset::CURSOR))){
				$this->inventory->addItem($this->uiInventory->getItem(UIInventorySlotOffset::CURSOR));
			}else{
				$this->dropItem($item);
			}
		}

		$this->uiInventory->clearAll();

		if($this->craftingGrid->getGridWidth() > CraftingGrid::SIZE_SMALL){
			$this->craftingGrid = new CraftingGrid($this, CraftingGrid::SIZE_SMALL);
		}
	}

	/**
	 * Returns the window ID which the inventory has for this player, or -1 if the window is not open to the player.
	 */
	public function getWindowId(Inventory $inventory) : int{
		return $this->windows[spl_object_hash($inventory)] ?? ContainerIds::NONE;
	}

	/**
	 * Returns the inventory window open to the player with the specified window ID, or null if no window is open with
	 * that ID.
	 *
	 * @return Inventory|null
	 */
	public function getWindow(int $windowId){
		return $this->windowIndex[$windowId] ?? null;
	}

	/**
	 * @param string $expectedClass
	 *
	 * @return null|Inventory
	 */
	public function findWindow(string $expectedClass) : ?Inventory{
		foreach($this->windowIndex as $window){
			if($window instanceof $expectedClass){
				return $window;
			}
		}

		return null;
	}

	/**
	 * Opens an inventory window to the player. Returns the ID of the created window, or the existing window ID if the
	 * player is already viewing the specified inventory.
	 *
	 * @param int|null  $forceId Forces a special ID for the window
	 * @param bool      $isPermanent Prevents the window being removed if true.
	 *
	 * @throws \InvalidArgumentException if a forceID which is already in use is specified
	 * @throws \InvalidStateException if trying to add a window without forceID when no slots are free
	 */
	public function addWindow(Inventory $inventory, int $forceId = null, bool $isPermanent = false) : int{
		if(($id = $this->getWindowId($inventory)) !== ContainerIds::NONE){
			return $id;
		}

		if($forceId === null){
			$cnt = $this->windowCnt;
			do{
				$cnt = max(ContainerIds::FIRST, ($cnt + 1) % self::RESERVED_WINDOW_ID_RANGE_START);
				if($cnt === $this->windowCnt){ //wraparound, no free slots
					throw new \InvalidStateException("No free window IDs found");
				}
			}while(isset($this->windowIndex[$cnt]));
			$this->windowCnt = $cnt;
		}else{
			$cnt = $forceId;
			if(isset($this->windowIndex[$cnt]) or ($cnt >= self::RESERVED_WINDOW_ID_RANGE_START && $cnt <= self::RESERVED_WINDOW_ID_RANGE_END)){
				throw new \InvalidArgumentException("Requested force ID $forceId already in use");
			}
		}

		$this->windowIndex[$cnt] = $inventory;
		$this->windows[spl_object_hash($inventory)] = $cnt;
		if($inventory->open($this)){
			if($isPermanent){
				$this->permanentWindows[$cnt] = true;
			}
			return $cnt;
		}else{
			$this->removeWindow($inventory);

			return -1;
		}
	}

	/**
	 * Removes an inventory window from the player.
	 *
	 * @param bool      $force Forces removal of permanent windows such as normal inventory, cursor
	 *
	 * @return void
	 * @throws \InvalidArgumentException if trying to remove a fixed inventory window without the `force` parameter as true
	 */
	public function removeWindow(Inventory $inventory, bool $force = false){
		$id = $this->windows[$hash = spl_object_hash($inventory)] ?? null;

		if($id !== null and !$force and isset($this->permanentWindows[$id])){
			throw new \InvalidArgumentException("Cannot remove fixed window $id (" . get_class($inventory) . ") from " . $this->getName());
		}

		if($id !== null){
			(new InventoryCloseEvent($inventory, $this))->call();
			$inventory->close($this);
			unset($this->windows[$hash], $this->windowIndex[$id], $this->permanentWindows[$id]);
		}
	}

	/**
	 * Removes all inventory windows from the player. By default this WILL NOT remove permanent windows.
	 *
	 * @param bool $removePermanentWindows Whether to remove permanent windows.
	 *
	 * @return void
	 */
	public function removeAllWindows(bool $removePermanentWindows = false){
		foreach($this->windowIndex as $id => $window){
			if(!$removePermanentWindows and isset($this->permanentWindows[$id])){
				continue;
			}

			$this->removeWindow($window, $removePermanentWindows);
		}
	}

	/**
	 * @return void
	 */
	protected function sendAllInventories(){
		foreach($this->windowIndex as $id => $inventory){
			$inventory->sendContents($this);
		}
	}

	public function setMetadata(string $metadataKey, MetadataValue $newMetadataValue){
		$this->server->getPlayerMetadata()->setMetadata($this, $metadataKey, $newMetadataValue);
	}

	public function getMetadata(string $metadataKey){
		return $this->server->getPlayerMetadata()->getMetadata($this, $metadataKey);
	}

	public function hasMetadata(string $metadataKey) : bool{
		return $this->server->getPlayerMetadata()->hasMetadata($this, $metadataKey);
	}

	public function removeMetadata(string $metadataKey, Plugin $owningPlugin){
		$this->server->getPlayerMetadata()->removeMetadata($this, $metadataKey, $owningPlugin);
	}

	public function onChunkChanged(Chunk $chunk){
		$hasSent = $this->usedChunks[$hash = Level::chunkHash($chunk->getX(), $chunk->getZ())] ?? false;
		if($hasSent){
			$this->usedChunks[$hash] = false;
			$this->nextChunkOrderRun = 0;
		}
	}

	public function onChunkLoaded(Chunk $chunk){

	}

	public function onChunkPopulated(Chunk $chunk){

	}

	public function onChunkUnloaded(Chunk $chunk){

	}

	public function onBlockChanged(Vector3 $block){

	}

	public function getLoaderId() : int{
		return $this->loaderId;
	}

	public function isLoaderActive() : bool{
		return $this->isConnected();
	}

	public function getDeviceOS(): ?int{
	    return $this->deviceOS;
    }

    public function getDeviceModel(): ?string{
	    return $this->deviceModel;
    }

    public function getDeviceId(): ?string{
	    return $this->deviceId;
    }
}
