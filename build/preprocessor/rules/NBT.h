use pocketmine\utils\Binary;

#define $nbt->put(data) ($nbt->buffer .= data)

#define $nbt->getByte() (ord($nbt->get(1)))
#define $nbt->putByte(data) ($nbt->buffer .= chr(data))

#define $this->put(data) ($this->buffer .= data)

#define $this->getByte() (ord($this->get(1)))
#define $this->putByte(data) ($this->buffer .= chr(data))
