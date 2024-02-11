<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

final class CompressionAlgorithm{
	public const ZLIB = 0;
	public const SNAPPY = 1;
	public const NONE = 255;
}