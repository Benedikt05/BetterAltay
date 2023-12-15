<?php


declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

final class DisconnectFailReason{

	private function __construct(){
		//NOOP
	}

	public const UNKNOWN = 0;
	public const CANT_CONNECT_NO_INTERNET = 1;
	public const NO_PERMISSIONS = 2;
	public const UNRECOVERABLE_ERROR = 3;
	public const THIRD_PARTY_BLOCKED = 4;
	public const THIRD_PARTY_NO_INTERNET = 5;
	public const THIRD_PARTY_BAD_IP = 6;
	public const THIRD_PARTY_NO_SERVER_OR_SERVER_LOCKED = 7;
	public const VERSION_MISMATCH = 8;
	public const SKIN_ISSUE = 9;
	public const INVITE_SESSION_NOT_FOUND = 10;
	public const EDU_LEVEL_SETTINGS_MISSING = 11;
	public const LOCAL_SERVER_NOT_FOUND = 12;
	public const LEGACY_DISCONNECT = 13;
	public const USER_LEAVE_GAME_ATTEMPTED = 14;
	public const PLATFORM_LOCKED_SKINS_ERROR = 15;
	public const REALMS_WORLD_UNASSIGNED = 16;
	public const REALMS_SERVER_CANT_CONNECT = 17;
	public const REALMS_SERVER_HIDDEN = 18;
	public const REALMS_SERVER_DISABLED_BETA = 19;
	public const REALMS_SERVER_DISABLED = 20;
	public const CROSS_PLATFORM_DISALLOWED = 21;
	public const CANT_CONNECT = 22;
	public const SESSION_NOT_FOUND = 23;
	public const CLIENT_SETTINGS_INCOMPATIBLE_WITH_SERVER = 24;
	public const SERVER_FULL = 25;
	public const INVALID_PLATFORM_SKIN = 26;
	public const EDITION_VERSION_MISMATCH = 27;
	public const EDITION_MISMATCH = 28;
	public const LEVEL_NEWER_THAN_EXE_VERSION = 29;
	public const NO_FAIL_OCCURRED = 30;
	public const BANNED_SKIN = 31;
	public const TIMEOUT = 32;
	public const SERVER_NOT_FOUND = 33;
	public const OUTDATED_SERVER = 34;
	public const OUTDATED_CLIENT = 35;
	public const NO_PREMIUM_PLATFORM = 36;
	public const MULTIPLAYER_DISABLED = 37;
	public const NO_WIFI = 38;
	public const WORLD_CORRUPTION = 39;
	public const NO_REASON = 40;
	public const DISCONNECTED = 41;
	public const INVALID_PLAYER = 42;
	public const LOGGED_IN_OTHER_LOCATION = 43;
	public const SERVER_ID_CONFLICT = 44;
	public const NOT_ALLOWED = 45;
	public const NOT_AUTHENTICATED = 46;
	public const INVALID_TENANT = 47;
	public const UNKNOWN_PACKET = 48;
	public const UNEXPECTED_PACKET = 49;
	public const INVALID_COMMAND_REQUEST_PACKET = 50;
	public const HOST_SUSPENDED = 51;
	public const LOGIN_PACKET_NO_REQUEST = 52;
	public const LOGIN_PACKET_NO_CERT = 53;
	public const MISSING_CLIENT = 54;
	public const KICKED = 55;
	public const KICKED_FOR_EXPLOIT = 56;
	public const KICKED_FOR_IDLE = 57;
	public const RESOURCE_PACK_PROBLEM = 58;
	public const INCOMPATIBLE_PACK = 59;
	public const OUT_OF_STORAGE = 60;
	public const INVALID_LEVEL = 61;
	public const DISCONNECT_PACKET_DEPRECATED = 62;
	public const BLOCK_MISMATCH = 63;
	public const INVALID_HEIGHTS = 64;
	public const INVALID_WIDTHS = 65;
	public const CONNECTION_LOST = 66;
	public const ZOMBIE_CONNECTION = 67;
	public const SHUTDOWN = 68;
	public const REASON_NOT_SET = 69;
	public const LOADING_STATE_TIMEOUT = 70;
	public const RESOURCE_PACK_LOADING_FAILED = 71;
	public const SEARCHING_FOR_SESSION_LOADING_SCREEN_FAILED = 72;
	public const CONN_PROTOCOL_VERSION = 73;
	public const SUBSYSTEM_STATUS_ERROR = 74;
	public const EMPTY_AUTH_FROM_DISCOVERY = 75;
	public const EMPTY_URL_FROM_DISCOVERY = 76;
	public const EXPIRED_AUTH_FROM_DISCOVERY = 77;
	public const UNKNOWN_SIGNAL_SERVICE_SIGN_IN_FAILURE = 78;
	public const XBL_JOIN_LOBBY_FAILURE = 79;
	public const UNSPECIFIED_CLIENT_INSTANCE_DISCONNECTION = 80;
	public const CONN_SESSION_NOT_FOUND = 81;
	public const CONN_CREATE_PEER_CONNECTION = 82;
	public const CONN_ICE = 83;
	public const CONN_CONNECT_REQUEST = 84;
	public const CONN_CONNECT_RESPONSE = 85;
	public const CONN_NEGOTIATION_TIMEOUT = 86;
	public const CONN_INACTIVITY_TIMEOUT = 87;
	public const STALE_CONNECTION_BEING_REPLACED = 88;
	public const REALMS_SESSION_NOT_FOUND = 89;
	public const BAD_PACKET = 90;
}
