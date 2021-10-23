<?php declare(strict_types = 1);

namespace DaveRandom\CallbackValidator;

abstract class Type
{
    /**
     * Weak mode validates types using the same rules are strict_types=0
     */
    const WEAK = 0x01;

    /**
     * Nullable types are either explicitly ?type (PHP>=7.1) or with a default value of null
     */
    const NULLABLE  = 0x02;

    /**
     * Reference types must always match
     */
    const REFERENCE = 0x04;

    /**
     * The name of the type
     *
     * @var string|null
     */
    public $typeName;

    /**
     * Whether the type is nullable
     *
     * @var bool
     */
    public $isNullable;

    /**
     * Whether the type is passed by reference
     *
     * @var bool
     */
    public $isByReference;

    /**
     * Whether the type should be matched in weak mode
     *
     * @var bool
     */
    public $isWeak;

    /**
     * Whether the type allows covariant matches
     *
     * @var bool
     */
    public $allowsCovariance;

    /**
     * Whether the type allows contravariant matches
     *
     * @var bool
     */
    public $allowsContravariance;

    /**
     * @param string|null $typeName
     * @param int $flags
     * @param bool $allowsCovariance
     * @param bool $allowsContravariance
     */
    protected function __construct($typeName, $flags, $allowsCovariance, $allowsContravariance)
    {
        $this->typeName = $typeName !== null
            ? (string)$typeName
            : null;

        $this->isNullable = (bool)($flags & self::NULLABLE);
        $this->isByReference = (bool)($flags & self::REFERENCE);
        $this->isWeak = (bool)($flags & self::WEAK);
        $this->allowsCovariance = (bool)$allowsCovariance;
        $this->allowsContravariance = (bool)$allowsContravariance;
    }

    /**
     * Whether the type will be satisfied by the specified type name, nullability and by-reference combination
     *
     * @param string|null $typeName
     * @param bool $nullable
     * @param bool $byReference
     * @return bool
     */
    public function isSatisfiedBy($typeName, $nullable, $byReference)
    {
        // By-ref must always be the same
        if ($byReference xor $this->isByReference) {
            return false;
        }

        // Candidate is exact match to prototype
        if ($typeName === $this->typeName && $nullable === $this->isNullable) {
            return true;
        }

        // Test for a covariant match
        if ($this->allowsCovariance
            && MatchTester::isMatch($this->typeName, $this->isNullable, $typeName, $nullable, $this->isWeak)) {
            return true;
        }

        // Test for a contravariant match
        if ($this->allowsContravariance
            && MatchTester::isMatch($typeName, $nullable, $this->typeName, $this->isNullable, $this->isWeak)) {
            return true;
        }

        // In weak mode, allow castable scalars as long as nullability matches (invariant)
        return $this->isWeak
            && $nullable === $this->isNullable
	    && $typeName !== null
	    && $this->typeName !== null
	    && MatchTester::isWeakScalarMatch($typeName, $this->typeName);
    }
}
