<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Primitives\Str;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use Jsadaa\PhpCoreLibrary\Modules\Option\Option;
use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Primitives\Char\Char;
use Jsadaa\PhpCoreLibrary\Primitives\Double\Double;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Error\EncodingError;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Error\InvalidNormalizationForm;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Error\InvalidSourceCharacters;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Error\InvalidUTF8Sequences;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Error\NormalizationError;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Error\ParseError;

/**
 * A UTF-8 oriented string type with immutable operations.
 * Provides methods for handling UTF-8 encoded strings safely.
 *
 * @psalm-immutable
 */
final readonly class Str implements \Stringable
{
    /**
     * @var string The UTF-8 encoding constant
     */
    private const string UTF8 = 'UTF-8';
    private string $value;

    /**
     * Creates a new Str instance
     *
     * @param string $value The encoded string value
     */
    private function __construct(string $value)
    {
        $this->value = $value;
    }

    #[\Override]
    /**
     * Returns a string representation when the object is used as a string
     *
     * @return string The raw UTF-8 string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Creates a new Str instance from a string value
     *
     * This does not try to force the string to UTF-8 encoding, see Str::forceUtf8() for details.
     * This does not apply Unicode normalization by default. See Str::normalize() for details.
     *
     * @param string $value The string value
     * @return self A new Str instance
     * @psalm-pure
     */
    public static function of(
        string $value,
    ): self {
        return new self($value);
    }

    /**
     * Creates a new empty Str instance
     *
     * @psalm-pure
     */
    public static function new(): self
    {
        return new self('');
    }

    /**
     * Returns a new empty Str instance
     *
     */
    public function clear(): self
    {
        return self::new();
    }

    /**
     * Returns the number of Unicode characters in the string
     *
     * @return Integer The number of characters
     */
    public function size(): Integer
    {
        return Integer::of(\mb_strlen($this->value, self::UTF8));
    }

    /**
     * Returns the length of the string in bytes
     *
     * @return Integer The number of bytes
     */
    public function byteSize(): Integer
    {
        return Integer::of(\strlen($this->value));
    }

    /**
     * Inserts a Str at the specified character position
     *
     * @param int|Integer $offset The character position to insert at
     * @param self $value The string to insert
     * @return self The new string with the inserted value
     */
    public function insertAt(
        int | Integer $offset,
        self $value,
    ): self {
        $offset = $offset instanceof Integer ? $offset->toInt() : $offset;

        // Split the original string at the offset position
        $before = \mb_substr($this->value, 0, $offset, self::UTF8);
        $after = \mb_substr($this->value, $offset, null, self::UTF8);

        // Create the new string by concatenating the parts with the inserted value in between
        $newString = $before . $value->toString() . $after;

        return new self($newString);
    }

    /**
     * Append the content of another Str instance to the end of this string
     *
     * @param self|string $other The string to append
     * @return self The new Str instance with the appended string
     */
    public function append(self | string $other): self
    {
        $other = $other instanceof self ? $other->value : $other;
        $newValue = $this->value . $other;

        return new self($newValue);
    }

    /**
     * Prepend the content of another Str instance to the beginning of this string
     *
     * @param self|string $other The string to prepend
     * @return self The new Str instance with the prepended string
     */
    public function prepend(self | string $other): self
    {
        $other = $other instanceof self ? $other->value : $other;
        $newValue = $other . $this->value;

        return new self($newValue);
    }

    /**
     * Checks if the string is empty
     *
     * @return bool True if the string is empty, false otherwise
     */
    public function isEmpty(): bool
    {
        return $this->value === '';
    }

    /**
     * Removes the character at the specified index
     *
     * @param int|Integer $index The character index to remove
     * @return self A new Str instance with the character removed
     */
    public function removeAt(int | Integer $index): self
    {
        $index = $index instanceof Integer ? $index->toInt() : $index;

        $before = \mb_substr($this->value, 0, $index, self::UTF8);
        $after = \mb_substr($this->value, $index + 1, null, self::UTF8);
        $newValue = $before . $after;

        return new self($newValue);
    }

    /**
     * Removes all characters matching the regex pattern
     *
     * @param string|Str $pattern The regex pattern to match
     * @return self A new Str instance with matches removed (unchanged if no matches)
     */
    public function removeMatches(string | self $pattern): self
    {
        $pattern = $pattern instanceof self ? $pattern->toString() : $pattern;

        if ($pattern === '') {
            return new self($this->value);
        }

        // Ensure the pattern has the 'u' modifier for UTF-8 support
        if (!\str_ends_with($pattern, 'u')) {
            $pattern .= 'u';
        }

        $newValue = \preg_replace($pattern, '', $this->value);

        if ($newValue === null) {
            return new self($this->value);
        }

        return new self($newValue);
    }

    /**
     * Truncates the string to the specified length
     *
     * @param int|Integer $length The maximum length in characters
     * @return self A new Str instance with the string truncated
     */
    public function truncate(int | Integer $length): self
    {
        $length = $length instanceof Integer ? $length : Integer::of($length);

        if ($length->le(0)) {
            return self::new();
        }

        if ($length->ge($this->chars()->size())) {
            return new self($this->value);
        }

        return new self(\mb_substr($this->value, 0, $length->toInt(), self::UTF8));
    }

    /**
     * Replaces all occurrences of a substring with another substring
     *
     * @param string|self $search The substring to search for
     * @param string|self $replace The substring to replace with
     * @param bool $useRegex Set to true to interpret $search as a regular expression
     * @return self The new string with replacements made
     */
    public function replace(string | self $search, string | self $replace, bool $useRegex = false): self
    {
        $search = $search instanceof self ? $search : self::of($search);
        $replace = $replace instanceof self ? $replace : self::of($replace);

        // If the search string is empty, return the string as is
        if ($search->isEmpty()) {
            return new self($this->value);
        }

        $search = $search->toString();

        /** @var non-empty-string $search */
        if ($useRegex) {
            // Ensure the pattern has the 'u' modifier for UTF-8 support
            if (!\str_ends_with($search, 'u')) {
                $search .= 'u';
            }

            // Add delimiters if they're not already there
            if (!\str_starts_with($search, '/')) {
                $search = '/' . $search . '/';

                if (!\str_ends_with($search, 'u')) {
                    $search .= 'u';
                }
            }

            $result = \preg_replace($search, $replace->toString(), $this->value);

            // If preg_replace fails or returns null, return the original string
            if ($result === null) {
                return new self($this->value);
            }

            return new self($result);
        }

        return new self(\str_replace($search, $replace->toString(), $this->value));
    }

    /**
     * Converts the string to a Sequence of bytes (integers representing byte values)
     *
     * @return Sequence<Integer> A Sequence containing the byte values of the string
     */
    public function bytes(): Sequence
    {
        // If string is empty, return empty Sequence
        if ($this->isEmpty()) {
            /** @var Sequence<Integer> */
            return Sequence::new();
        }

        $unpacked = \unpack('C*', $this->value);

        if ($unpacked === false) {
            /** @var Sequence<Integer> */
            return Sequence::new();
        }

        /** @var Sequence<Integer> */
        return Sequence::ofArray(\array_values($unpacked))->map(static fn(int $byte) => Integer::of($byte));
    }

    /**
     * Converts the string to a Sequence of individual characters
     *
     * This method cuts the string by code points, not graphemes.
     * Depending on the normalization form, it may not always produce the same length with multibyte characters.
     *
     * @return Sequence<Char> A Sequence containing the individual characters of the string
     */
    public function chars(): Sequence
    {
        if ($this->isEmpty()) {
            /** @var Sequence<Char> */
            return Sequence::new();
        }

        // Use mb_str_split with a length of 1 to split into individual characters
        // mb_str_split always returns an array in PHP 7.4+ for valid UTF-8
        $chars = \mb_str_split($this->value, 1, self::UTF8);

        /** @var Sequence<Char> */
        return Sequence::ofArray($chars)->map(static fn(string $char) => Char::of($char));
    }

    /**
     * Splits the string into an array of substrings using the given delimiter
     *
     * @param string|Str $delimiter The delimiter to use for splitting the string
     * @return Sequence<Str> A Sequence containing the substrings resulting from the split
     */
    public function split(string | self $delimiter): Sequence
    {
        $delimiter = $delimiter instanceof self ? $delimiter->toString() : $delimiter;

        if ($this->isEmpty()) {
            /** @var Sequence<Str> */
            return Sequence::new();
        }

        // For simple string delimiter, use explode which is more efficient
        if (
            \strlen($delimiter) === \mb_strlen($delimiter, self::UTF8) &&
            $delimiter !== ''
        ) {
            $parts = \explode($delimiter, $this->value);
        } elseif ($delimiter === '') {
            // For empty delimiter, split into characters
            // mb_str_split always returns an array for valid UTF-8 strings in PHP 7.4+
            $parts = \mb_str_split($this->value, 1, self::UTF8);
        } else {
            // For complex UTF-8 delimiters, ensure we have proper regex with UTF-8 support
            $pattern = \preg_quote($delimiter, '/');
            $pattern = '/' . $pattern . '/u';

            $result = \preg_split($pattern, $this->value);
            $parts = $result !== false ? $result : [];
        }

        /** @var Sequence<Str> */
        return Sequence::ofArray($parts)->map(
            static fn(string $part) => Str::of($part),
        );
    }

    /**
     * Splits the string into two substrings at the given index
     *
     * @param int|Integer $index The index at which to split the string
     * @return Sequence<Str> A Sequence containing two substrings (before and after the index),
     */
    public function splitAt(int | Integer $index): Sequence
    {
        $index = $index instanceof Integer ? $index->toInt() : $index;

        if ($this->isEmpty()) {
            return Sequence::new();
        }

        $before = \mb_substr($this->value, 0, $index, self::UTF8);
        $after = \mb_substr($this->value, $index, null, self::UTF8);

        return Sequence::ofArray([
            new self($before),
            new self($after),
        ]);
    }

    /**
     * Splits the string into an array of substrings at whitespace characters
     *
     * @return Sequence<Str> A Sequence containing the substrings resulting from the split
     */
    public function splitWhitespace(): Sequence
    {
        if ($this->isEmpty()) {
            /** @var Sequence<Str> */
            return Sequence::new();
        }

        $parts = \preg_split("/[\p{Z}\s]+/u", $this->value);
        $parts = \is_array($parts) ? $parts : [];

        /** @var Sequence<Str> */
        return Sequence::ofArray($parts)->map(
            static fn(string $part) => Str::of($part),
        );
    }

    /**
     * Pads the string to the specified length with the given pad string at the start
     *
     * @param int<0, max>|Integer $targetLength The target length after padding
     * @param string|Str $padString The string to use for padding (default: space)
     * @return self A new Str instance with padding applied
     */
    public function padStart(int | Integer $targetLength, string | self $padString): self
    {
        $targetLength = $targetLength instanceof Integer ? $targetLength : Integer::of($targetLength);
        $targetLength = $targetLength->max(0);
        $padString = $padString instanceof self ? $padString->toString() : $padString;

        $currentLength = $this->chars()->size();

        // If target length is less than or equal to current length, return as is
        if ($targetLength->le($currentLength) || $padString === '') {
            return new self($this->value);
        }

        // Calculate padding length needed
        $paddingLength = $targetLength->sub($currentLength);

        // For multibyte characters, we need to calculate how many repetitions we need
        $padCharLen = \mb_strlen($padString, self::UTF8);
        $repetitions = \ceil($paddingLength->toInt() / $padCharLen);
        $paddingText = \mb_substr(\str_repeat($padString, (int) $repetitions), 0, $paddingLength->toInt(), self::UTF8);

        // Concatenate the padding and the original string
        $padded = $paddingText . $this->value;

        return new self($padded);
    }

    /**
     * Pads the string to the specified length with the given pad string at the end
     *
     * @param int<0, max>|Integer $targetLength The target length after padding
     * @param string|Str $padString The string to use for padding (default: space)
     * @return self A new Str instance with padding applied
     */
    public function padEnd(int | Integer $targetLength, string | self $padString): self
    {
        $targetLength = $targetLength instanceof Integer ? $targetLength : Integer::of($targetLength);
        $targetLength = $targetLength->max(0);
        $padString = $padString instanceof self ? $padString->toString() : $padString;

        $currentLength = $this->chars()->size();

        // If target length is less than or equal to current length, return as is
        if ($targetLength->le($currentLength) || $padString === '') {
            return new self($this->value);
        }

        // Calculate padding length needed
        $paddingLength = $targetLength->sub($currentLength);

        // For multibyte characters, we need to calculate how many repetitions we need
        $padCharLen = \mb_strlen($padString, self::UTF8);
        $repetitions = \ceil($paddingLength->toInt() / $padCharLen);
        $paddingText = \mb_substr(\str_repeat($padString, (int) $repetitions), 0, $paddingLength->toInt(), self::UTF8);

        // Concatenate the original string and the padding
        $padded = $this->value . $paddingText;

        return new self($padded);
    }

    /**
     * Converts the string to lowercase
     *
     * @return self A new Str instance with the string converted to lowercase
     */
    public function toLowercase(): self
    {
        return new self(\mb_strtolower($this->value, self::UTF8));
    }

    /**
     * Converts the string to uppercase
     *
     * @return self A new Str instance with the string converted to uppercase
     */
    public function toUppercase(): self
    {
        return new self(\mb_strtoupper($this->value, self::UTF8));
    }

    /**
     * Trims whitespace from the beginning and end of the string
     *
     * @return self A new Str instance with the string trimmed
     */
    public function trim(): self
    {
        // First try with the Unicode property pattern for all kinds of whitespace
        $pattern = '/^[\p{Z}\s]+|[\p{Z}\s]+$/u';
        $trimmed = \preg_replace($pattern, '', $this->value);

        if ($trimmed === null || $trimmed === $this->value) {
            // Try a more specific pattern that explicitly includes common Unicode whitespace characters
            $pattern = '/^[\s\x{00A0}\x{2000}-\x{200D}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}]+|' .
                '[\s\x{00A0}\x{2000}-\x{200D}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}]+$/u';
            $trimmed = \preg_replace($pattern, '', $this->value);

            if ($trimmed === null) {
                // Fallback to standard trim if all else fails
                return new self(\trim($this->value));
            }
        }

        return new self($trimmed);
    }

    /**
     * Trims whitespace from the beginning of the string
     *
     * @return self A new Str instance with the string trimmed
     */
    public function trimStart(): self
    {
        // First try with the Unicode property pattern for all kinds of whitespace
        $pattern = '/^[\p{Z}\s]+/u';
        $trimmed = \preg_replace($pattern, '', $this->value);

        if ($trimmed === null || $trimmed === $this->value) {
            // Try a more specific pattern that explicitly includes common Unicode whitespace characters
            $pattern = '/^[\s\x{00A0}\x{2000}-\x{200D}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}]+/u';
            $trimmed = \preg_replace($pattern, '', $this->value);

            if ($trimmed === null) {
                // Fallback to standard ltrim if all else fails
                return new self(\ltrim($this->value));
            }
        }

        return new self($trimmed);
    }

    /**
     * Trims whitespace from the end of the string
     *
     * @return self A new Str instance with the string trimmed
     */
    public function trimEnd(): self
    {
        // First try with the Unicode property pattern for all kinds of whitespace
        $pattern = '/[\p{Z}\s]+$/u';
        $trimmed = \preg_replace($pattern, '', $this->value);

        if ($trimmed === null || $trimmed === $this->value) {
            // Try a more specific pattern that explicitly includes common Unicode whitespace characters
            $pattern = '/[\s\x{00A0}\x{2000}-\x{200D}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}]+$/u';
            $trimmed = \preg_replace($pattern, '', $this->value);

            if ($trimmed === null) {
                // Fallback to standard rtrim if all else fails
                return new self(\rtrim($this->value));
            }
        }

        return new self($trimmed);
    }

    /**
     * Returns a new string repeated n times
     *
     * @param int<0, max>|Integer $count The number of times to repeat the string
     * @return self A new Str instance with the repeated string
     */
    public function repeat(int | Integer $count): self
    {
        $count = $count instanceof Integer ? $count : Integer::of($count);
        $count = $count->max(0);

        if ($count->eq(0) || $this->isEmpty()) {
            return self::new();
        }

        return new self(\str_repeat($this->value, $count->toInt()));
    }

    /**
     * Strips the given prefix from the string if it exists
     *
     * @param string|Str $prefix The prefix to remove
     * @return self A new Str instance with the prefix removed (unchanged if prefix doesn't match)
     */
    public function stripPrefix(string | self $prefix): self
    {
        $prefix = $prefix instanceof self ? $prefix->toString() : $prefix;

        if ($this->isEmpty() || $prefix === '') {
            return new self($this->value);
        }

        if ($this->startsWith($prefix)) {
            $prefixLen = \mb_strlen($prefix, self::UTF8);

            return new self(\mb_substr($this->value, $prefixLen, null, self::UTF8));
        }

        return new self($this->value);
    }

    /**
     * Strips the given suffix from the string if it exists
     *
     * @param string|Str $suffix The suffix to remove
     * @return self A new Str instance with the suffix removed (unchanged if suffix doesn't match)
     */
    public function stripSuffix(string | self $suffix): self
    {
        $suffix = $suffix instanceof self ? $suffix->toString() : $suffix;

        if ($this->isEmpty() || $suffix === '') {
            return new self($this->value);
        }

        if ($this->endsWith($suffix)) {
            $valueLen = \mb_strlen($this->value, self::UTF8);
            $suffixLen = \mb_strlen($suffix, self::UTF8);

            return new self(\mb_substr($this->value, 0, $valueLen - $suffixLen, self::UTF8));
        }

        return new self($this->value);
    }

    /**
     * Checks if the string starts with the given prefix
     *
     * @param string|Str $prefix The prefix to check for
     * @return bool True if the string starts with the prefix
     */
    public function startsWith(string | self $prefix): bool
    {
        $prefix = $prefix instanceof self ? $prefix->toString() : $prefix;

        if ($this->isEmpty()) {
            return false;
        }

        return \mb_strpos($this->value, $prefix, 0, self::UTF8) === 0;
    }

    /**
     * Checks if the string ends with the given suffix
     *
     * @param string|Str $suffix The suffix to check for
     * @return bool True if the string ends with the suffix
     */
    public function endsWith(string | self $suffix): bool
    {
        $suffix = $suffix instanceof self ? $suffix->toString() : $suffix;

        if ($this->isEmpty()) {
            return false;
        }

        $valueLength = \mb_strlen($this->value, self::UTF8);
        $suffixLength = \mb_strlen($suffix, self::UTF8);

        if ($suffixLength > $valueLength) {
            return false;
        }

        $start = $valueLength - $suffixLength;

        return \mb_substr($this->value, $start, null, self::UTF8) === $suffix;
    }

    /**
     * Replace a range of characters with a new string
     *
     * @param int|Integer $start The starting index
     * @param int|Integer $length The length of the range to replace
     * @param string|self $replacement The string to insert
     * @return self The new string with the range replaced
     */
    public function replaceRange(
        int | Integer $start,
        int | Integer $length,
        string | self $replacement,
    ): self {
        $start = $start instanceof Integer ? $start->toInt() : $start;
        $length = $length instanceof Integer ? $length->toInt() : $length;
        $replacement = $replacement instanceof self ? $replacement->toString() : $replacement;

        $beforePart = \mb_substr(
            $this->value,
            0,
            $start,
            self::UTF8,
        );

        $afterPart = \mb_substr(
            $this->value,
            $start + $length,
            null,
            self::UTF8,
        );

        $newString = $beforePart . $replacement . $afterPart;

        return new self($newString);
    }

    /**
     * Wraps the string to a specified line length
     *
     * The width must be greater than 0. If a negative or zero Integer is provided, it will be treated as 1.
     *
     * @param int<1, max>|Integer $width The maximum line length
     * @param string $break The line break character(s) to use
     * @return self A new Str instance with line breaks inserted
     */
    public function wrap(int | Integer $width, string $break = "\n"): self
    {
        $width = $width instanceof Integer ? $width : Integer::of($width);
        $width = $width->max(1);

        if ($this->isEmpty()) {
            return new self($this->value);
        }

        // If the string is shorter than the width, no wrapping needed
        if ($width->ge($this->chars()->size())) {
            return new self($this->value);
        }

        // For very small widths, we'll use a character-by-character approach
        if ($width->le(3)) {
            return $this->wrapSmallWidth($width->toInt(), $break);
        }

        $width = $width->toInt();

        // Split into words for normal wrapping
        $result = '';
        $words = \preg_split('/\s+/u', $this->value, -1, \PREG_SPLIT_NO_EMPTY);

        if ($words === false) {
            return new self($this->value);
        }

        $lineLength = 0;
        $isFirstWord = true;

        foreach ($words as $word) {
            $wordLength = \mb_strlen($word, self::UTF8);

            // Special case: If this word is too long to fit on a line
            if ($wordLength > $width) {
                // If we were in the middle of a line, end it first
                if (!$isFirstWord && $lineLength > 0) {
                    $result .= $break;
                }

                // Break down the long word into chunks
                $result .= $this->wrapLongWord($word, $width, $break);
                $lineLength = \mb_strlen(\mb_substr($word, $wordLength - ($wordLength % $width), null, self::UTF8), self::UTF8);

                if ($lineLength === 0 && $wordLength > 0) {
                    $lineLength = $width; // We ended on a perfect boundary
                }
                $isFirstWord = false;

                continue;
            }

            // Normal case: This word can fit on a line
            // Check if it would make the current line too long
            if (!$isFirstWord && $lineLength + 1 + $wordLength > $width) {
                $result .= $break;
                $result .= $word;
                $lineLength = $wordLength;
            } else {
                // Add space before non-first words
                if (!$isFirstWord) {
                    $result .= ' ';
                    $lineLength++;
                }
                $result .= $word;
                $lineLength += $wordLength;
            }

            $isFirstWord = false;
        }

        return new self($result);
    }

    /**
     * Attempt to convert the string to an integer
     *
     * @return Result<Integer, ParseError> The integer value or an error
     */
    public function parseInteger(): Result
    {
        if ($this->isEmpty()) {
            /** @var Result<Integer, ParseError> */
            return Result::err(
                new ParseError('Cannot convert empty string to integer - string must contain numeric digits'),
            );
        }

        if (!\preg_match('/^[+-]?\d+$/', $this->value)) {
            /** @var Result<Integer, ParseError> */
            return Result::err(
                new ParseError(
                    \sprintf('String "%s" does not represent a valid integer - must be a whole number with optional sign', $this->value),
                ),
            );
        }

        $value = (int) $this->value;
        $stringValue = (string) $value;

        // Check if the number is too large for PHP's integer type by checking if
        // conversion back to string matches original value
        if ($stringValue !== $this->value && \is_numeric($this->value)) {
            /** @var Result<Integer, ParseError> */
            return Result::err(
                new ParseError(
                    \sprintf(
                        'Number "%s" is too large for PHP\'s integer type. Max value: %s, Min value: %s',
                        $this->value,
                        \PHP_INT_MAX,
                        \PHP_INT_MIN,
                    ),
                ),
            );
        }

        /** @var Result<Integer, ParseError> */
        return Result::ok(Integer::of($value));
    }

    /**
     * Attempt to convert the string to a Double
     *
     * @return Result<Double, ParseError> The float value or an error
     */
    public function parseDouble(): Result
    {
        if ($this->isEmpty()) {
            /** @var Result<Double, ParseError> */
            return Result::err(
                new ParseError('Cannot convert empty string to float - string must contain a numeric value'),
            );
        }

        if (!\is_numeric($this->value)) {
            /** @var Result<Double, ParseError> */
            return Result::err(
                new ParseError(
                    \sprintf('String "%s" does not represent a valid float - expected format is [-+]?[0-9]*\.?[0-9]+', $this->value),
                ),
            );
        }

        $floatValue = (float) $this->value;

        // Check for INF or NAN
        if (\is_infinite($floatValue)) {
            /** @var Result<Double, ParseError> */
            return Result::err(
                new ParseError(
                    \sprintf('Value "%s" is too large for a float (resulted in INF)', $this->value),
                ),
            );
        }

        if (\is_nan($floatValue)) {
            /** @var Result<Double, ParseError> */
            return Result::err(
                new ParseError(
                    \sprintf('Value "%s" resulted in NaN which is not a valid float', $this->value),
                ),
            );
        }

        /** @var Result<Double, ParseError> */
        return Result::ok(Double::of($floatValue));
    }

    /**
     * Attempt to convert the string to a boolean
     *
     * "true", "1" are considered true
     * "false", "0" are considered false
     *
     * @return Result<bool, ParseError> The boolean value or an error
     */
    public function parseBool(): Result
    {
        if ($this->isEmpty()) {
            /** @var Result<bool, ParseError> */
            return Result::err(
                new ParseError('Cannot convert empty string to boolean - expected values: true/false, 1/0'),
            );
        }

        $lower = \mb_strtolower($this->value, self::UTF8);

        if (\in_array($lower, ['true', '1'], true)) {
            /** @var Result<bool, ParseError> */
            return Result::ok(true);
        }

        if (\in_array($lower, ['false', '0'], true)) {
            /** @var Result<bool, ParseError> */
            return Result::ok(false);
        }

        /** @var Result<bool, ParseError> */
        return Result::err(
            new ParseError(
                \sprintf('String "%s" does not represent a valid boolean - expected values: true/false, 1/0', $this->value),
            ),
        );
    }

    /**
     * Checks if the string contains the given substring
     *
     * @param string|Str $substring The substring to check for
     * @return bool True if the string contains the substring
     */
    public function contains(string | self $substring): bool
    {
        $substring = $substring instanceof self ? $substring->toString() : $substring;

        if ($this->isEmpty() || $substring === '') {
            return false;
        }

        return \mb_strpos($this->value, $substring, 0, self::UTF8) !== false;
    }

    /**
     * Returns a Sequence of all matches of a pattern in the string
     *
     * @param string|Str $pattern The regex pattern to match with "u" modifier for UTF-8
     * @return Sequence<Str> A Sequence with all matching substrings
     */
    public function matches(string | self $pattern): Sequence
    {
        $pattern = $pattern instanceof self ? $pattern->toString() : $pattern;

        if ($pattern === '') {
            return Sequence::new();
        }

        if ($this->isEmpty()) {
            return Sequence::new();
        }

        // Ensure that the pattern has the 'u' modifier for UTF-8
        if (!\str_ends_with($pattern, 'u')) {
            $pattern .= 'u';
        }

        // Check if the pattern is delimited by slashes
        if (
            !\str_starts_with($pattern, '/') &&
            \substr($pattern, -2, 1) !== '/'
        ) {
            $pattern = '/' . $pattern . '/';

            if (!\str_ends_with($pattern, 'u')) {
                $pattern .= 'u';
            }
        }

        $matches = [];
        \preg_match_all($pattern, $this->value, $matches);

        if (!isset($matches[0]) || \count($matches[0]) === 0) {
            return Sequence::new();
        }

        return Sequence::ofArray($matches[0])->map(
            static fn(string $match) => Str::of($match),
        );
    }

    /**
     * Returns a Sequence over the indices of all matches of a pattern in the string
     *
     * TODO : return a MAP
     *
     * @param string|Str $pattern The regex pattern to match with "u" modifier for UTF-8
     * @return Sequence<array{Integer, Str}> A Sequence with tuples of [index, match]
     */
    public function matchIndices(string | self $pattern): Sequence
    {
        $pattern = $pattern instanceof self ? $pattern->toString() : $pattern;

        if ($pattern === '') {
            return Sequence::new();
        }

        if ($this->isEmpty()) {
            return Sequence::new();
        }

        // Ensure that the pattern has the 'u' modifier for UTF-8
        if (!\str_ends_with($pattern, 'u')) {
            $pattern .= 'u';
        }

        // Check if the pattern is delimited by slashes
        if (
            !\str_starts_with($pattern, '/') &&
            \substr($pattern, -2, 1) !== '/'
        ) {
            $pattern = '/' . $pattern . '/';

            if (!\str_ends_with($pattern, 'u')) {
                $pattern .= 'u';
            }
        }

        $matches = [];
        $offsets = [];

        // Use preg_match_all with PREG_OFFSET_CAPTURE to get byte offsets
        if (
            \preg_match_all(
                $pattern,
                $this->value,
                $matches,
                \PREG_OFFSET_CAPTURE,
            ) === false
        ) {
            return Sequence::new();
        }

        if (!isset($matches[0]) || \count($matches[0]) === 0) {
            return Sequence::new();
        }

        // Convert byte offsets to UTF-8 character offsets
        foreach ($matches[0] as $match) {
            $byteOffset = $match[1];
            $matchText = $match[0];

            // Compute character offset: extract bytes then count UTF-8 characters
            $substring = \substr($this->value, 0, $byteOffset);
            $charOffset = \mb_strlen($substring, self::UTF8);

            /** @var array{Integer, Str} $offset */
            $offset = [Integer::of($charOffset), self::of($matchText)];
            $offsets[] = $offset;
        }

        return Sequence::ofArray($offsets);
    }

    /**
     * Applies a function to the string and returns a new string
     *
     * @param callable(string): string $mapper A function that takes a string and returns a string
     * @return self A new string with the function applied
     */
    public function map(callable $mapper): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return self::of($mapper($this->value));
    }

    /**
     * Returns the string with Unicode normalization applied
     *
     * @param non-empty-string $form Normalization form (NFC, NFD, NFKC, NFKD)
     * @return Result<self, NormalizationError|InvalidNormalizationForm|\RuntimeException> A result containing the normalized string or an error
     */
    public function normalize(string $form = 'NFC'): Result
    {
        if (!\extension_loaded('intl')) {
            /** @var Result<self, NormalizationError|InvalidNormalizationForm|\RuntimeException> */
            return Result::err(new \RuntimeException('The intl extension is required for Unicode normalization'));
        }

        $validForms = ['NFC', 'NFD', 'NFKC', 'NFKD'];

        if (!\in_array($form, $validForms, true)) {
            /** @var Result<self, NormalizationError|InvalidNormalizationForm|\RuntimeException> */
            return Result::err(new InvalidNormalizationForm(
                "Invalid normalization form: $form. Valid options are: " .
                \implode(', ', $validForms),
            ));
        }

        // Map normalization forms to their constants
        $formConstants = [
            'NFC' => \Normalizer::FORM_C,
            'NFD' => \Normalizer::FORM_D,
            'NFKC' => \Normalizer::FORM_KC,
            'NFKD' => \Normalizer::FORM_KD,
        ];

        $normalized = \normalizer_normalize(
            $this->value,
            $formConstants[$form],
        );

        if ($normalized === false) {
            /** @var Result<self, NormalizationError|InvalidNormalizationForm|\RuntimeException> */
            return Result::err(new NormalizationError('Unicode normalization failed'));
        }

        /** @var Result<self, NormalizationError|InvalidNormalizationForm|\RuntimeException> */
        return Result::ok(new self($normalized));
    }

    /**
     * Checks if the string is valid UTF-8
     *
     * @return bool True if the string is valid UTF-8
     */
    public function isValidUtf8(): bool
    {
        return \mb_check_encoding($this->value, self::UTF8);
    }

    /**
     * Escapes Unicode characters in the string
     *
     * If the string contains invalid UTF-8 characters, they will be replaced with the Unicode replacement character (\uFFFD).
     *
     * @return self A new Str instance with escaped Unicode characters
     */
    public function escapeUnicode(): self
    {
        if ($this->isEmpty()) {
            return self::new();
        }

        $escaped = '';

        for ($i = 0; $i < \mb_strlen($this->value, self::UTF8); $i++) {
            $char = \mb_substr($this->value, $i, 1, self::UTF8);
            $code = \mb_ord($char, self::UTF8);

            // Use the Unicode specification for invalid characters
            if ($code === false) {
                $escaped .= '\\uFFFD'; // Unicode replacement character

                continue;
            }

            // ASCII printable characters (32-126) stay as they are
            if ($code >= 32 && $code <= 126) {
                $escaped .= $char;
            }
            // Control characters
            elseif ($code <= 0x1f || $code === 0x7f) {
                $escaped .= '\\x' . \str_pad(\dechex($code), 2, '0', \STR_PAD_LEFT);
            }
            // Extended ASCII and other BMP characters
            elseif ($code <= 0xffff) {
                $escaped .= '\\u' . \str_pad(\dechex($code), 4, '0', \STR_PAD_LEFT);
            }
            // Non-BMP characters (supplementary planes)
            else {
                $escaped .= '\\U' . \str_pad(\dechex($code), 8, '0', \STR_PAD_LEFT);
            }
        }

        return new self($escaped);
    }

    /**
     * Returns a substring of the string
     *
     * @param int|Integer $start The starting index of the substring
     * @param int|Integer $length The length of the substring
     * @return Option<self> A new Str with the substring, or None if the indices are invalid
     */
    public function getRange(int | Integer $start, int | Integer $length): Option
    {
        $start = $start instanceof Integer ? $start : Integer::of($start);
        $length = $length instanceof Integer ? $length : Integer::of($length);
        $size = $this->chars()->size();

        if ($start->lt(0) || $start->ge($size)) {
            return Option::none();
        }

        if ($start->add($length)->gt($size)) {
            $length = $size->sub($start);
        }

        return Option::some(
            new self(\mb_substr($this->value, $start->toInt(), $length->toInt(), self::UTF8)),
        );
    }

    /**
     * Returns the character at the given index
     *
     * @param int|Integer $index The index of the character to get
     * @return Option<Char> The character at the given index, or None if out of bounds
     */
    public function get(int | Integer $index): Option
    {
        $index = $index instanceof Integer ? $index : Integer::of($index);

        if ($index->lt(0) || $index->ge($this->chars()->size())) {
            return Option::none();
        }

        return Option::some(
            Char::of(\mb_substr($this->value, $index->toInt(), 1, self::UTF8)),
        );
    }

    /**
     * Finds the first occurrence of a substring in the string
     *
     * @param string|Str $needle The substring to search for
     * @return Option<Integer> The index of the first occurrence, or None if not found
     */
    public function find(string | self $needle): Option
    {
        $needle = $needle instanceof self ? $needle->toString() : $needle;
        $pos = \mb_strpos($this->value, $needle, 0, self::UTF8);

        return $pos === false ? Option::none() : Option::some(Integer::of($pos));
    }

    /**
     * Checks if the string is ASCII
     *
     * @return bool True if the string is ASCII, false otherwise
     */
    public function isAscii(): bool
    {
        if ($this->isEmpty()) {
            return true;
        }

        // Use PHP's built-in function to check if there are any non-ASCII characters
        return !\preg_match('/[\x80-\xFF]/', $this->value);
    }

    /**
     * Splits the string into lines
     *
     * @return Sequence<Str> A Sequence of Str lines
     */
    public function lines(): Sequence
    {
        if ($this->isEmpty()) {
            /** @var Sequence<Str> */
            return Sequence::new();
        }

        $lines = \preg_split('/\r\n|\n|\r/u', $this->value);

        if (!\is_array($lines)) {
            /** @var Sequence<Str> */
            return Sequence::new();
        }

        /** @var Sequence<Str> */
        return Sequence::ofArray($lines)->map(
            static fn(string $line) => Str::of($line),
        );
    }

    /**
     * Returns a new string containing the first $length characters of the current string.
     *
     * @param int|Integer $length The number of characters to take.
     * @return Str The new string.
     */
    public function take(int | Integer $length): self
    {
        $length = $length instanceof Integer ? $length : Integer::of($length);

        if ($length->le(0) || $this->isEmpty()) {
            return self::new();
        }

        $value = \mb_substr($this->value, 0, $length->toInt(), self::UTF8);

        return new self($value);
    }

    /**
     * Returns a new string containing the last $length characters of the current string.
     *
     * @param int|Integer $length The number of characters to drop from the start of the string.
     * @return Str The new string.
     */
    public function skip(int | Integer $length): self
    {
        $length = $length instanceof Integer ? $length : Integer::of($length);

        if ($this->isEmpty()) {
            return self::new();
        }

        if ($length->le(0)) {
            return new self($this->value);
        }

        $value = \mb_substr($this->value, $length->toInt(), null, self::UTF8);

        return new self($value);
    }

    /**
     * Returns the raw UTF-8 string value
     *
     * @return string The raw UTF-8 string
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Try to encode a string to UTF-8
     *
     * @param non-empty-string|null $sourceEncoding Optional explicit source encoding
     * @return Result<Str, EncodingError> A Result containing the UTF-8 encoded string or an error
     * @psalm-suppress ImpureMethodCall
     */
    public function forceUtf8(?string $sourceEncoding = null): Result
    {
        $result = $this->encode($this->value, $sourceEncoding);

        if ($result->isErr()) {
            /** @var Result<Str, EncodingError> */
            return $result;
        }

        /** @var Result<Str, EncodingError> */
        return $result->map(static fn(string $encoded) => Str::of($encoded));
    }

    /**
     * Helper method for wrapping very long words
     *
     * @param string $word The word to wrap
     * @param int $width The maximum line width
     * @param string $break The line break character to use
     * @return string The wrapped word
     */
    private function wrapLongWord(string $word, int $width, string $break): string
    {
        $result = '';
        $wordLength = \mb_strlen($word, self::UTF8);

        for ($i = 0; $i < $wordLength; $i += $width) {
            if ($i > 0) {
                $result .= $break;
            }
            $result .= \mb_substr($word, $i, $width, self::UTF8);
        }

        return $result;
    }

    /**
     * Helper method for wrapping at very small widths (character by character)
     *
     * @param int $width The maximum line width
     * @param string $break The line break character to use
     * @return self The wrapped string
     */
    private function wrapSmallWidth(int $width, string $break): self
    {
        $result = '';
        $chars = $this->chars();
        $count = $chars->size()->toInt();

        $chars = $chars->map(static fn(Char $char) => $char->toString())->toArray();

        for ($i = 0; $i < $count; $i++) {
            $result .= $chars[$i];

            // Add a line break after all $width characters, but not at the end
            if (($i + 1) % $width === 0 && $i < $count - 1) {
                $result .= $break;
            }
        }

        return new self($result);
    }

    /**
     * Encodes a string to UTF-8, handling multiple source encodings
     * and performing validation
     *
     * @param string $value The string to encode
     * @param non-empty-string|null $sourceEncoding Optional explicit source encoding
     * @return Result<string, EncodingError> A Result containing the encoded string or an error
     */
    private function encode(
        string $value,
        ?string $sourceEncoding = null,
    ): Result {
        // If empty, return immediately for performance
        if ($value === '') {
            /** @var Result<string, EncodingError> */
            return Result::ok('');
        }

        // If the string is already valid UTF-8 and no source encoding is specified, return it as is
        if (
            $sourceEncoding === null &&
            \mb_check_encoding($value, self::UTF8)
        ) {
            /** @var Result<string, EncodingError> */
            return Result::ok($value);
        }

        // Remove UTF-8 BOM if present
        if (\strncmp($value, "\xEF\xBB\xBF", 3) === 0) {
            $value = \substr($value, 3);
        }

        // Try to detect encoding if not provided
        if ($sourceEncoding === null) {
            // List of common encodings to detect, from most to least likely
            $encodings = [
                self::UTF8,
                'ASCII',
                'ISO-8859-1',
                'Windows-1252',
                'ISO-8859-15',
                'Windows-1251',
                'ISO-8859-2',
            ];

            // In PHP 8.4+, some encodings may not be supported
            // Check if each encoding is available
            $supportedEncodings = [];

            foreach ($encodings as $encoding) {
                if (@\mb_check_encoding('', $encoding)) {
                    $supportedEncodings[] = $encoding;
                }
            }

            $sourceEncoding = \mb_detect_encoding($value, $supportedEncodings, true);

            // Fallback to UTF-8 if detection fails
            if ($sourceEncoding === false) {
                $sourceEncoding = self::UTF8;
            }
        }

        // Convert to UTF-8
        $convertedString = \mb_convert_encoding(
            $value,
            self::UTF8,
            $sourceEncoding,
        );

        if ($convertedString === false) {
            /** @var Result<string, EncodingError> */
            return Result::err(
                new InvalidSourceCharacters(
                    \sprintf(
                        'Failed to encode string from %s to UTF-8: possible invalid source character(s) in string',
                        $sourceEncoding,
                    ),
                ),
            );
        }

        // Validate UTF-8
        if (!\mb_check_encoding($convertedString, self::UTF8)) {
            // Try a more aggressive approach with replacement of invalid bytes
            if (\function_exists('iconv')) {
                $convertedString = \iconv(
                    $sourceEncoding,
                    'UTF-8//IGNORE',
                    $value,
                );

                if ($convertedString === false) {
                    /** @var Result<string, EncodingError> */
                    return Result::err(
                        new InvalidUTF8Sequences(
                            \sprintf(
                                'String contains invalid UTF-8 sequences that could not be converted. Source encoding: %s',
                                $sourceEncoding,
                            ),
                        ),
                    );
                }
            } else {
                // Last resort: use preg_replace to remove invalid UTF-8 sequences
                $convertedString = \preg_replace(
                    '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]|[\xC0-\xDF](?![\x80-\xBF])|[\xE0-\xEF](?![\x80-\xBF]{2})|[\xF0-\xF7](?![\x80-\xBF]{3})|(?<=[\x00-\x7F\xF8-\xFF])[\x80-\xBF]|(?<![\xC0-\xEF\xF0-\xF7])[\x80-\xBF]|(?<=[\xC0-\xDF])[\x80-\xBF](?![\x80-\xBF])|(?<=[\xE0-\xEF])[\x80-\xBF](?![\x80-\xBF])|(?<=[\xF0-\xF7])[\x80-\xBF](?![\x80-\xBF]{2})|(?<=[\xF8-\xFB])[\x80-\xBF](?![\x80-\xBF]{3})/S',
                    '',
                    $convertedString,
                );

                if ($convertedString === null) {
                    // As a true last resort, return an empty string rather than failing entirely
                    $convertedString = '';
                }
            }
        }

        /** @var Result<string, EncodingError> */
        return Result::ok($convertedString);
    }
}
