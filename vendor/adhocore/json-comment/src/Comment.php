<?php

declare(strict_types=1);

/*
 * This file is part of the PHP-JSON-COMMENT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https://github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace Ahc\Json;

/**
 * JSON comment and trailing comma stripper.
 *
 * @author Jitendra Adhikari <jiten.adhikary@gmail.com>
 */
class Comment
{
    /** @var int The current index being scanned */
    protected $index = -1;

    /** @var bool If current char is within a string */
    protected $inStr = false;

    /** @var int Lines of comments 0 = no comment, 1 = single line, 2 = multi lines */
    protected $comment = 0;

    /** @var int Holds the backtace position of a possibly trailing comma */
    protected $commaPos = -1;

    /**
     * Strip comments from JSON string.
     *
     * @param string $json
     *
     * @return string The comment stripped JSON.
     */
    public function strip(string $json): string
    {
        if (!\preg_match('%\/(\/|\*)%', $json) && !\preg_match('/,\s*(\}|\])/', $json)) {
            return $json;
        }

        $this->reset();

        return $this->doStrip($json);
    }

    protected function reset()
    {
        $this->index   = -1;
        $this->inStr   = false;
        $this->comment = 0;
    }

    protected function doStrip(string $json): string
    {
        $return = '';
        $crlf   = ["\n" => '\n', "\r" => '\r'];

        while (isset($json[++$this->index])) {
            list($prev, $char, $next) = $this->getSegments($json);

            $return = $this->checkTrail($char, $return);

            if ($this->inStringOrCommentEnd($prev, $char, $char . $next)) {
                $return .= $this->inStr && isset($crlf[$char]) ? $crlf[$char] : $char;

                continue;
            }

            $wasSingle = 1 === $this->comment;
            if ($this->hasCommentEnded($char, $char . $next) && $wasSingle) {
                $return = \rtrim($return) . $char;
            }

            $this->index += $char . $next === '*/' ? 1 : 0;
        }

        return $return;
    }

    protected function getSegments(string $json): array
    {
        return [
            $json[$this->index - 1] ?? '',
            $json[$this->index],
            $json[$this->index + 1] ?? '',
        ];
    }

    protected function checkTrail(string $char, string $json): string
    {
        if ($char === ',' || $this->commaPos === -1) {
            $this->commaPos = $this->commaPos + ($char === ',' ? 1 : 0);

            return $json;
        }

        if (\ctype_digit($char) || \strpbrk($char, '"tfn{[')) {
            $this->commaPos = -1;
        } elseif ($char === ']' || $char === '}') {
            $pos  = \strlen($json) - $this->commaPos - 1;
            $json = \substr($json, 0, $pos) . \ltrim(\substr($json, $pos), ',');

            $this->commaPos = -1;
        } else {
            $this->commaPos += 1;
        }

        return $json;
    }

    protected function inStringOrCommentEnd(string $prev, string $char, string $next): bool
    {
        return $this->inString($char, $prev, $next) || $this->inCommentEnd($next);
    }

    protected function inString(string $char, string $prev, string $next): bool
    {
        if (0 === $this->comment && $char === '"' && $prev !== '\\') {
            return $this->inStr = !$this->inStr;
        }

        if ($this->inStr && \in_array($next, ['":', '",', '"]', '"}'], true)) {
            $this->inStr = false;
        }

        return $this->inStr;
    }

    protected function inCommentEnd(string $next): bool
    {
        if (!$this->inStr && 0 === $this->comment) {
            $this->comment = $next === '//' ? 1 : ($next === '/*' ? 2 : 0);
        }

        return 0 === $this->comment;
    }

    protected function hasCommentEnded(string $char, string $next): bool
    {
        $singleEnded = $this->comment === 1 && $char == "\n";
        $multiEnded  = $this->comment === 2 && $next == '*/';

        if ($singleEnded || $multiEnded) {
            $this->comment = 0;

            return true;
        }

        return false;
    }

    /**
     * Strip comments and decode JSON string.
     *
     * @param string $json
     * @param bool   $assoc
     * @param int    $depth
     * @param int    $options
     *
     * @see http://php.net/json_decode [JSON decode native function]
     *
     * @throws \RuntimeException When decode fails.
     *
     * @return mixed
     */
    public function decode(string $json, bool $assoc = false, int $depth = 512, int $options = 0)
    {
        $decoded = \json_decode($this->strip($json), $assoc, $depth, $options);

        if (\JSON_ERROR_NONE !== $err = \json_last_error()) {
            $msg = 'JSON decode failed';

            if (\function_exists('json_last_error_msg')) {
                $msg .= ': ' . \json_last_error_msg();
            }

            throw new \RuntimeException($msg, $err);
        }

        return $decoded;
    }

    /**
     * Static alias of decode().
     */
    public static function parse(string $json, bool $assoc = false, int $depth = 512, int $options = 0)
    {
        static $parser;

        if (!$parser) {
            $parser = new static;
        }

        return $parser->decode($json, $assoc, $depth, $options);
    }

    public static function parseFromFile(string $file, bool $assoc = false, int $depth = 512, int $options = 0)
    {
        $json = \file_get_contents($file);

        return static::parse(\trim($json), $assoc, $depth, $options);
    }
}
