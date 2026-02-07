# Char

The `Char` class is an immutable Unicode character type representing a single Unicode codepoint. Classification methods use `IntlChar` for full Unicode support beyond ASCII.

## Table of Contents

- [Creation](#creation)
- [Classification](#classification)
- [Conversion](#conversion)
- [Notes](#notes)

## Creation

### From String

Creates a new Char instance from a single-character string. Supports any valid Unicode codepoint.

```php
$char = Char::of('A');
$accented = Char::of('é');
$cjk = Char::of('中');
$emoji = Char::of('🎉');
```

Throws `InvalidArgumentException` if the string is empty or contains more than one character.

```php
Char::of('');   // InvalidArgumentException
Char::of('AB'); // InvalidArgumentException
Char::of('éà'); // InvalidArgumentException
```

### From Digit

Creates a Char from an integer digit between 0 and 9. Accepts both `int` and `Integer`.

```php
$five = Char::ofDigit(5);                // Char '5'
$three = Char::ofDigit(Integer::of(3));  // Char '3'
```

Throws `InvalidArgumentException` if the digit is outside the 0-9 range.

```php
Char::ofDigit(-1); // InvalidArgumentException
Char::ofDigit(10); // InvalidArgumentException
```

## Classification

All classification methods are Unicode-aware, powered by `IntlChar` (ICU library). They return `bool`.

### Is Alphabetic

Checks if the character is a Unicode letter.

```php
Char::of('A')->isAlphabetic();  // true
Char::of('é')->isAlphabetic();  // true
Char::of('中')->isAlphabetic(); // true
Char::of('5')->isAlphabetic();  // false
Char::of('!')->isAlphabetic();  // false
```

### Is Digit

Checks if the character is a Unicode digit.

```php
Char::of('5')->isDigit();       // true
Char::of('0')->isDigit();       // true
Char::of("\u{0663}")->isDigit(); // true (Arabic-Indic digit three)
Char::of('A')->isDigit();       // false
```

### Is Alphanumeric

Checks if the character is a Unicode letter or digit.

```php
Char::of('A')->isAlphanumeric(); // true
Char::of('5')->isAlphanumeric(); // true
Char::of('é')->isAlphanumeric(); // true
Char::of('!')->isAlphanumeric(); // false
```

### Is Whitespace

Checks if the character is a Unicode whitespace character (ICU definition).

```php
Char::of(' ')->isWhitespace();       // true
Char::of("\t")->isWhitespace();      // true
Char::of("\n")->isWhitespace();      // true
Char::of("\u{2003}")->isWhitespace(); // true (em space)
Char::of('A')->isWhitespace();       // false
```

**Note:** `IntlChar::isWhitespace()` follows ICU/Java semantics, which excludes non-breaking space (U+00A0). See [Notes](#notes).

### Is Lowercase

Checks if the character is a Unicode lowercase letter.

```php
Char::of('a')->isLowercase(); // true
Char::of('é')->isLowercase(); // true
Char::of('A')->isLowercase(); // false
Char::of('5')->isLowercase(); // false
```

### Is Uppercase

Checks if the character is a Unicode uppercase letter.

```php
Char::of('A')->isUppercase(); // true
Char::of('É')->isUppercase(); // true
Char::of('a')->isUppercase(); // false
Char::of('5')->isUppercase(); // false
```

### Is Punctuation

Checks if the character is a Unicode punctuation character.

```php
Char::of('!')->isPunctuation(); // true
Char::of('.')->isPunctuation(); // true
Char::of(',')->isPunctuation(); // true
Char::of('A')->isPunctuation(); // false
```

### Is Control

Checks if the character is a Unicode control character.

```php
Char::of("\x00")->isControl(); // true (NUL)
Char::of("\x1F")->isControl(); // true (US)
Char::of('A')->isControl();    // false
```

### Is Printable

Checks if the character is printable.

```php
Char::of('A')->isPrintable();    // true
Char::of('é')->isPrintable();    // true
Char::of(' ')->isPrintable();    // true
Char::of("\x00")->isPrintable(); // false
```

### Is Hexadecimal

Checks if the character is a hexadecimal digit (0-9, a-f, A-F).

```php
Char::of('0')->isHexadecimal(); // true
Char::of('9')->isHexadecimal(); // true
Char::of('a')->isHexadecimal(); // true
Char::of('F')->isHexadecimal(); // true
Char::of('G')->isHexadecimal(); // false
```

### Is ASCII

Checks if the character's codepoint is below 128.

```php
Char::of('A')->isAscii();  // true
Char::of('0')->isAscii();  // true
Char::of(' ')->isAscii();  // true
Char::of('é')->isAscii();  // false
Char::of('中')->isAscii(); // false
```

## Conversion

### To Uppercase

Returns a new Char with the character converted to uppercase.

```php
Char::of('a')->toUppercase()->toString(); // 'A'
Char::of('é')->toUppercase()->toString(); // 'É'
Char::of('ö')->toUppercase()->toString(); // 'Ö'
```

### To Lowercase

Returns a new Char with the character converted to lowercase.

```php
Char::of('A')->toLowercase()->toString(); // 'a'
Char::of('É')->toLowercase()->toString(); // 'é'
Char::of('Ö')->toLowercase()->toString(); // 'ö'
```

### To String

Returns the raw string value. Also usable via string interpolation.

```php
$char = Char::of('X');
$char->toString(); // 'X'
(string) $char;    // 'X'
echo "$char";      // 'X'
```

## Notes

### Codepoint vs Grapheme Cluster

`Char` represents a single Unicode codepoint, not a grapheme cluster. Some visible characters are composed of multiple codepoints (e.g. combining diacritical marks, emoji with modifiers). These cannot be represented as a single `Char`.

For working with grapheme clusters, use `Str` and its methods instead.

### ICU Whitespace Semantics

`isWhitespace()` follows the ICU/Java definition of whitespace, which specifically **excludes** no-break space (U+00A0) and zero-width no-break space (U+FEFF). This is intentional: these characters are designed to prevent line breaks and are not considered general-purpose whitespace.

Characters considered whitespace include: space (U+0020), tab (U+0009), line feed (U+000A), carriage return (U+000D), and Unicode space separators like em space (U+2003).
