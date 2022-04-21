<?php declare(strict_types = 1);

namespace DaveRandom\CallbackValidator;

final class ReturnType extends Type
{
    /**
     * Contravariant return types allow implementors to specify a supertype of that which is specified in the prototype
     * Usually this isn't a good idea, it's not type-safe, do not use unless you understand what you are doing!
     */
    const CONTRAVARIANT = 0x01 << 16;

    /**
     * Covariant return types allow implementors to specify a subtype of that which is specified in the prototype
     */
    const COVARIANT = 0x02 << 16;

    /**
     * @param \ReflectionFunctionAbstract $reflection
     * @param int $flags
     * @return ReturnType
     */
    public static function createFromReflectionFunctionAbstract($reflection, $flags = 0)
    {
        if ($reflection->returnsReference()) {
            $flags |= self::REFERENCE;
        }

        $typeName = null;
        $typeReflection = $reflection->getReturnType();

        if ($typeReflection instanceof \ReflectionNamedType) {
            $typeName = $typeReflection->getName();

            if ($typeReflection->allowsNull()) {
                $flags |= self::NULLABLE;
            }
        } elseif ($typeReflection !== null) {
            throw new \LogicException("Unsupported reflection type " . get_class($typeReflection));
        }

        return new self($typeName, $flags);
    }

    /**
     * @param string|null $typeName
     * @param int $flags
     */
    public function __construct($typeName = null, $flags = self::COVARIANT)
    {
        $flags = (int)$flags;

        parent::__construct($typeName, $flags, ($flags & self::COVARIANT) !== 0, ($flags & self::CONTRAVARIANT) !== 0);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->isNullable && $this->typeName !== null
            ? '?' . $this->typeName
            : (string)$this->typeName;
    }
}
