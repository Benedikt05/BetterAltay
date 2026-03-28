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

namespace pocketmine\network\mcpe\protocol\types;

use pocketmine\utils\BinaryStream;

final class SerializableVoxelShape{

	/**
	 * @param list<float> $xCoordinates
	 * @param list<float> $yCoordinates
	 * @param list<float> $zCoordinates
	 */
	public function __construct(
		private SerializableVoxelCells $cells,
		private array $xCoordinates,
		private array $yCoordinates,
		private array $zCoordinates
	){}

	public function getCells() : SerializableVoxelCells{ return $this->cells; }

	/** @return float[] */
	public function getXCoordinates() : array{ return $this->xCoordinates; }

	/** @return float[] */
	public function getYCoordinates() : array{ return $this->yCoordinates; }

	/** @return float[] */
	public function getZCoordinates() : array{ return $this->zCoordinates; }

	public static function read(BinaryStream $in) : self{
		$cells = SerializableVoxelCells::read($in);

		$xCoordinates = [];
		for($i = 0; $i < $in->getUnsignedVarInt(); ++$i){
			$xCoordinates[] = $in->getLFloat();
		}

		$yCoordinates = [];
		for($i = 0; $i < $in->getUnsignedVarInt(); ++$i){
			$yCoordinates[] = $in->getLFloat();
		}

		$zCoordinates = [];
		for($i = 0; $i < $in->getUnsignedVarInt(); ++$i){
			$zCoordinates[] = $in->getLFloat();
		}

		return new self(
			$cells,
			$xCoordinates,
			$yCoordinates,
			$zCoordinates
		);
	}

	public function write(BinaryStream $out) : void{
		$this->cells->write($out);

		$out->putUnsignedVarInt(count($this->xCoordinates));
		foreach($this->xCoordinates as $value){
			$out->putLFloat($value);
		}

		$out->putUnsignedVarInt(count($this->yCoordinates));
		foreach($this->yCoordinates as $value){
			$out->putLFloat($value);
		}

		$out->putUnsignedVarInt(count($this->zCoordinates));
		foreach($this->zCoordinates as $value){
			$out->putLFloat($value);
		}
	}
}