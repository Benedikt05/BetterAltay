<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

use InvalidArgumentException;
use function strlen;

class SkinImage {

    /** @var int */
    private $height;
    /** @var int */
    private $width;
    /** @var string */
    private $data;

    public function __construct(int $height, int $width, $data){
        if($height < 0 or $width < 0){
            $height = 0;
            $width = 0;
            $data = "";
        }
        if(($expected = $height * $width * 4) !== ($actual = strlen($data))){
            $data = "";
        }
        $this->height = $height;
        $this->width = $width;
        $this->data = $data;
    }

    public static function fromLegacy(string $data) : SkinImage{
        switch(strlen($data)){
            case 64 * 32 * 4:
                return new self(32, 64, $data);
            case 64 * 64 * 4:
                return new self(64, 64, $data);
            case 128 * 128 * 4:
                return new self(128, 128, $data);
            case 256 * 128 * 4:
                return new self(128, 256, $data);
            case 256 * 256 * 4:
                return new self(256, 256, $data);
            default:
                return new self(0, 0, "");
        }
    }

    public function getHeight() : int{
        return $this->height;
    }

    public function getWidth() : int{
        return $this->width;
    }

    public function getData() : string{
        return $this->data;
    }
}
