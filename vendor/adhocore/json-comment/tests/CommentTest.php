<?php

/*
 * This file is part of the PHP-JSON-COMMENT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https://github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace Ahc\Json\Test;

use Ahc\Json\Comment;
use PHPUnit\Framework\TestCase;

class CommentTest extends TestCase
{
    /**
     * @dataProvider theTests
     */
    public function testStrip($json, $expect)
    {
        $this->assertSame($expect, (new Comment)->strip($json));
    }

    /**
     * @dataProvider theTests
     */
    public function testDecode($json)
    {
        $actual = (new Comment)->decode($json, true);

        $this->assertSame(JSON_ERROR_NONE, json_last_error());
        $this->assertNotEmpty($actual);
        $this->assertArrayHasKey('a', $actual);
        $this->assertArrayHasKey('b', $actual);
    }

    public function testDecodeThrows()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('JSON decode failed');

        (new Comment)->decode('{"a":1, /* comment */, "b":}', true);
    }

    public function testParse()
    {
        $parsed = Comment::parse('{
            // comment
            "a//b":"/*value*/"
            /* also comment */
        }', true);

        $this->assertNotEmpty($parsed);
        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('a//b', $parsed);
        $this->assertSame('/*value*/', $parsed['a//b']);
    }

    public function testParseFromFile()
    {
        $parsed = Comment::parseFromFile(__DIR__ . '/composer.json', true);

        $this->assertTrue(is_array($parsed));
        $this->assertSame('adhocore/json-comment', $parsed['name']);
    }

    public function theTests()
    {
        return [
            'without comment' => [
                'json'   => '{"a":1,"b":2}',
                'expect' => '{"a":1,"b":2}',
            ],
            'with trail only' => [
                'json'   => '{"a":1,"b":2,,}',
                'expect' => '{"a":1,"b":2}',
            ],
            'single line comment' => [
                'json'   => '{"a":1,
                // comment
                "b":2,
                // comment
                "c":3,,}',
                'expect' => '{"a":1,
                "b":2,
                "c":3}',
            ],
            'single line comment at end' => [
                'json'   => '{"a":1,
                "b":2,// comment
                "c":[1,2,,]}',
                'expect' => '{"a":1,
                "b":2,
                "c":[1,2]}',
            ],
            'real multiline comment' => [
                'json'   => '{"a":1,
                /*
                 * comment
                 */
                "b":2, "c":3,}',
                'expect' => '{"a":1,
                ' . '
                "b":2, "c":3}',
            ],
            'inline multiline comment' => [
                'json'   => '{"a":1,
                /* comment */ "b":2, "c":3}',
                'expect' => '{"a":1,
                 "b":2, "c":3}',
            ],
            'inline multiline comment at end' => [
                'json'   => '{"a":1, "b":2, "c":3/* comment */,}',
                'expect' => '{"a":1, "b":2, "c":3}',
            ],
            'comment inside string' => [
                'json'   => '{"a": "a//b", "b":"a/* not really comment */b"}',
                'expect' => '{"a": "a//b", "b":"a/* not really comment */b"}',
            ],
            'escaped string' => [
                'json'   => '{"a": "a//b", "b":"a/* \"not really comment\" */b"}',
                'expect' => '{"a": "a//b", "b":"a/* \"not really comment\" */b"}',
            ],
            'string inside comment' => [
                'json'   => '{"a": "ab", /* also comment */ "b":"a/* not a comment */b" /* "comment string" */ }',
                'expect' => '{"a": "ab",  "b":"a/* not a comment */b"  }',
            ],
            'literal lf' => [
                'json'   => '{"a":/*literal linefeed*/"apple' . "\n" . 'ball","b":"","c\\\\":"",}',
                'expect' => '{"a":"apple\nball","b":"","c\\\\":""}',
            ],
        ];
    }
}
