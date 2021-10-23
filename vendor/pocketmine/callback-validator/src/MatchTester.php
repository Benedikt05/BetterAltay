<?php declare(strict_types = 1);

namespace DaveRandom\CallbackValidator;

final class MatchTester
{
    /**
     * Thou shalt not instantiate
     */
    private function __construct() { }

    /**
     * Lookup table of all built-in types
     * @var true[]
     */
    private static $builtInTypes = [
        BuiltInTypes::STRING   => true,
        BuiltInTypes::INT      => true,
        BuiltInTypes::FLOAT    => true,
        BuiltInTypes::BOOL     => true,
        BuiltInTypes::ARRAY    => true,
        BuiltInTypes::CALLABLE => true,
        BuiltInTypes::VOID     => true,
        BuiltInTypes::ITERABLE => true,
    ];

    /**
     * Lookup table of scalar types
     * @var true[]
     */
    private static $scalarTypes = [
        BuiltInTypes::STRING => true,
        BuiltInTypes::INT    => true,
        BuiltInTypes::FLOAT  => true,
        BuiltInTypes::BOOL   => true,
    ];

    /**
     * @param string $superTypeName
     * @param string $subTypeName
     * @return bool
     */
    public static function isWeakScalarMatch($superTypeName, $subTypeName)
    {
        // Nothing else satisfies array, callable, void or iterable
        if (!isset(self::$scalarTypes[$superTypeName])) {
            return false;
        }

        // Scalars can all cast to each other
        if (isset(self::$scalarTypes[$subTypeName])) {
            return true;
        }

        // Classes with __toString() satisfy string
        if ($superTypeName === BuiltInTypes::STRING && \method_exists($subTypeName, '__toString')) {
            return true;
        }

        return false;
    }

    /**
     * @param string|null $superTypeName
     * @param bool $superTypeNullable
     * @param string|null $subTypeName
     * @param bool $subTypeNullable
     * @param bool $weak
     * @return bool
     */
    public static function isMatch($superTypeName, $superTypeNullable, $subTypeName, $subTypeNullable, $weak)
    {
        // If the super type is unspecified, anything is a match
        if ($superTypeName === null) {
            return true;
        }

        // If the sub type is unspecified, nothing is a match
        if ($subTypeName === null) {
            return false;
        }

        $superTypeName = (string)$superTypeName;
        $subTypeName = (string)$subTypeName;

        // Sub type cannot be nullable unless the super type is as well
        if ($subTypeNullable && !$superTypeNullable) {
            // nullable void doesn't really make sense but for completeness...
            return $superTypeName === BuiltInTypes::VOID && $subTypeName === BuiltInTypes::VOID;
        }

        // If the string is an exact match it's definitely acceptable
        if ($superTypeName === $subTypeName) {
            return true;
        }

        // Check iterable
        if ($superTypeName === BuiltInTypes::ITERABLE) {
            return $subTypeName === BuiltInTypes::ARRAY
                || $subTypeName === \Traversable::class
                || \is_subclass_of($subTypeName, \Traversable::class);
        }

        // Check callable
        if ($superTypeName === BuiltInTypes::CALLABLE) {
            return $subTypeName === \Closure::class
                || \method_exists($subTypeName, '__invoke')
                || \is_subclass_of($subTypeName, \Closure::class);
        }

        // If the super type is built-in, check whether casting rules can succeed
        if (isset(self::$builtInTypes[$superTypeName])) {
            // Fail immediately in strict mode
            return $weak && self::isWeakScalarMatch($superTypeName, $subTypeName);
        }

        // We now know the super type is not built-in and there's no string match, sub type must not be built-in
        if (isset(self::$builtInTypes[$subTypeName])) {
            return false;
        }

        return \is_subclass_of($subTypeName, $superTypeName);
    }
}
