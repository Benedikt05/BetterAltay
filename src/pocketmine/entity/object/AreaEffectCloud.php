<?php

namespace pocketmine\entity\object;

use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\item\Potion;
use pocketmine\level\particle\Particle;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ShortTag;

class AreaEffectCloud extends Entity {

	public const NETWORK_ID = self::AREA_EFFECT_CLOUD;
	/** @var string */
	public const
		TAG_POTION_ID = "PotionId",
		TAG_AGE = "Age",
		TAG_RADIUS = "Radius",
		TAG_RADIUS_ON_USE = "RadiusOnUse",
		TAG_RADIUS_PER_TICK = "RadiusPerTick",
		TAG_WAIT_TIME = "WaitTime",
		TAG_TILE_X = "TileX",
		TAG_TILE_Y = "TileY",
		TAG_TILE_Z = "TileZ",
		TAG_DURATION = "Duration",
		TAG_DURATION_ON_USE = "DurationOnUse";
	public $width = 5;
	public $length = 5;
	public $height = 1;
	private $PotionId = 0;
	private $Radius = 3;
	private $RadiusOnUse = -0.5;
	private $RadiusPerTick = -0.005;
	private $WaitTime = 10;
	private $TileX = 0;
	private $TileY = 0;
	private $TileZ = 0;
	private $Duration = 600;
	private $DurationOnUse = 0;
	protected $age = 0;

	public function initEntity(): void{
		parent::initEntity();

		if(!$this->namedtag->hasTag(self::TAG_POTION_ID, ShortTag::class)){
			$this->namedtag->setShort(self::TAG_POTION_ID, $this->PotionId);
		}
		$this->PotionId = $this->namedtag->getShort(self::TAG_POTION_ID);

		if(!$this->namedtag->hasTag(self::TAG_RADIUS, FloatTag::class)){
			$this->namedtag->setFloat(self::TAG_RADIUS, $this->Radius);
		}
		$this->Radius = $this->namedtag->getFloat(self::TAG_RADIUS);

		if(!$this->namedtag->hasTag(self::TAG_RADIUS_ON_USE, FloatTag::class)){
			$this->namedtag->setFloat(self::TAG_RADIUS_ON_USE, $this->RadiusOnUse);
		}
		$this->RadiusOnUse = $this->namedtag->getFloat(self::TAG_RADIUS_ON_USE);

		if(!$this->namedtag->hasTag(self::TAG_RADIUS_PER_TICK, FloatTag::class)){
			$this->namedtag->setFloat(self::TAG_RADIUS_PER_TICK, $this->RadiusPerTick);
		}
		$this->RadiusPerTick = $this->namedtag->getFloat(self::TAG_RADIUS_PER_TICK);

		if(!$this->namedtag->hasTag(self::TAG_WAIT_TIME, IntTag::class)){
			$this->namedtag->setInt(self::TAG_WAIT_TIME, $this->WaitTime);
		}
		$this->WaitTime = $this->namedtag->getInt(self::TAG_WAIT_TIME);

		if(!$this->namedtag->hasTag(self::TAG_TILE_X, IntTag::class)){
			$this->namedtag->setInt(self::TAG_TILE_X, intval(round($this->getX())));
		}
		$this->TileX = $this->namedtag->getInt(self::TAG_TILE_X);

		if(!$this->namedtag->hasTag(self::TAG_TILE_Y, IntTag::class)){
			$this->namedtag->setInt(self::TAG_TILE_Y, intval(round($this->getY())));
		}
		$this->TileY = $this->namedtag->getInt(self::TAG_TILE_Y);

		if(!$this->namedtag->hasTag(self::TAG_TILE_Z, IntTag::class)){
			$this->namedtag->setInt(self::TAG_TILE_Z, intval(round($this->getZ())));
		}
		$this->TileZ = $this->namedtag->getInt(self::TAG_TILE_Z);

		if(!$this->namedtag->hasTag(self::TAG_DURATION, IntTag::class)){
			$this->namedtag->setInt(self::TAG_DURATION, $this->Duration);
		}
		$this->Duration = $this->namedtag->getInt(self::TAG_DURATION);

		if(!$this->namedtag->hasTag(self::TAG_DURATION_ON_USE, IntTag::class)){
			$this->namedtag->setInt(self::TAG_DURATION_ON_USE, $this->DurationOnUse);
		}
		$this->DurationOnUse = $this->namedtag->getInt(self::TAG_DURATION_ON_USE);

		$this->getDataPropertyManager()->setInt(self::DATA_AREA_EFFECT_CLOUD_PARTICLE_ID, Particle::TYPE_MOB_SPELL);//todo
		$this->getDataPropertyManager()->setFloat(self::DATA_AREA_EFFECT_CLOUD_RADIUS, $this->Radius);
		$this->getDataPropertyManager()->setInt(self::DATA_AREA_EFFECT_CLOUD_WAITING, $this->WaitTime);
		$this->getDataPropertyManager()->setFloat(self::DATA_BOUNDING_BOX_HEIGHT, 1);
		$this->getDataPropertyManager()->setFloat(self::DATA_BOUNDING_BOX_WIDTH, $this->Radius * 2);
		$this->getDataPropertyManager()->setByte(self::DATA_POTION_AMBIENT, 1);
	}

