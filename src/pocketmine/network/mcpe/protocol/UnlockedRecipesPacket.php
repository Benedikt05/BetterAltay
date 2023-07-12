<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pocketmine\network\mcpe\NetworkSession;
use function count;

class UnlockedRecipesPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::UNLOCKED_RECIPES_PACKET;

	public const TYPE_EMPTY = 0;
	public const TYPE_INITIALLY_UNLOCKED = 1;
	public const TYPE_NEWLY_UNLOCKED = 2;
	public const TYPE_REMOVE = 3;
	public const TYPE_REMOVE_ALL = 4;

	private int $type;
	/** @var string[] */
	private array $recipes;

	/**
	 * @generate-create-func
	 * @param string[] $recipes
	 */
	public static function create(int $type, array $recipes) : self{
		$result = new self;
		$result->type = $type;
		$result->recipes = $recipes;
		return $result;
	}

	public function getType() : int{ return $this->type; }

	/**
	 * @return string[]
	 */
	public function getRecipes() : array{ return $this->recipes; }

	protected function decodePayload() : void{
		$this->type = $this->getLInt();
		$this->recipes = [];
		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; $i++){
			$this->recipes[] = $this->getString();
		}
	}

	protected function encodePayload() : void{
		$this->putLInt($this->type);
		$this->putUnsignedVarInt(count($this->recipes));
		foreach($this->recipes as $recipe){
			$this->putString($recipe);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleUnlockedRecipes($this);
	}
}