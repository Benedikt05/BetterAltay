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

namespace pocketmine\tile;

use InvalidArgumentException;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\level\Level;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\utils\Binary;
use pocketmine\utils\Color;
use pocketmine\utils\TextFormat;
use UnexpectedValueException;
use function array_map;
use function array_pad;
use function array_slice;
use function explode;
use function implode;
use function mb_check_encoding;
use function mb_scrub;
use function sprintf;
use function strlen;

class Sign extends Spawnable{
	public const TAG_TEXT_ROOT = "FrontText";
	public const TAG_TEXT_BLOB = "Text";
	public const TAG_TEXT_LINE = "Text%d"; //sprintf()able
	public const TAG_TEXT_COLOR = "SignTextColor";
	public const TAG_GLOWING_TEXT = "IgnoreLighting";
	public const TAG_LEGACY_BUG_RESOLVE = "TextIgnoreLegacyBugResolved";

	/**
	 * @return string[]
	 */
	private static function fixTextBlob(string $blob) : array{
		return array_slice(array_pad(explode("\n", $blob), 4, ""), 0, 4);
	}

	/** @var string[] */
	protected $text = ["", "", "", ""];

	/** @var int|null */
	protected $editorEntityRuntimeId = null;

	/** @var Color  */
	protected Color $textColor;

	/** @var bool  */
	private bool $glowing;

	public function __construct(Level $level, CompoundTag $nbt, bool $glowing = false){
		parent::__construct($level, $nbt);

		$this->glowing = $glowing;
	}

	protected function readSaveData(CompoundTag $nbt) : void{
		$this->textColor = new Color(0, 0, 0);
		if($nbt->hasTag(self::TAG_TEXT_COLOR)){
			if(($baseColor = $nbt->getTag(self::TAG_TEXT_COLOR)) instanceof IntTag){
				$this->textColor = Color::fromARGB(Binary::unsignInt($baseColor->getValue()));
			}
		}

		if(($glowingTag = $nbt->getTag(self::TAG_GLOWING_TEXT)) instanceof ByteTag &&
			($lightingBugResolvedTag = $nbt->getTag(self::TAG_LEGACY_BUG_RESOLVE)) instanceof ByteTag){
			$glowingText = $glowingTag->getValue() !== 0 && $lightingBugResolvedTag->getValue() !== 0;
			$this->glowing = $glowingText;
		}

		if ($nbt->hasTag(self::TAG_TEXT_ROOT, CompoundTag::class)){ //MCPE 1.19.80 save format
			$this->text = self::fixTextBlob($nbt->getCompoundTag(self::TAG_TEXT_ROOT)->getString(self::TAG_TEXT_BLOB, ""));
		}else if($nbt->hasTag(self::TAG_TEXT_BLOB, StringTag::class)){ //MCPE 1.2 save format
			$this->text = self::fixTextBlob($nbt->getString(self::TAG_TEXT_BLOB));
		}else{
			for($i = 1; $i <= 4; ++$i){
				$textKey = sprintf(self::TAG_TEXT_LINE, $i);
				if($nbt->hasTag($textKey, StringTag::class)){
					$this->text[$i - 1] = $nbt->getString($textKey);
				}
			}
		}

		$this->text = array_map(function(string $line) : string{
			return mb_scrub($line, 'UTF-8');
		}, $this->text);
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$signNbt = new CompoundTag(self::TAG_TEXT_ROOT);
		$signNbt->setString(self::TAG_TEXT_BLOB, implode("\n", $this->text));
		$nbt->setTag($signNbt);
		$nbt->setInt(self::TAG_TEXT_COLOR, Binary::signInt($this->textColor->toARGB()));
		$nbt->setByte(self::TAG_GLOWING_TEXT, $this->glowing ? 1 : 0);
		$nbt->setByte(self::TAG_LEGACY_BUG_RESOLVE, 1);
	}

	/**
	 * Changes contents of the specific lines to the string provided.
	 * Leaves contents of the specific lines as is if null is provided.
	 */
	public function setText(?string $line1 = "", ?string $line2 = "", ?string $line3 = "", ?string $line4 = "") : void{
		if($line1 !== null){
			$this->setLine(0, $line1, false);
		}
		if($line2 !== null){
			$this->setLine(1, $line2, false);
		}
		if($line3 !== null){
			$this->setLine(2, $line3, false);
		}
		if($line4 !== null){
			$this->setLine(3, $line4, false);
		}

		$this->onChanged();
	}

