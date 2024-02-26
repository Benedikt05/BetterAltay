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

namespace pocketmine\level\particle;

use pocketmine\block\BlockIds;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\types\entityProperty\EntityProperties;

class FloatingTextParticle extends Particle{
	//TODO: HACK!

	/** @var string */
	protected $text;
	/** @var string */
	protected $title;
	/** @var int|null */
	protected $entityId = null;
	/** @var bool */
	protected $invisible = false;

	public function __construct(Vector3 $pos, string $text, string $title = ""){
		parent::__construct($pos->x, $pos->y, $pos->z);
		$this->text = $text;
		$this->title = $title;
	}

	public function getText() : string{
		return $this->text;
	}

	public function setText(string $text) : void{
		$this->text = $text;
	}

	public function getTitle() : string{
		return $this->title;
	}

	public function setTitle(string $title) : void{
		$this->title = $title;
	}

	public function isInvisible() : bool{
		return $this->invisible;
	}

	public function setInvisible(bool $value = true) : void{
		$this->invisible = $value;
	}

	public function encode(){
		$p = [];

		if($this->entityId === null){
			$this->entityId = Entity::$entityCount++;
		}else{
			$pk0 = new RemoveActorPacket();
			$pk0->entityUniqueId = $this->entityId;

			$p[] = $pk0;
		}

		if(!$this->invisible){

			$name = $this->title . ($this->text !== "" ? "\n" . $this->text : "");

			$actorFlags = (
				1 << Entity::DATA_FLAG_NO_AI
			);
			$actorMetadata = [
				Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $actorFlags],
				Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0.01],
				Entity::DATA_BOUNDING_BOX_WIDTH => [Entity::DATA_TYPE_FLOAT, 0.0],
				Entity::DATA_BOUNDING_BOX_HEIGHT => [Entity::DATA_TYPE_FLOAT, 0.0],
				Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $name],
				Entity::DATA_VARIANT =>[Entity::DATA_TYPE_INT, RuntimeBlockMapping::toStaticRuntimeId(BlockIds::AIR)],
				Entity::DATA_ALWAYS_SHOW_NAMETAG => [Entity::DATA_TYPE_BYTE, 1],
			];

			$pk = new AddActorPacket();
			$pk->entityRuntimeId = $this->entityId;
			$pk->entityUniqueId = $this->entityId;
			$pk->type = AddActorPacket::LEGACY_ID_MAP_BC[EntityIds::FALLING_BLOCK];
			$pk->position = $this->asVector3();
			$pk->metadata = $actorMetadata;
			$pk->entityProperties = new EntityProperties([], []);

			$p[] = $pk;
		}

		return $p;
	}
}
