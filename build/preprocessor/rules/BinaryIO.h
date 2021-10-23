#define $this->put(data) ($this->buffer .= data)

#define $this->putBool(data) ($this->buffer .= Binary::writeBool(data))
#define $this->getBool() (Binary::readBool($this->get(1)))

#define $this->getByte() (ord($this->get(1)))
#define $this->putByte(data) ($this->buffer .= chr(data))

#define $this->getShort() (Binary::readShort($this->get(2)))
#define $this->getSignedShort() (Binary::readSignedShort($this->get(2)))
#define $this->getLShort() (Binary::readLShort($this->get(2)))
#define $this->getSignedLShort() (Binary::readSignedLShort($this->get(2)))
#define $this->putShort(data) ($this->buffer .= Binary::writeShort(data))
#define $this->putLShort(data) ($this->buffer .= Binary::writeLShort(data))

#define $this->getTriad() (Binary::readTriad($this->get(3)))
#define $this->getLTriad() (Binary::readLTriad($this->get(3)))
#define $this->putTriad(data) ($this->buffer .= Binary::writeTriad(data))
#define $this->putLTriad(data) ($this->buffer .= Binary::writeLTriad(data))

#define $this->getInt() (Binary::readInt($this->get(4)))
#define $this->getLInt() (Binary::readLInt($this->get(4)))
#define $this->putInt(data) ($this->buffer .= Binary::writeInt(data))
#define $this->putLInt(data) ($this->buffer .= Binary::writeLInt(data))

#define $this->getLong() (Binary::readLong($this->get(8)))
#define $this->getLLong() (Binary::readLLong($this->get(8)))
#define $this->putLong(data) ($this->buffer .= Binary::writeLong(data))
#define $this->putLLong(data) ($this->buffer .= Binary::writeLLong(data))

#define $this->getFloat() (Binary::readFloat($this->get(4)))
#define $this->getRoundedFloat(accuracy) (Binary::readRoundedFloat($this->get(4), accuracy))
#define $this->getLFloat() (Binary::readLFloat($this->get(4)))
#define $this->getRoundedLFloat(accuracy) (Binary::readRoundedLFloat($this->get(4), accuracy))
#define $this->putFloat(data) ($this->buffer .= Binary::writeFloat(data))
#define $this->putLFloat(data) ($this->buffer .= Binary::writeLFloat(data))
