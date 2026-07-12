<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\utils\UUID;

final class GatheringJoinInfo{

	public function __construct(
		private UUID $experienceId,
		private string $experienceName,
		private UUID $experienceWorldId,
		private string $experienceWorldName,
		private string $creatorId,
		private UUID $targetId,
		private string $scenarioId,
		private string $serverId,
	){
	}

	public function getExperienceId() : UUID{ return $this->experienceId; }

	public function getExperienceName() : string{ return $this->experienceName; }

	public function getExperienceWorldId() : UUID{ return $this->experienceWorldId; }

	public function getExperienceWorldName() : string{ return $this->experienceWorldName; }

	public function getCreatorId() : string{ return $this->creatorId; }

	public function getTargetId() : UUID{ return $this->targetId; }

	public function getScenarioId() : string{ return $this->scenarioId; }

	public function getServerId() : string{ return $this->serverId; }

	public static function read(NetworkBinaryStream $in) : self{
		$experienceId = $in->getUUID();
		$experienceName = $in->getString();
		$experienceWorldId = $in->getUUID();
		$experienceWorldName = $in->getString();
		$creatorId = $in->getString();
		$targetId = $in->getUUID();
		$scenarioId = $in->getString();
		$serverId = $in->getString();

		return new self(
			$experienceId,
			$experienceName,
			$experienceWorldId,
			$experienceWorldName,
			$creatorId,
			$targetId,
			$scenarioId,
			$serverId,
		);
	}

	public function write(NetworkBinaryStream $out) : void{
		$out->putUUID($this->experienceId);
		$out->putString($this->experienceName);
		$out->putUUID($this->experienceWorldId);
		$out->putString($this->experienceWorldName);
		$out->putString($this->creatorId);
		$out->putUUID($this->targetId);
		$out->putString($this->scenarioId);
		$out->putString($this->serverId);
	}
}