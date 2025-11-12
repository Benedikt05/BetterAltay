<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use function in_array;

class VanillaProtocolMapping implements ProtocolInfo{

	public static function getBlockStatesProtocol(int $protocol) : int{
		if(in_array($protocol, [self::PROTOCOL_1_21_80, self::PROTOCOL_1_21_90, -2], true)){
			return self::PROTOCOL_1_21_93;
		}

		return $protocol;
	}

	public static function getR12BlockMapProtocol(int $protocol) : int{
		if(in_array($protocol, [self::PROTOCOL_1_21_60, self::PROTOCOL_1_21_70, self::PROTOCOL_1_21_80, self::PROTOCOL_1_21_90, self::PROTOCOL_1_21_100, self::PROTOCOL_1_21_111, self::PROTOCOL_1_21_120, -2], true)){
			return self::PROTOCOL_1_21_120;
		}

		return $protocol;
	}
}