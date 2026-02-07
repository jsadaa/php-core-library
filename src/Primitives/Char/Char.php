<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Primitives\Char;

use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;

/**
 * An immutable Unicode character (single codepoint, not a grapheme cluster).
 *
 * Classification methods use IntlChar for full Unicode support.
 *
 * @psalm-immutable
 */
final readonly class Char
{
    private const string UTF8 = 'UTF-8';

    private string $value;

    private function __construct(string $value)
    {
        if (\mb_strlen($value, self::UTF8) !== 1) {
            throw new \InvalidArgumentException('Char must be a single character.');
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Create a Char from a single-character string
     *
     * @psalm-pure
     */
    public static function of(string $value): self
    {
        return new self($value);
    }

    /**
     * Create a Char from a digit (0-9)
     *
     * @param int|Integer $digit A digit between 0 and 9
     * @throws \InvalidArgumentException If the digit is not between 0 and 9
     */
    public static function ofDigit(int | Integer $digit): self
    {
        $digit = $digit instanceof Integer ? $digit->toInt() : $digit;

        if ($digit < 0 || $digit > 9) {
            throw new \InvalidArgumentException("Digit must be between 0 and 9, got $digit.");
        }

        return new self(\chr($digit + 48));
    }

    /**
     * Check if the character is alphabetic (Unicode-aware)
     */
    public function isAlphabetic(): bool
    {
        /** @psalm-suppress ImpureFunctionCall */
        return \IntlChar::isalpha($this->codepoint()) === true;
    }

    /**
     * Check if the character is a digit (Unicode-aware)
     */
    public function isDigit(): bool
    {
        /** @psalm-suppress ImpureFunctionCall */
        return \IntlChar::isdigit($this->codepoint()) === true;
    }

    /**
     * Check if the character is alphanumeric (Unicode-aware)
     */
    public function isAlphanumeric(): bool
    {
        /** @psalm-suppress ImpureFunctionCall */
        return \IntlChar::isalnum($this->codepoint()) === true;
    }

    /**
     * Check if the character is whitespace (Unicode-aware)
     */
    public function isWhitespace(): bool
    {
        /** @psalm-suppress ImpureFunctionCall */
        return \IntlChar::isWhitespace($this->codepoint()) === true;
    }

    /**
     * Check if the character is lowercase (Unicode-aware)
     */
    public function isLowercase(): bool
    {
        /** @psalm-suppress ImpureFunctionCall */
        return \IntlChar::islower($this->codepoint()) === true;
    }

    /**
     * Check if the character is uppercase (Unicode-aware)
     */
    public function isUppercase(): bool
    {
        /** @psalm-suppress ImpureFunctionCall */
        return \IntlChar::isupper($this->codepoint()) === true;
    }

    /**
     * Check if the character is punctuation (Unicode-aware)
     */
    public function isPunctuation(): bool
    {
        /** @psalm-suppress ImpureFunctionCall */
        return \IntlChar::ispunct($this->codepoint()) === true;
    }

    /**
     * Check if the character is a control character (Unicode-aware)
     */
    public function isControl(): bool
    {
        /** @psalm-suppress ImpureFunctionCall */
        return \IntlChar::iscntrl($this->codepoint()) === true;
    }

    /**
     * Check if the character is printable (Unicode-aware)
     */
    public function isPrintable(): bool
    {
        /** @psalm-suppress ImpureFunctionCall */
        return \IntlChar::isprint($this->codepoint()) === true;
    }

    /**
     * Check if the character is a hexadecimal digit (Unicode-aware)
     */
    public function isHexadecimal(): bool
    {
        /** @psalm-suppress ImpureFunctionCall */
        return \IntlChar::isxdigit($this->codepoint()) === true;
    }

    /**
     * Check if the character is ASCII (codepoint < 128)
     */
    public function isAscii(): bool
    {
        return \mb_ord($this->value, self::UTF8) < 128;
    }

    /**
     * Convert the character to uppercase
     */
    public function toUppercase(): self
    {
        return new self(\mb_strtoupper($this->value, self::UTF8));
    }

    /**
     * Convert the character to lowercase
     */
    public function toLowercase(): self
    {
        return new self(\mb_strtolower($this->value, self::UTF8));
    }

    /**
     * Get the raw string value
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Get the Unicode codepoint of this character
     *
     * @psalm-suppress ImpureFunctionCall
     */
    private function codepoint(): int
    {
        /** @var int Constructor validates single character, so ord() cannot return null */
        return \IntlChar::ord($this->value);
    }
}
