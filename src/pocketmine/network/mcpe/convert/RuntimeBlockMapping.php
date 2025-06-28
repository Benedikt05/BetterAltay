<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\convert;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

/**
 * @internal
 */
final class RuntimeBlockMapping{

	/** @var RuntimeBlockMappingMV[] */
	private static array $mappings = [];

	private static function getMapping(int $protocol) : RuntimeBlockMappingMV{
		if(!isset(self::$mappings[$protocol])){
			self::$mappings[$protocol] = new RuntimeBlockMappingMV($protocol);
		}
		return self::$mappings[$protocol];
	}

	public static function toStaticRuntimeId(int $id, int $meta = 0, int $protocol = ProtocolInfo::CURRENT_PROTOCOL) : int{
		return self::getMapping($protocol)->toStaticRuntimeId($id, $meta);
	}

	public static function fromStaticRuntimeId(int $runtimeId, int $protocol = ProtocolInfo::CURRENT_PROTOCOL) : array{
		return self::getMapping($protocol)->fromStaticRuntimeId($runtimeId);
	}

	/**
	 * @return CompoundTag[]
	 */
	public static function getBedrockKnownStates(int $protocol = ProtocolInfo::CURRENT_PROTOCOL) : array{
		return self::getMapping($protocol)->getBedrockKnownStates();
	}
}
