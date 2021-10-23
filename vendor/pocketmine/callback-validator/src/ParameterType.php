<?php declare(strict_types = 1);

namespace DaveRandom\CallbackValidator;

final class ParameterType extends Type
{
    /**
     * Contravariant parameters allow implementors to specify a supertype of that which is specified in the prototype
     */
    const CONTRAVARIANT = 0x01 << 8;

    /**
     * Covariant parameters allow implementors to specify a subtype of that which is specified in the prototype
     * Usually this isn't a good idea, it's not type-safe, do not use unless you understand what you are doing!
     */
    const COVARIANT = 0x02 << 8;

    /**
     * A variadic parameter accepts zero or more arguments of the specified type
     */
    const VARIADIC = 0x04 << 8;

    /**
     * An optional parameter may be omitted at call time
     */
    const OPTIONAL = 0x08 << 8;

    /**
     * The name of the parameter in the prototype
     *
     * @var string
     */
    private $parameterName;

    /**
     * Whether the parameter accepts multiple values
     *
     * @var bool
     */
    public $isVariadic;

    /**
     * Whether the parameter value can be omitted at call time#
     *
     * @var bool
     */
    public $isOptional;

    /**
     * Create a new ParameterType instance from a \ReflectionParameter instance
     *
     * @param \ReflectionParameter $reflection
     * @param int $flags
     * @return ParameterType
     */
    public static function createFromReflectionParameter($reflection, $flags = 0)
    {
        $parameterName = $reflection->getName();

        if ($reflection->isPassedByReference()) {
            $flags |= self::REFERENCE;
        }

        if ($reflection->isVariadic()) {
            $flags |= self::VARIADIC;
        }

        if ($reflection->isOptional()) {
            $flags |= self::OPTIONAL;
        }

        $typeName = null;
        $typeReflection = $reflection->getType();

        if ($typeReflection instanceof \ReflectionNamedType) {
            $typeName = $typeReflection->getName();

            if ($typeReflection->allowsNull()) {
                $flags |= self::NULLABLE;
            }
        } elseif ($typeReflection !== null) {
            throw new \LogicException("Unsupported reflection type " . get_class($typeReflection));
        }

        return new self($parameterName, $typeName, $flags);
    }

    /**
     * @param string $parameterName
     * @param string|null $typeName
     * @param int $flags
     */
    public function __construct($parameterName, $typeName = null, $flags = self::CONTRAVARIANT)
    {
        $flags = (int)$flags;

        parent::__construct($typeName, $flags, ($flags & self::COVARIANT) !== 0, ($flags & self::CONTRAVARIANT) !== 0);

        $this->parameterName = (string)$parameterName;
        $this->isOptional = (bool)($flags & self::OPTIONAL);
        $this->isVariadic = (bool)($flags & self::VARIADIC);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $string = '';

        if ($this->typeName !== null) {
            if ($this->isNullable) {
                $string .= '?';
            }

            $string .= $this->typeName . ' ';
        }

        if ($this->isByReference) {
            $string .= '&';
        }

        if ($this->isVariadic) {
            $string .= '...';
        }

        return $string . '$' . $this->parameterName;
    }
}
