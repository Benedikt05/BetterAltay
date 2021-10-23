
#define $this->getChunkEntities(chunkX, chunkZ) (($______chunk = $this->getChunk(chunkX, chunkZ)) !== null ? $______chunk->getEntities() : [])

#define $this->isChunkLoaded(chunkX, chunkZ) (isset($this->chunks[Level::chunkHash(chunkX, chunkZ)]))

#define $this->getBlockIdAt(x, y, z) ($this->getChunk(x >> 4, z >> 4, true)->getBlockId(x & 0x0f, y, z & 0x0f))
#define $this->getBlockDataAt(x, y, z) ($this->getChunk(x >> 4, z >> 4, true)->getBlockData(x & 0x0f, y, z & 0x0f))

#define $this->getBlockLightAt(x, y, z) ($this->getChunk(x >> 4, z >> 4, true)->getBlockLight(x & 0x0f, y, z & 0x0f))
#define $this->getBlockSkyLightAt(x, y, z) ($this->getChunk(x >> 4, z >> 4, true)->getBlockSkyLight(x & 0x0f, y, z & 0x0f))
