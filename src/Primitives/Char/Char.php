<?php

declare(strict_types=1);

namespace Jsadaa\PhpCoreLibrary\Primitives\Char;

use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;

final readonly class Char
{
    private string $value;

    private function __construct(string $value)
    {
        if (\mb_strlen($value) !== 1) {
            throw new \InvalidArgumentException('Char must be a single character.');
        }

        $this->value = $value;
    }

    public static function of(string $value): self
    {
        return new self($value);
    }

    public static function ofDigit(int|Integer $digit): self
    {
        $digit = $digit instanceof Integer ? $digit->toInt() : $digit;

        return new self(\chr($digit + 48));
    }

    public function isAlphabetic(): bool
    {
        return \ctype_alpha($this->value);
    }

    public function isDigit(): bool
    {
        return \ctype_digit($this->value);
    }

    public function isAlphanumeric(): bool
    {
        return \ctype_alnum($this->value);
    }

    public function isWhitespace(): bool
    {
        return \ctype_space($this->value);
    }

    public function isLowercase(): bool
    {
        return \ctype_lower($this->value);
    }

    public function isUppercase(): bool
    {
        return \ctype_upper($this->value);
    }

    public function isPunctuation(): bool
    {
        return \ctype_punct($this->value);
    }

    public function isControl(): bool
    {
        return \ctype_cntrl($this->value);
    }

    public function isPrintable(): bool
    {
        return \ctype_print($this->value);
    }

    public function isHexadecimal(): bool
    {
        return \ctype_xdigit($this->value);
    }

    public function isAscii(): bool
    {
        return \mb_ord($this->value) < 128;
    }

    public function toUppercase(): self
    {
        return new self(\mb_strtoupper($this->value));
    }

    public function toLowercase(): self
    {
        return new self(\mb_strtolower($this->value));
    }
    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
