<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\SerializableVoxelShape;

class VoxelShapesPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::VOXEL_SHAPES_PACKET;
	/**
	 * @var SerializableVoxelShape[]
	 * @phpstan-var list<SerializableVoxelShape>
	 */
	 public array $shapes = [];
	/**
	 * @var int[]
	 * @phpstan-var array<string, int>
	 */
	 public array $names = [];
	 public int $customShapeCount = 0;

	protected function decodePayload() : void{
		$this->shapes = [];
		for($i = 0; $i < $this->getUnsignedVarInt(); ++$i){
			$this->shapes[] = SerializableVoxelShape::read($this);
		}

		$this->names = [];
		for($i = 0; $i < $this->getUnsignedVarInt(); ++$i){
			$name = $this->getString();
			$id = $this->getLShort();
			$this->names[$name] = $id;
		}

		$this->customShapeCount = $this->getLShort();
	}

	protected function encodePayload() : void{
		$this->putUnsignedVarInt(count($this->shapes));
		foreach($this->shapes as $shape){
			$shape->write($this);
		}
		$this->putUnsignedVarInt(count($this->names));
		foreach($this->names as $name => $id){
			$this->putString($name);
			$this->putLShort($id);
		}
		$this->putLShort($this->customShapeCount);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleVoxelShapes($this);
	}
}