	public function entityBaseTick(int $tickDiff = 1): bool{
		if($this->isFlaggedForDespawn()){
			return false;
		}

		$this->timings->startTiming();

		$hasUpdate = parent::entityBaseTick($tickDiff);

		if($this->age > $this->Duration || $this->PotionId == 0 || $this->Radius <= 0){
			$this->flagForDespawn();
			$hasUpdate = true;
		}else{
			$effects = Potion::getPotionEffectsById($this->PotionId);
			if(count($effects) <= 0){
				$this->flagForDespawn();
				$this->timings->stopTiming();

				return true;
			}
			$count = $r = $g = $b = $a = 0;
			foreach($effects as $effect){
				$ecol = $effect->getColor();
				$r += $ecol->getR();
				$g += $ecol->getG();
				$b += $ecol->getB();
				$a += $ecol->getA();
				$count++;
			}

			$r /= $count;
			$g /= $count;
			$b /= $count;
			$a /= $count;

			$this->getDataPropertyManager()->setInt(self::DATA_POTION_COLOR, (($a & 0xff) << 24) | (($r & 0xff) << 16) | (($g & 0xff) << 8) | ($b & 0xff));

			$this->Radius += $this->RadiusPerTick;
			$this->getDataPropertyManager()->setFloat(self::DATA_BOUNDING_BOX_WIDTH, $this->Radius * 2);
			if($this->WaitTime > 0){
				$this->WaitTime--;
				$this->timings->stopTiming();

				return true;
			}
			$bb = new AxisAlignedBB($this->x - $this->Radius, $this->y - 1, $this->z - $this->Radius, $this->x + $this->Radius, $this->y + 1, $this->z + $this->Radius);
			$used = false;
			foreach($this->getLevel()->getCollidingEntities($bb, $this) as $collidingEntity){
				if($collidingEntity instanceof Living && $collidingEntity->distanceSquared($this) <= $this->Radius ** 2){
					$used = true;
					foreach($effects as $eff){
						$collidingEntity->addEffect($eff);
					}
				}
			}
			if($used){
				$this->Duration -= $this->DurationOnUse;
				$this->Radius += $this->RadiusOnUse;
				$this->WaitTime = 10;
			}
		}

		$this->getDataPropertyManager()->setFloat(self::DATA_AREA_EFFECT_CLOUD_RADIUS, $this->Radius);
		$this->getDataPropertyManager()->setInt(self::DATA_AREA_EFFECT_CLOUD_WAITING, $this->WaitTime);

		$this->timings->stopTiming();

		return $hasUpdate;
	}

	public function getName(){
		return "Area Effect Cloud";
	}

	protected function applyGravity(): void{
	}
}
