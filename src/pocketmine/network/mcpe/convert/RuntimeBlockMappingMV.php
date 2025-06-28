<?php


declare(strict_types=1);

namespace pocketmine\network\mcpe\convert;

use pocketmine\block\BlockIds;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\utils\AssumptionFailedError;
use RuntimeException;
use const pocketmine\RESOURCE_PATH;

final class RuntimeBlockMappingMV{

	private array $legacyToRuntimeMap = [];
	private array $runtimeToLegacyMap = [];
	private ?array $bedrockKnownStates = null;

	public function __construct(private int $protocol) {
		$this->init();
	}

	private function init(): void {
		$fileSuffix = ($this->protocol === ProtocolInfo::CURRENT_PROTOCOL || $this->protocol === -2) ? "" : $this->protocol;
		$statesFilename = "canonical_block_states{$fileSuffix}.nbt";
		$statesPath = RESOURCE_PATH . "vanilla/" . $statesFilename;

		$data = file_get_contents($statesPath);
		if ($data === false) {
			throw new AssumptionFailedError("Missing block state file for protocol {$this->protocol}");
		}

		$stream = new NetworkBinaryStream($data);
		$stream->setProtocol($this->protocol);
		$states = [];
		while (!$stream->feof()) {
			$states[] = $stream->getNbtCompoundRoot();
		}
		$this->bedrockKnownStates = $states;

		$this->setupLegacyMappings();
	}

	private function setupLegacyMappings(): void {
		$legacyIdMap = json_decode(file_get_contents(RESOURCE_PATH . "vanilla/block_id_map.json"), true);

		$fileSuffix = ($this->protocol === ProtocolInfo::CURRENT_PROTOCOL || $this->protocol === ProtocolInfo::PROTOCOL_1_21_60 || $this->protocol === -2) ? "" : $this->protocol;
		$legacyMapFilename = "r12_to_current_block_map{$fileSuffix}.bin";
		$legacyMapPath = RESOURCE_PATH . "vanilla/" . $legacyMapFilename;

		$stateMapReader = new NetworkBinaryStream(file_get_contents($legacyMapPath));
		$stateMapReader->setProtocol($this->protocol);
		$nbtReader = new NetworkLittleEndianNBTStream();

		$legacyStateMap = [];
		while (!$stateMapReader->feof()) {
			$id = $stateMapReader->getString();
			$meta = $stateMapReader->getLShort();
			$offset = $stateMapReader->getOffset();
			$state = $nbtReader->read($stateMapReader->getBuffer(), false, $offset);
			$stateMapReader->setOffset($offset);

			if (!($state instanceof CompoundTag)) {
				throw new RuntimeException("Expected CompoundTag for block state");
			}
			$legacyStateMap[] = new R12ToCurrentBlockMapEntry($id, $meta, $state);
		}

		$idToStatesMap = [];
		foreach ($this->bedrockKnownStates as $k => $state) {
			$idToStatesMap[$state->getString("name")][] = $k;
		}

		foreach ($legacyStateMap as $pair) {
			$id = $legacyIdMap[$pair->getId()] ?? null;
			if ($id === null) {
				throw new RuntimeException("No legacy ID matches " . $pair->getId());
			}

			$meta = $pair->getMeta();
			if ($meta > 15) continue;

			$mappedState = $pair->getBlockState();
			$mappedState->setName("");
			$mappedName = $mappedState->getString("name");

			foreach ($idToStatesMap[$mappedName] ?? [] as $k) {
				if ($mappedState->equals($this->bedrockKnownStates[$k])) {
					$this->registerMapping($k, $id, $meta);
					continue 2;
				}
			}

			throw new RuntimeException("Mapped state not found in network states for protocol {$this->protocol}");
		}
	}

	private function registerMapping(int $runtimeId, int $legacyId, int $meta): void {
		$key = ($legacyId << 4) | $meta;
		$this->legacyToRuntimeMap[$key] = $runtimeId;
		$this->runtimeToLegacyMap[$runtimeId] = $key;
	}

	public function toStaticRuntimeId(int $id, int $meta = 0): int {
		return $this->legacyToRuntimeMap[($id << 4) | $meta]
			?? $this->legacyToRuntimeMap[$id << 4]
			?? $this->legacyToRuntimeMap[BlockIds::INFO_UPDATE << 4];
	}

	public function fromStaticRuntimeId(int $runtimeId): array {
		$v = $this->runtimeToLegacyMap[$runtimeId] ?? (BlockIds::INFO_UPDATE << 4);
		return [$v >> 4, $v & 0xf];
	}

	/**
	 * @return CompoundTag[]
	 */
	public function getBedrockKnownStates(): array {
		return $this->bedrockKnownStates ?? [];
	}
}
