<?php

/*
 *               _ _
 *         /\   | | |
 *        /  \  | | |_ __ _ _   _
 *       / /\ \ | | __/ _` | | | |
 *      / ____ \| | || (_| | |_| |
 *     /_/    \_|_|\__\__,_|\__, |
 *                           __/ |
 *                          |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author TuranicTeam
 * @link https://github.com/TuranicTeam/Altay
 *
 */

namespace pocketmine\maps\renderer;

use pocketmine\block\Air;
use pocketmine\block\BlockIds;
use pocketmine\block\Liquid;
use pocketmine\maps\MapData;
use pocketmine\Player;
use pocketmine\utils\Color;
use function floor;
use function max;
use function min;

class VanillaMapRenderer extends MapRenderer{
	protected int $currentCheckX = 0;

	public function initialize(MapData $mapData) : void{

	}

	public function onMapCreated(Player $player, MapData $mapData) : void{
		// TODO: make this async
		//for($i = 0; $i < 128; $i++){
		$this->render($mapData, $player);
		//}
	}

	/**
	 * Renders a map
	 *
	 * @param MapData $mapData
	 * @param Player  $player
	 */
	public function render(MapData $mapData, Player $player) : void{
		if($mapData->getLevelName() === ""){
			$mapData->setLevelName($player->level->getFolderName());
		}

		if($mapData->getLevelName() === $player->level->getFolderName() and $player->level->getDimension() === $mapData->getDimension() and !$mapData->isLocked()){
			$realScale = 1 << $mapData->getScale();

			$centerX = $mapData->getCenterX();
			$centerZ = $mapData->getCenterZ();

			$playerMapX = (int) (floor($player->x - $centerX) / $realScale + 64);
			$playerMapY = (int) (floor($player->z - $centerZ) / $realScale + 64);
			$maxCheckDistance = 128 / $realScale;

			$world = $player->level;
			$air = new Air();

			$avgY = 0;

			for($y = max(0, $playerMapY - $maxCheckDistance + 1); $y < min(128, $playerMapY + $maxCheckDistance); $y++){
				$mapX = $this->currentCheckX;
				$mapY = $y;

				$distX = $mapX - $playerMapX;
				$distY = $mapY - $playerMapY;

				$isTooFar = $distX ** 2 + $distY ** 2 > ($maxCheckDistance - 2) ** 2;

				$worldX = ($centerX / $realScale + $mapX - 64) * $realScale;
				$worldZ = ($centerZ / $realScale + $mapY - 64) * $realScale;

				if($world->isChunkLoaded($worldX >> 4, $worldZ >> 4)){
					$liquidDepth = 0;
					$nextAvgY = 0;

					$chunk = $world->getChunk($worldX >> 4, $worldZ >> 4);
					$worldY = $chunk->getHeightMap($worldX & 15, $worldZ & 15) + 1;
					$block = clone $air;

					if($worldY > 1){
						while(true){
							$worldY--;
							$block = $world->getBlockAt($worldX, $worldY, $worldZ, true, false);

							$mapColor = MapColorTable::getColor($block);

							if(($block->getId() !== BlockIds::AIR and $mapColor->getA() > 0) or $worldY <= 0){
								break;
							}
						}

						if($worldY > 0 and $block instanceof Liquid){
							$attempt = 0;
							$worldY2 = $worldY - 1;

							while($attempt++ <= 10){
								$b = $world->getBlockAt($worldX, $worldY2--, $worldZ, true, false);
								$liquidDepth++;

								if($worldY2 <= 0 or !($b instanceof Liquid)){
									break;
								}
							}
						}

						$nextAvgY += $worldY / (int) ($realScale * $realScale);
					}else{
						$mapColor = new Color(0, 0, 0, 0);
					}

					$liquidDepth /= ($realScale * $realScale);
					$avgYDifference = ($nextAvgY - $avgY) * 4 / (int) ($realScale + 4) + ((int) ($mapX + $mapY & 1) - 0.5) * 0.4;
					$colorDepth = 1;

					if($avgYDifference > 0.6){
						$colorDepth = 2;
					}

					if($avgYDifference < -0.6){
						$colorDepth = 0;
					}

					if($block instanceof Liquid){
						$avgYDifference = (int) $liquidDepth * 0.1 + (int) ($mapX + $mapY & 1) * 0.2;
						$colorDepth = 1;

						if($avgYDifference < 0.5){
							$colorDepth = 2;
						}

						if($avgYDifference > 0.9){
							$colorDepth = 0;
						}
					}

					$avgY = $nextAvgY;

					if(($distX ** 2 + $distY ** 2) < $maxCheckDistance ** 2 and (!$isTooFar or ($mapX + $mapY & 1) !== 0)){
						$oldColor = $mapData->getColorAt($mapX, $mapY);
						$newColor = self::colorizeMapColor($mapColor, $colorDepth);

						if(!$oldColor->equals($newColor)){
							$mapData->setColorAt($mapX, $mapY, $newColor);
							$mapData->updateTextureAt($mapX, $mapY);
						}
					}
				}
			}

			$this->currentCheckX++;
			$this->currentCheckX %= 128;
		}
	}

	/**
	 * @param Color $color
	 * @param int   $colorLevel colorization level
	 *
	 * @return Color
	 */
	public static function colorizeMapColor(Color $color, int $colorLevel) : Color{
		$multiplier = match ($colorLevel) {
			2 => 255,
			0 => 180,
			default => 220,
		};

		$r = (int) ($color->getR() * $multiplier / 255);
		$g = (int) ($color->getG() * $multiplier / 255);
		$b = (int) ($color->getB() * $multiplier / 255);

		$alpha = $color->getA() === 0 ? 0 : 255;

		return new Color($r, $g, $b, $alpha);
	}
}
