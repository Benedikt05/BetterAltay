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

namespace pocketmine\item;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\ProtectionEnchantment;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\IntTag;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use pocketmine\utils\Binary;
use pocketmine\utils\Color;
use function lcg_value;
use function mt_rand;

abstract class Armor extends Durable{

	public const SLOT_HELMET = 0;
	public const SLOT_CHESTPLATE = 1;
	public const SLOT_LEGGINGS = 2;
	public const SLOT_BOOTS = 3;

	public const TIER_LEATHER = 1;
	public const TIER_COPPER = 2;
	public const TIER_IRON = 3;
	public const TIER_CHAIN = 4;
	public const TIER_GOLD = 5;
	public const TIER_DIAMOND = 6;
	public const TIER_NETHERITE = 7;
	public const TIER_OTHER = 8;

	public const TAG_CUSTOM_COLOR = "customColor"; //TAG_Int

	public function getMaxStackSize() : int{
		return 1;
	}

	abstract public function getArmorSlot() : int;

	/**
	 * Returns the dyed colour of this armour piece. This generally only applies to leather armour.
	 */
	public function getCustomColor() : ?Color{
		if($this->getNamedTag()->hasTag(self::TAG_CUSTOM_COLOR, IntTag::class)){
			return Color::fromARGB(Binary::unsignInt($this->getNamedTag()->getInt(self::TAG_CUSTOM_COLOR)));
		}

		return null;
	}

	/**
	 * Sets the dyed colour of this armour piece. This generally only applies to leather armour.
	 */
	public function setCustomColor(Color $color) : void{
		$this->setNamedTagEntry(new IntTag(self::TAG_CUSTOM_COLOR, Binary::signInt($color->toARGB())));
	}

	public function clearCustomColor() : void{
		$this->removeNamedTagEntry(self::TAG_CUSTOM_COLOR);
	}

	/**
	 * Returns the total enchantment protection factor this armour piece offers from all applicable protection
	 * enchantments on the item.
	 */
	public function getEnchantmentProtectionFactor(EntityDamageEvent $event) : int{
		$epf = 0;

		foreach($this->getEnchantments() as $enchantment){
			$type = $enchantment->getType();
			if($type instanceof ProtectionEnchantment and $type->isApplicable($event)){
				$epf += $type->getProtectionFactor($enchantment->getLevel());
			}
		}

		return $epf;
	}

	protected function getUnbreakingDamageReduction(int $amount) : int{
		if(($unbreakingLevel = $this->getEnchantmentLevel(Enchantment::UNBREAKING)) > 0){
			$negated = 0;

			$chance = 1 / ($unbreakingLevel + 1);
			for($i = 0; $i < $amount; ++$i){
				if(mt_rand(1, 100) > 60 and lcg_value() > $chance){ //unbreaking only applies to armor 40% of the time at best
					$negated++;
				}
			}

			return $negated;
		}

		return 0;
	}

	public function onClickAir(Player $player, Vector3 $directionVector) : bool{
		$sound = match ($this->getTier()) {
			self::TIER_LEATHER => LevelSoundEventPacket::SOUND_ARMOR_EQUIP_LEATHER,
			self::TIER_COPPER => LevelSoundEventPacket::SOUND_ARMOR_EQUIP_COPPER,
			self::TIER_CHAIN => LevelSoundEventPacket::SOUND_ARMOR_EQUIP_CHAIN,
			self::TIER_IRON => LevelSoundEventPacket::SOUND_ARMOR_EQUIP_IRON,
			self::TIER_GOLD => LevelSoundEventPacket::SOUND_ARMOR_EQUIP_GOLD,
			self::TIER_DIAMOND => LevelSoundEventPacket::SOUND_ARMOR_EQUIP_DIAMOND,
			self::TIER_NETHERITE => LevelSoundEventPacket::SOUND_ARMOR_EQUIP_NETHERITE,
			default => LevelSoundEventPacket::SOUND_ARMOR_EQUIP_GENERIC,
		};

		$current = $player->getArmorInventory()->getItem($this->getArmorSlot());
		if($current->isNull()){
			$player->getArmorInventory()->setItem($this->getArmorSlot(), $this->pop());
			$player->getLevelNonNull()->broadcastLevelSoundEvent($player, $sound);

			return true;
		}elseif(!$current->equals($this) and $player->getInventory()->canAddItem($current)){
			$player->getArmorInventory()->setItem($this->getArmorSlot(), $this->pop());
			$player->getInventory()->addItem($current);
			$player->getLevelNonNull()->broadcastLevelSoundEvent($player, $sound);

			return true;
		}

		return false;
	}

	public function getTier() : int{
		return self::TIER_OTHER;
	}
}
