<?php declare(strict_types = 1);

namespace DaveRandom\CallbackValidator\Test\Php71;

use PHPUnit\Framework\TestCase;

abstract class BasePhp71Test extends TestCase
{
    public function setUp(): void
    {
        if (PHP_VERSION_ID < 70100) {
            $this->markTestSkipped('PHP >= 7.1.0');
        }
    }
}
