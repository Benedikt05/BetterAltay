<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use InvalidArgumentException;
use pocketmine\network\mcpe\NetworkSession;
use UnexpectedValueException;

class BookEditPacket extends DataPacket {
    public const NETWORK_ID = ProtocolInfo::BOOK_EDIT_PACKET;

    public const TYPE_REPLACE_PAGE = 0;
    public const TYPE_ADD_PAGE = 1;
    public const TYPE_DELETE_PAGE = 2;
    public const TYPE_SWAP_PAGES = 3;
    public const TYPE_SIGN_BOOK = 4;

    private const MAX_PAGE_TEXT_LENGTH = 256;
    private const MAX_TITLE_LENGTH = 32;
    private const MAX_AUTHOR_LENGTH = 32;
    private const MAX_PAGES = 50;

    public int $type;
    public int $inventorySlot;
    public int $pageNumber;
    public int $secondaryPageNumber = 0;

    public string $text = "";
    public string $photoName = "";

    public string $title = "";
    public string $author = "";
    public string $xuid = "";

    protected function decodePayload() {
        $this->type = $this->getByte();
        $this->inventorySlot = $this->getByte();

        switch ($this->type) {
            case self::TYPE_REPLACE_PAGE:
            case self::TYPE_ADD_PAGE:
                $this->pageNumber = $this->getByte();
                if ($this->pageNumber < 0 || $this->pageNumber >= self::MAX_PAGES) {
                    throw new UnexpectedValueException("Invalid page number: $this->pageNumber");
                }
                $this->text = $this->getString();
                if (strlen($this->text) > self::MAX_PAGE_TEXT_LENGTH) {
                    throw new UnexpectedValueException("Page text too long");
                }
                $this->photoName = $this->getString();
                break;

            case self::TYPE_DELETE_PAGE:
                $this->pageNumber = $this->getByte();
                if ($this->pageNumber < 0 || $this->pageNumber >= self::MAX_PAGES) {
                    throw new UnexpectedValueException("Invalid page number: $this->pageNumber");
                }
                break;

            case self::TYPE_SWAP_PAGES:
                $this->pageNumber = $this->getByte();
                $this->secondaryPageNumber = $this->getByte();
                if ($this->pageNumber < 0 || $this->secondaryPageNumber < 0 ||
                    $this->pageNumber >= self::MAX_PAGES || $this->secondaryPageNumber >= self::MAX_PAGES) {
                    throw new UnexpectedValueException("Invalid swap pages: $this->pageNumber <-> $this->secondaryPageNumber");
                }
                break;

            case self::TYPE_SIGN_BOOK:
                $this->title = $this->getString();
                if (strlen($this->title) > self::MAX_TITLE_LENGTH) {
                    throw new UnexpectedValueException("Title too long");
                }
                $this->author = $this->getString();
                if (strlen($this->author) > self::MAX_AUTHOR_LENGTH) {
                    throw new UnexpectedValueException("Author name too long");
                }
                $this->xuid = $this->getString();
                break;

            default:
                throw new UnexpectedValueException("Unknown book edit type: $this->type");
        }
    }

    protected function encodePayload() {
        $this->putByte($this->type);
        $this->putByte($this->inventorySlot);

        switch ($this->type) {
            case self::TYPE_REPLACE_PAGE:
            case self::TYPE_ADD_PAGE:
                $this->putByte($this->pageNumber);
                $this->putString(substr($this->text, 0, self::MAX_PAGE_TEXT_LENGTH));
                $this->putString($this->photoName);
                break;

            case self::TYPE_DELETE_PAGE:
                $this->putByte($this->pageNumber);
                break;

            case self::TYPE_SWAP_PAGES:
                $this->putByte($this->pageNumber);
                $this->putByte($this->secondaryPageNumber);
                break;

            case self::TYPE_SIGN_BOOK:
                $this->putString(substr($this->title, 0, self::MAX_TITLE_LENGTH));
                $this->putString(substr($this->author, 0, self::MAX_AUTHOR_LENGTH));
                $this->putString($this->xuid);
                break;

            default:
                throw new InvalidArgumentException("Unknown book edit type: $this->type");
        }
    }

    public function handle(NetworkSession $session): bool {
        return $session->handleBookEdit($this);
    }
}