	/**
	 * @param int $index 0-3
	 */
	public function setLine(int $index, string $line, bool $update = true) : void{
		if($index < 0 or $index > 3){
			throw new InvalidArgumentException("Index must be in the range 0-3!");
		}
		if(!mb_check_encoding($line, 'UTF-8')){
			throw new InvalidArgumentException("Text must be valid UTF-8");
		}

		$this->text[$index] = $line;
		if($update){
			$this->onChanged();
		}
	}

	/**
	 * @param int $index 0-3
	 */
	public function getLine(int $index) : string{
		if($index < 0 or $index > 3){
			throw new InvalidArgumentException("Index must be in the range 0-3!");
		}
		return $this->text[$index];
	}

	/**
	 * @return string[]
	 */
	public function getText() : array{
		return $this->text;
	}

	/**
	 * @param bool $glowing
	 */
	public function setGlowing(bool $glowing) : void{
		$this->glowing = $glowing;
	}

	/**
	 * @return bool
	 */
	public function isGlowing() : bool{
		return $this->glowing;
	}

	/**
	 * @param Color $textColor
	 */
	public function setTextColor(Color $textColor) : void{
		$this->textColor = $textColor;
	}

	/**
	 * @return Color
	 */
	public function getTextColor() : Color{
		return $this->textColor;
	}

	/**
	 * Returns the entity runtime ID of the player who placed this sign. Only the player whose entity ID matches this
	 * one may edit the sign text.
	 * This is needed because as of 1.16.220, there is still no reliable way to detect when the MCPE client closed the
	 * sign edit GUI, so we have no way to know when the text is finalized. This limits editing of the text to only the
	 * player who placed it, and only while that player is online.
	 * We can say for sure that the sign is finalized if either of the following occurs:
	 * - The player quits (after rejoin, the player's entity runtimeID will be different).
	 * - The chunk is unloaded (on next load, the entity runtimeID will be null, because it's not saved).
	 */
	public function getEditorEntityRuntimeId() : ?int{ return $this->editorEntityRuntimeId; }

	public function setEditorEntityRuntimeId(?int $editorEntityRuntimeId) : void{
		$this->editorEntityRuntimeId = $editorEntityRuntimeId;
	}

	protected function addAdditionalSpawnData(CompoundTag $nbt) : void{
		$nbt->setString(self::TAG_TEXT_BLOB, implode("\n", $this->text));
	}

	public function updateCompoundTag(CompoundTag $nbt, Player $player) : bool{
		if($nbt->getString("id") !== Tile::SIGN){
			return false;
		}

		if($nbt->hasTag(self::TAG_TEXT_BLOB, StringTag::class)){ //MCPE 1.2 save format
			$lines = self::fixTextBlob($nbt->getString(self::TAG_TEXT_BLOB));
		}else if($nbt->hasTag(self::TAG_TEXT_ROOT, CompoundTag::class)){ //MCPE 1.19.80 save format
			$lines = self::fixTextBlob($nbt->getCompoundTag(self::TAG_TEXT_ROOT)->getString(self::TAG_TEXT_BLOB, ""));
		}else{
			return false;
		}
		$size = 0;
		foreach($lines as $line){
			$size += strlen($line);
		}
		if($size > 1000){
			//trigger kick + IP ban - TODO: on 4.0 this will require a better fix
			throw new UnexpectedValueException($player->getName() . " tried to write $size bytes of text onto a sign (bigger than max 1000)");
		}

		$removeFormat = $player->getRemoveFormat();

		$ev = new SignChangeEvent($this->getBlock(), $player, array_map(function(string $line) use ($removeFormat) : string{ return TextFormat::clean($line, $removeFormat); }, $lines));
		if($this->editorEntityRuntimeId === null || $this->editorEntityRuntimeId !== $player->getId()){
			$ev->setCancelled();
		}
		$ev->call();

		if(!$ev->isCancelled()){
			$this->setText(...$ev->getLines());

			return true;
		}else{
			return false;
		}
	}
}
