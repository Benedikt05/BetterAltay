<?php

namespace pocketmine\level\format\io\leveldb;

use pocketmine\utils\Binary;

final class Key
{
    public const TAG_CHUNK_VERSION = "\x2c";
    public const TAG_SUBCHUNK = "\x2f";
	public const TAG_BIOMES = "\x2b";
	public const TAG_BLOCK_ENTITIES = "\x31";
	public const TAG_ENTITIES = "\x32";
	public const TAG_FINALISATION = "\x36";

    public static function get(int $chunkX, int $chunkZ, int $dimension = 0, string $tag = ""): string {
        $index = Binary::writeLInt($chunkX) . Binary::writeLInt($chunkZ);
        if ($dimension !== 0) {
            $index .= Binary::writeLInt($dimension);
        }

        return $tag !== "" ? $index . $tag : $index;
    }
}