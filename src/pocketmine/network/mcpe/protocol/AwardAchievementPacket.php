<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;

class AwardAchievementPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::AWARD_ACHIEVEMENT_PACKET;

	public int $achievementId;

	protected function decodePayload(): void{
		$this->achievementId = $this->getLInt();
	}

	protected function encodePayload(): void{
		$this->putLInt($this->achievementId);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAwardAchievement($this);
	}
}