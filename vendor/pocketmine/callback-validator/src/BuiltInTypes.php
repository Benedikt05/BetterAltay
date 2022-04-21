<?php declare(strict_types = 1);

namespace DaveRandom\CallbackValidator;

final class BuiltInTypes
{
    /**
     * Thou shalt not instantiate
     */
    private function __construct() { }

    const STRING   = 'string';
    const INT      = 'int';
    const FLOAT    = 'float';
    const BOOL     = 'bool';
    const ARRAY    = 'array';
    const VOID     = 'void';
    const CALLABLE = 'callable';
    const ITERABLE = 'iterable';
}
