<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\item\TieredTool;

class NetheriteBlock extends Solid{

	public function __construct(){
		parent::__construct(self::NETHERITE_BLOCK, 0, "Block of Netherite");
	}

	public function getName() : string{
		return "Netherite Block";
	}

	public function getToolType() : int{
		return BlockToolType::TYPE_PICKAXE;
	}

	public function getToolHarvestLevel() : int{
		return TieredTool::TIER_DIAMOND;
	}

	public function getHardness() : float{
		return 50;
	}

	public function getBlastResistance() : float{
		return 6000;
	}
}