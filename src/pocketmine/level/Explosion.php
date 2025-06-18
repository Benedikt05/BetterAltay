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

namespace pocketmine\level;

use InvalidArgumentException;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\TNT;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\format\Chunk;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\tile\Chest;
use pocketmine\tile\Container;
use pocketmine\tile\Tile;
use function ceil;
use function floor;
use function mt_rand;

class Explosion{
	/** @var int */
	private $rays = 16;
	/** @var Level */
	public $level;
	/** @var Position */
	public $source;
	/** @var float */
	public $size;

	/** @var Block[] */
	public $affectedBlocks = [];
	/** @var float */
	public $stepLen = 0.3;
	/** @var Entity|Block|null */
	private $what;

	/** @var SubChunkIteratorManager */
	private $subChunkHandler;

	/**
	 * @param Entity|Block|null $what
	 */
	public function __construct(Position $center, float $size, $what = null){
		if(!$center->isValid()){
			throw new InvalidArgumentException("Position does not have a valid world");
		}
		$this->source = $center;
		$this->level = $center->getLevelNonNull();

		if($size <= 0){
			throw new InvalidArgumentException("Explosion radius must be greater than 0, got $size");
		}
		$this->size = $size;

		$this->what = $what;
		$this->subChunkHandler = new SubChunkIteratorManager($this->level, false);
	}

