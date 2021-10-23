<?php declare(strict_types = 1);

namespace DaveRandom\CallbackValidator\Test\Fixtures;

class ClassImplementingToString
{
    public function __toString() { return ''; }
}
