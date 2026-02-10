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

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use InvalidArgumentException;
use pocketmine\network\mcpe\NetworkSession;
use UnexpectedValueException;

class BookEditPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::BOOK_EDIT_PACKET;

	public const TYPE_REPLACE_PAGE = 0;
	public const TYPE_ADD_PAGE = 1;
	public const TYPE_DELETE_PAGE = 2;
	public const TYPE_SWAP_PAGES = 3;
	public const TYPE_SIGN_BOOK = 4;

	public int $inventorySlot;
	public int $type;
	public int $pageNumber;
	public int $secondaryPageNumber;

	public string $text;
	public string $photoName;

	public string $title;
	public string $author;
	public string $xuid;

	protected function decodePayload() : void{
		if($this->protocol <= ProtocolInfo::P_1_21_130){
			$this->type = $this->getByte();
			$this->inventorySlot = $this->getByte();
		}else{
			$this->inventorySlot = $this->getVarInt();
			$this->type = $this->getUnsignedVarInt();
		}

		switch($this->type){
			case self::TYPE_REPLACE_PAGE:
			case self::TYPE_ADD_PAGE:
				$this->pageNumber = $this->readPageNumber();
				$this->text = $this->getString();
				$this->photoName = $this->getString();
				break;
			case self::TYPE_DELETE_PAGE:
				$this->pageNumber = $this->readPageNumber();
				break;
			case self::TYPE_SWAP_PAGES:
				$this->pageNumber = $this->readPageNumber();
				$this->secondaryPageNumber = $this->readPageNumber();
				break;
			case self::TYPE_SIGN_BOOK:
				$this->title = $this->getString();
				$this->author = $this->getString();
				$this->xuid = $this->getString();
				break;
			default:
				throw new UnexpectedValueException("Unknown book edit type $this->type!");
		}
	}

	protected function encodePayload() : void{
		if($this->protocol <= ProtocolInfo::P_1_21_130){
			$this->putByte($this->type);
			$this->putByte($this->inventorySlot);
		}else{
			$this->putVarInt($this->inventorySlot);
			$this->putUnsignedVarInt($this->type);
		}

		switch($this->type){
			case self::TYPE_REPLACE_PAGE:
			case self::TYPE_ADD_PAGE:
				$this->writePageNumber($this->pageNumber);
				$this->putString($this->text);
				$this->putString($this->photoName);
				break;
			case self::TYPE_DELETE_PAGE:
				$this->writePageNumber($this->pageNumber);
				break;
			case self::TYPE_SWAP_PAGES:
				$this->writePageNumber($this->pageNumber);
				$this->writePageNumber($this->secondaryPageNumber);
				break;
			case self::TYPE_SIGN_BOOK:
				$this->putString($this->title);
				$this->putString($this->author);
				$this->putString($this->xuid);
				break;
			default:
				throw new InvalidArgumentException("Unknown book edit type $this->type!");
		}
	}

	private function writePageNumber(int $page) : void{
		$this->protocol <= ProtocolInfo::P_1_21_130 ? $this->putByte($page) : $this->putVarInt($page);
	}

	private function readPageNumber() : int{
		return $this->protocol <= ProtocolInfo::P_1_21_130 ? $this->getByte() : $this->getVarInt();
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleBookEdit($this);
	}
}
