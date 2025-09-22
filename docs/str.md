# Str (String)

The `Str` class is an immutable UTF-8 string type that provides a rich set of operations for safely handling text. It draws inspiration from Rust's string handling but is adapted to PHP's ecosystem and offers strong UTF-8 support.

## Table of Contents

- [Creation](#creation)
- [Basic Operations](#basic-operations)
- [Inspection](#inspection)
- [Modification](#modification)
- [Transformation](#transformation)
- [Extraction](#extraction)
- [Searching](#searching)
- [Padding and Trimming](#padding-and-trimming)
- [Splitting](#splitting)
- [Case Conversion](#case-conversion)
- [Parsing](#parsing)
- [UTF-8 Handling](#utf-8-handling)
- [Comparison](#comparison)

## Creation

### From String

Creates a new Str instance from a string value.

```php
// Create from a plain string
$str = Str::of('Hello world');

// Create from a string with explicit encoding
$str = Str::of('Привет', 'UTF-8');
```

**Notes:**

- NOTE: This does not try to force the string to UTF-8 encoding. See `Str::forceUtf8()` for forcing UTF-8 encoding and `Str::isValidUtf8()` for checking if the string is valid UTF-8.

- This does not apply unicode normalization by default. See `Str::normalize()` for handling normalized Unicode text.

### Empty String

Creates a new empty Str instance.

```php
// Create an empty string
$emptyStr = Str::new();
```

### Clear

Returns a new empty Str instance.

```php
$str = Str::of('Hello');
$emptyStr = $str->clear(); // Creates a new empty string
```

## Basic Operations

### Length

Returns the length of the string in bytes (not characters).

```php
$str = Str::of('Hello');
$length = $str->size()->toInt(); // 5 (for ASCII characters, bytes equal characters)

$utf8Str = Str::of('été'); // Multi-byte characters
$length = $utf8Str->size()->toInt(); // 4 (counts bytes, not characters)
```

**Notes:** For graphemes counting, use :

```php
$len = Str::of('été')->chars()->size()->toInt();
```

But note that it also depends of the normalization form used. see `Str::normalize()`.

### Is Empty

Checks if the string is empty.

```php
$str = Str::of('Hello');
$isEmpty = $str->isEmpty(); // false

$emptyStr = Str::new();
$isEmpty = $emptyStr->isEmpty(); // true
```

### String Representation

Returns the raw UTF-8 string.

```php
$str = Str::of('Hello');
echo $str; // Outputs: Hello
```

### To String

Returns the underlying string value.

```php
$str = Str::of('Hello');
$string = $str->toString(); // 'Hello'
```

## Modification

### Insert At

Inserts a string at the specified character position.

```php
$str = Str::of('Hello World');
$newStr = $str->insertAt(5, Str::of(', beautiful')); // 'Hello, beautiful World'
```

### Append

Appends the content of another Str instance to the end of this string.

```php
$str1 = Str::of('Hello');
$str2 = Str::of(' World');
$combined = $str1->append($str2); // 'Hello World'
```

### Prepend

Prepends the content of another Str instance to the beginning of this string.

```php
$str1 = Str::of('World');
$str2 = Str::of('Hello ');
$combined = $str1->prepend($str2); // 'Hello World'
```

### Remove At

Removes the character at the specified index.

```php
$str = Str::of('Hello');
$modified = $str->removeAt(1); // 'Hllo' (removes the 'e')
```

### Remove Matches

Removes all characters matching the regex pattern.

```php
$str = Str::of('Hello123World456');
$noNumbers = $str->removeMatches('/\d+/'); // 'HelloWorld'
```

**Note:** The pattern is automatically adjusted to support UTF-8 if necessary.

### Truncate

Truncates the string to the specified length.

```php
$str = Str::of('Hello World');
$truncated = $str->truncate(5); // 'Hello'
```

### Take

Returns a new string containing the first `n` characters of the current string.

```php
$str = Str::of('Hello World');
$taken = $str->take(5); // 'Hello'
```

**Note:** If `length` is less than or equal to zero, an empty string is returned. If `length` is greater than the string length, the entire string is returned.

### Skip

Returns a new string with the first `n` characters removed.

```php
$str = Str::of('Hello World');
$skipped = $str->skip(6); // 'World'
```

**Note:** If `length` is less than or equal to zero, an unchanged string is returned. If `length` is greater than the string length, an empty string is returned.

### Replace

Replaces all occurrences of a substring with another substring.

```php
$str = Str::of('Hello World');
$replaced = $str->replace('World', 'PHP'); // 'Hello PHP'

// Using regex
$str = Str::of('Hello 123 World 456');
$replaced = $str->replace('\d+', 'X', true); // 'Hello X World X'
```

### Replace Range

Replaces a range of characters with a new substring.

```php
$str = Str::of('Hello World');
$replaced = $str->replaceRange(6, 5, 'PHP'); // 'Hello PHP'
```

## Transformation

### To Lowercase

Converts the string to lowercase.

```php
$str = Str::of('Hello World');
$lower = $str->toLowercase(); // 'hello world'
```

### To Uppercase

Converts the string to uppercase.

```php
$str = Str::of('Hello World');
$upper = $str->toUppercase(); // 'HELLO WORLD'
```

### Normalize

Normalizes the string according to Unicode normalization forms.

```php
// Composite character 'é' normalized to 'e' + combining accent
$str = Str::of('café');
$normalized = $str->normalize('NFD');

// By default uses NFC (canonical composition)
$normalized = $str->normalize(); // 'café' (with é as a single character)
```

**Supported forms:**
- `NFC`: Canonical Decomposition, followed by Canonical Composition (default)
- `NFD`: Canonical Decomposition
- `NFKC`: Compatibility Decomposition, followed by Canonical Composition
- `NFKD`: Compatibility Decomposition

### Escape Unicode

Escapes non-ASCII characters to Unicode escape sequences.

```php
$str = Str::of('Café');
$escaped = $str->escapeUnicode(); // 'Caf\u00e9'
```

### Repeat

Repeats the string a specified number of times.

```php
$str = Str::of('abc');
$repeated = $str->repeat(3); // 'abcabcabc'
```

## Extraction

### Get

Gets the character at the specified index.

```php
$str = Str::of('Hello');
$char = $str->get(1); // Option::some('e')
$outOfBounds = $str->get(10); // Option::none()
```

### Get Range

Gets a substring from the specified start position with the given length.

```php
$str = Str::of('Hello World');
$range = $str->getRange(0, 5); // Option::some('Hello')
$outOfBounds = $str->getRange(20, 5); // Option::none()
```

### Bytes

Converts the string to a Sequence of bytes (integers representing byte values).

```php
$str = Str::of('AB');
$bytes = $str->bytes(); // Sequence [65, 66]

$str = Str::of('é'); // UTF-8 multi-byte character
$bytes = $str->bytes(); // Sequence [195, 169] (UTF-8 representation of é)
```

### Chars

Converts the string to a Sequence of individual characters (each as a string).

```php
$str = Str::of('hello');
$chars = $str->chars(); // Sequence ["h", "e", "l", "l", "o"]

$str = Str::of('😀😀😀'); // UTF-8 multi-byte characters
$chars = $str->chars(); // Sequence ["😀", "😀", "😀"] (properly handles UTF-8)
```

**Note:** This method splits by Unicode code points, so characters in decomposed form will be split into multiple code points.

```php
$nfc = Str::of('é')->normalize('NFC')->unwrap()->chars()->size()->toInt(); // 1 "é" in composed form
$nfd = Str::of('é')->normalize('NFD')->unwrap()->chars()->size()->toInt(); // 2 "é" in decomposed form : "e" + "́"
```

### Lines

Splits the string into lines, returning a Sequence of Str instances.

```php
$str = Str::of("Line 1\nLine 2\nLine 3");
$lines = $str->lines(); // Sequence of Str with ["Line 1", "Line 2", "Line 3"]
```

## Searching

### Contains

Checks if the string contains the given substring.

```php
$str = Str::of('Hello World');
$contains = $str->contains('World'); // true
$contains = $str->contains('PHP'); // false
```

### Starts With

Checks if the string starts with the given prefix.

```php
$str = Str::of('Hello World');
$startsWith = $str->startsWith('Hello'); // true
$startsWith = $str->startsWith('World'); // false
```

### Ends With

Checks if the string ends with the given suffix.

```php
$str = Str::of('Hello World');
$endsWith = $str->endsWith('World'); // true
$endsWith = $str->endsWith('Hello'); // false
```

### Find

Finds the index of the first occurrence of a substring.

```php
$str = Str::of('Hello World');
$index = $str->find('World'); // Option::some(6)
$notFound = $str->find('PHP'); // Option::none()
```

### Matches

Checks if the string matches the given regex pattern.

```php
$str = Str::of('Hello123');
$matches = $str->matches('/^[A-Za-z]+\d+$/'); // true
$matches = $str->matches('/^\d+$/'); // false
```

### Match Indices

Finds all matches of a regex pattern and returns their positions.

```php
$str = Str::of('apple banana apple cherry');
$indices = $str->matchIndices('/apple/'); // Sequence [[0, 5], [12, 5]]
```

**Note:** Each match is returned as a Sequence with [start_position, length].

## Padding and Trimming

### Pad Start

Pads the string to the specified length with the given pad string at the start.

```php
$str = Str::of('42');
$padded = $str->padStart(5, '0'); // '00042'
```

### Pad End

Pads the string to the specified length with the given pad string at the end.

```php
$str = Str::of('Hello');
$padded = $str->padEnd(10, '.'); // 'Hello.....'
```

### Trim

Removes whitespace from both ends of the string.

```php
$str = Str::of('  Hello World  ');
$trimmed = $str->trim(); // 'Hello World'
```

### Trim Start

Removes whitespace from the start of the string.

```php
$str = Str::of('  Hello World  ');
$trimmed = $str->trimStart(); // 'Hello World  '
```

### Trim End

Removes whitespace from the end of the string.

```php
$str = Str::of('  Hello World  ');
$trimmed = $str->trimEnd(); // '  Hello World'
```

## Splitting

### Split

Splits the string into a Sequence of Str instances using the given delimiter.

```php
$str = Str::of('apple,banana,cherry');
$parts = $str->split(','); // Sequence of Str with ['apple', 'banana', 'cherry']

$str = Str::of('Hello');
$chars = $str->split(''); // Sequence of Str with ['H', 'e', 'l', 'l', 'o']
```

### Split At

Splits the string into two Str instances at the given index.

```php
$str = Str::of('Hello World');
$parts = $str->splitAt(5); // Sequence of Str with ['Hello', ' World']
```

### Split Whitespace

Splits the string into a Sequence of Str instances at whitespace characters.

```php
$str = Str::of('Hello World  Test');
$words = $str->splitWhitespace(); // Sequence of Str with ['Hello', 'World', 'Test']
```

## Case Conversion

### Strip Prefix

Removes the given prefix from the beginning of the string if it exists.

```php
$str = Str::of('HelloWorld');
$stripped = $str->stripPrefix('Hello'); // 'World'
$unchanged = $str->stripPrefix('World'); // 'HelloWorld' (unchanged)
```

### Strip Suffix

Removes the given suffix from the end of the string if it exists.

```php
$str = Str::of('HelloWorld');
$stripped = $str->stripSuffix('World'); // 'Hello'
$unchanged = $str->stripSuffix('Hello'); // 'HelloWorld' (unchanged)
```

## Parsing

### Parse Integer

Parses the string as an integer.

```php
$str = Str::of('123');
$result = $str->parseInteger(); // Result::ok(Integer::of(123))

$str = Str::of('abc');
$result = $str->parseInteger(); // Result::err(...)
```

### Parse Double

Parses the string as a floating-point number.

```php
$str = Str::of('3.14');
$result = $str->parseDouble(); // Result::ok(Double::of(3.14))

$str = Str::of('abc');
$result = $str->parseDouble(); // Result::err(...)
```

### Parse Bool

Parses the string as a boolean value.

```php
$str = Str::of('true');
$result = $str->parseBool(); // Result::ok(true)

$str = Str::of('1');
$result = $str->parseBool(); // Result::ok(true)

$str = Str::of('invalid');
$result = $str->parseBool(); // Result::err(...)
```

## UTF-8 Handling

### Is Valid UTF-8

Checks if the string is valid UTF-8.

```php
$str = Str::of('Hello');
$isValid = $str->isValidUtf8(); // true
```

### Is ASCII

Checks if the string contains only ASCII characters.

```php
$str = Str::of('Hello');
$isAscii = $str->isAscii(); // true

$str = Str::of('Café');
$isAscii = $str->isAscii(); // false
```

### Force UTF-8

Attempts to convert the string to valid UTF-8 encoding.

```php
$str = Str::of('Some text with encoding issues');
$result = $str->forceUtf8(); // Result::ok(Str) or Result::err(EncodingError)

// Specify source encoding
$result = $str->forceUtf8('ISO-8859-1'); // Result::ok(Str) or Result::err(EncodingError)
```

**Note:** This method performs aggressive encoding conversion and may replace invalid characters (with the UTF-8 replacement character).

## Wrapping

### Wrap

Wraps the string to a specified width.

```php
$str = Str::of('The quick brown fox jumps over the lazy dog');
$wrapped = $str->wrap(10);
// "The quick\nbrown fox\njumps over\nthe lazy\ndog"
```

**Note:** This performs smart word-wrapping to avoid breaking words.

## Error Handling

The Str class uses the Option and Result types for error handling:

- Methods that might return no value (like `get()` and `find()`) return an `Option<T>` type
- Parsing methods (like `parseInteger()`) return a `Result<T, E>` type
- Methods that modify the string return a new `Str` instance

This approach provides type-safe error handling without exceptions for most operations.