	/**
	 * Calculates which blocks will be destroyed by this explosion. If explodeB() is called without calling this, no blocks
	 * will be destroyed.
	 */
	public function explodeA() : bool{
		if($this->size < 0.1){
			return false;
		}

		$vector = new Vector3(0, 0, 0);
		$vBlock = new Position(0, 0, 0, $this->level);

		$currentChunk = null;
		$currentSubChunk = null;

		$mRays = $this->rays - 1;
		for($i = 0; $i < $this->rays; ++$i){
			for($j = 0; $j < $this->rays; ++$j){
				for($k = 0; $k < $this->rays; ++$k){
					if($i === 0 or $i === $mRays or $j === 0 or $j === $mRays or $k === 0 or $k === $mRays){
						$vector->setComponents($i / $mRays * 2 - 1, $j / $mRays * 2 - 1, $k / $mRays * 2 - 1);
						$vector->setComponents(($vector->x / ($len = $vector->length())) * $this->stepLen, ($vector->y / $len) * $this->stepLen, ($vector->z / $len) * $this->stepLen);
						$pointerX = $this->source->x;
						$pointerY = $this->source->y;
						$pointerZ = $this->source->z;

						for($blastForce = $this->size * (mt_rand(700, 1300) / 1000); $blastForce > 0; $blastForce -= $this->stepLen * 0.75){
							$x = (int) $pointerX;
							$y = (int) $pointerY;
							$z = (int) $pointerZ;
							$vBlock->x = $pointerX >= $x ? $x : $x - 1;
							$vBlock->y = $pointerY >= $y ? $y : $y - 1;
							$vBlock->z = $pointerZ >= $z ? $z : $z - 1;

							$pointerX += $vector->x;
							$pointerY += $vector->y;
							$pointerZ += $vector->z;

							if(!$this->subChunkHandler->moveTo($vBlock->x, $vBlock->y, $vBlock->z)){
								continue;
							}

							[$blockId, $meta] = RuntimeBlockMapping::fromStaticRuntimeId($this->subChunkHandler->currentSubChunk->getBlockId($vBlock->x & Chunk::COORD_MASK, $vBlock->y & Chunk::COORD_MASK, $vBlock->z & Chunk::COORD_MASK, 0));

							if($blockId !== 0){
								$blastForce -= (BlockFactory::$blastResistance[$blockId] / 5 + 0.3) * $this->stepLen;
								if($blastForce > 0){
									if(!isset($this->affectedBlocks[Level::blockHash($vBlock->x, $vBlock->y, $vBlock->z)])){
										$_block = BlockFactory::get($blockId, $meta, $vBlock);
										foreach($_block->getAffectedBlocks() as $_affectedBlock){
											$this->affectedBlocks[Level::blockHash($_affectedBlock->x, $_affectedBlock->y, $_affectedBlock->z)] = $_affectedBlock;
										}
									}
								}
							}
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 * Executes the explosion's effects on the world. This includes destroying blocks (if any), harming and knocking back entities,
	 * and creating sounds and particles.
	 */
	public function explodeB() : bool{
		$updateBlocks = [];

		$source = (new Vector3($this->source->x, $this->source->y, $this->source->z))->floor();
		$yield = (1 / $this->size) * 100;

		if($this->what instanceof Entity){
			$ev = new EntityExplodeEvent($this->what, $this->source, $this->affectedBlocks, $yield);
			$ev->call();
			if($ev->isCancelled()){
				return false;
			}else{
				$yield = $ev->getYield();
				$this->affectedBlocks = $ev->getBlockList();
			}
		}

		$explosionSize = $this->size * 2;
		$minX = (int) floor($this->source->x - $explosionSize - 1);
		$maxX = (int) ceil($this->source->x + $explosionSize + 1);
		$minY = (int) floor($this->source->y - $explosionSize - 1);
		$maxY = (int) ceil($this->source->y + $explosionSize + 1);
		$minZ = (int) floor($this->source->z - $explosionSize - 1);
		$maxZ = (int) ceil($this->source->z + $explosionSize + 1);

		$explosionBB = new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ);

		$list = $this->level->getNearbyEntities($explosionBB, $this->what instanceof Entity ? $this->what : null);
		foreach($list as $entity){
			$distance = $entity->distance($this->source) / ($explosionSize * 2);

			if($distance <= 1){
				$motion = $entity->subtract($this->source)->normalize();
				$density = $entity->level->getBlockDensity($this->source, $entity->getBoundingBox());
				$impact = (1 - $distance) * $density;

				$damage = (int) ((($impact * $impact + $impact) / 2) * 8 * $explosionSize + 1);

				if($this->what instanceof Entity){
					$ev = new EntityDamageByEntityEvent($this->what, $entity, EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, $damage);
				}elseif($this->what instanceof Block){
					$ev = new EntityDamageByBlockEvent($this->what, $entity, EntityDamageEvent::CAUSE_BLOCK_EXPLOSION, $damage);
				}else{
					$ev = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_BLOCK_EXPLOSION, $damage);
				}

				$entity->attack($ev);
				$entity->setMotion($motion->multiply($impact));
			}
		}

		$air = ItemFactory::get(Item::AIR);

		foreach($this->affectedBlocks as $block){
			$yieldDrops = false;

			if($block instanceof TNT){
				$block->ignite(mt_rand(10, 30));
			}elseif($yieldDrops = (mt_rand(0, 100) < $yield)){
				foreach($block->getDrops($air) as $drop){
					$this->level->dropItem($block->add(0.5, 0.5, 0.5), $drop);
				}
			}

			$this->level->setBlockIdAt($block->x, $block->y, $block->z, RuntimeBlockMapping::AIR());

			$t = $this->level->getTileAt($block->x, $block->y, $block->z);
			if($t instanceof Tile){
				if($t instanceof Chest){
					$t->unpair();
				}
				if($yieldDrops and $t instanceof Container){
					$t->getInventory()->dropContents($this->level, $t->add(0.5, 0.5, 0.5));
				}

				$t->close();
			}
		}

		foreach($this->affectedBlocks as $block){
			$pos = new Vector3($block->x, $block->y, $block->z);

			for($side = 0; $side <= 5; $side++){
				$sideBlock = $pos->getSide($side);
				if(!$this->level->isInWorld($sideBlock->x, $sideBlock->y, $sideBlock->z)){
					continue;
				}
				if(!isset($this->affectedBlocks[$index = Level::blockHash($sideBlock->x, $sideBlock->y, $sideBlock->z)]) and !isset($updateBlocks[$index])){
					$ev = new BlockUpdateEvent($this->level->getBlockAt($sideBlock->x, $sideBlock->y, $sideBlock->z));
					$ev->call();
					if(!$ev->isCancelled()){
						foreach($this->level->getNearbyEntities(new AxisAlignedBB($sideBlock->x - 1, $sideBlock->y - 1, $sideBlock->z - 1, $sideBlock->x + 2, $sideBlock->y + 2, $sideBlock->z + 2)) as $entity){
							$entity->onNearbyBlockChange();
						}
						$ev->getBlock()->onNearbyBlockChange();
					}
					$updateBlocks[$index] = true;
				}
			}
		}

		$this->level->broadcastLevelEvent($source, LevelEventPacket::EVENT_PARTICLE_EXPLOSION, (int) ceil($this->size));
		$this->level->broadcastLevelSoundEvent($source, LevelSoundEventPacket::SOUND_EXPLODE);

		return true;
	}
}
