<?php

declare(strict_types=1);

namespace pocketmine\entity;

abstract class ClimateAnimal extends Animal{
	protected int $climateVariant = ClimateVariant::CLIMATE_TEMPERATE;

	public function getClimateVariant() : int{
		return $this->climateVariant;
	}

	public function setClimateVariant(int $variant) : void{
		$this->climateVariant = $variant;
	}

	protected function initEntity() : void{
		parent::initEntity();
		$this->climateVariant = $this->namedtag->getInt("ClimateVariant", ClimateVariant::CLIMATE_TEMPERATE);
	}

	public function saveNBT() : void{
		parent::saveNBT();
		$this->namedtag->setInt("ClimateVariant", $this->climateVariant);
	}
}