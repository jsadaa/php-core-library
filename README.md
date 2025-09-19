# PHP Core Library

![pic2](./docs/images/pic.png)

> [!IMPORTANT]
> **Note:**
>
> This project is still under construction and very experimental.
> This is not yet ready for production use.

## Overview

PHP Core Library is a standard library ecosystem very loosely inspired by Rust, bringing functional programming patterns and safe abstractions to PHP.
This ecosystem provides a growing collection of types and modules that work together to enable more robust and expressive PHP development.

### Core Types

- `Vec`: An ordered collection of elements of the same type
- `Option`: A type that represents optional values (Some or None)
- `Result`: A type that represents either success (Ok) or failure (Err)
- `Str`: A UTF-8 string type with extensive manipulation methods
- `Integer`: An immutable wrapper around PHP integers with safe operations and overflow handling
- `Double`: An immutable wrapper around PHP floats with safe operations and precision handling

### Standard Library Modules

- **FileSystem**: Complete file and directory manipulation with type-safe error handling
- **Path**: Path manipulation and validation
- **Time**: Precise time handling with `SystemTime` and `Duration` types

> [!NOTE]
> **Expanding Ecosystem**: This is an active project under development. More types and modules are being added regularly to build a standard library for PHP. Additional modules for networking, collections, parsing, and more are planned.

Well, as silly as this project may look, it's just an attempt to provide some free implementations of Rust-like patterns to PHP to bring pleasure and joy to PHP developers. This project in no way claims to replicate the way Rust actually works in PHP, because well, it's PHP, but it does aim (or at least try 😅) to bring certain ways of programming to the table.

## Installation

(Not available yet)

```bash
composer require jsadaa/php-core-library
```

## Usage Examples

>See the [tests](tests) for more examples and edge cases.

### Vec (Vector)

A `Vec` is an ordered, immutable collection of elements of the same type that provides many functional operations:

```php
// Create a vector and apply operations
$vec = Vec::from(1, 2, 3, 4, 5);

// Map, filter, fold operations
$result = $vec
    ->map(fn($n) => $n * 2)                 // [2, 4, 6, 8, 10]
    ->filter(fn($n) => $n > 5)               // [6, 8, 10]
    ->fold(fn($acc, $n) => $acc + $n, 0);    // 24

// Get elements safely with Option type
$thirdItem = $vec
    ->get(2) // Option::some(3)
    ->match(
        fn($value) => "Found: $value",
        fn() => "Not found"
    ); // "Found: 3"

// ... And more
```

Vec provides many more powerful operations for working with collections, including:
- Creation methods: `from()`, `fromArray()`, `new()`
- Inspection: `all()`, `any()`, `contains()`, `isEmpty()`
- Transformation: `map()`, `filter()`, `flatMap()`, `flatten()`
- Combination: `append()`, `zip()`, `push()`
- Advanced operations: `windows()`, `dedup()`, `sortBy()`

For complete documentation with examples, see [Vec Documentation](./docs/vec.md).

### Option Type

The `Option` type represents an optional value that can be either `Some(value)` or `None`. It's used to handle the absence of values without using null.

```php
// Create Options
$some = Option::some(42);        // Some value
$none = Option::none();          // No value

// Check and extract value
$isSome = $some->isSome();       // true
$value = $some->unwrap();        // 42 (throws exception if None)
$safeValue = $none->unwrapOr(0); // 0 (default value if None)

// Pattern matching
$result = $some->match(
    fn($value) => "Got value: $value",  // Called if Some
    fn() => "No value present"          // Called if None
); // "Got value: 42"

// Transformations
$mapped = $some->map(fn($x) => $x * 2);           // Some(84)
$filtered = $some->filter(fn($x) => $x % 2 === 0); // Some(42) - condition met

// ... And more
```

The Option type provides a safe and expressive way to handle optional values:

- Eliminates null reference errors by forcing explicit handling of absence
- Enables fluent method chaining for transforming optional values
- Integrates well with other types in the library

For complete documentation with examples, see [Option Documentation](./docs/option.md).

### Result Type

The `Result` type represents either success (`Ok<T>`) or failure (`Err<E>`). It's used to handle operations that might fail in a type-safe way without exceptions.

```php
// Create Results
$ok = Result::ok(42);            // Success with value 42
$err = Result::err("Not found"); // Error with message

// Check variants and extract values
$isOk = $ok->isOk();             // true
$value = $ok->unwrap();          // 42 (throws exception if Err)
$safeValue = $err->unwrapOr(0);  // 0 (default if Err)

// Pattern matching
$result = $ok->match(
    fn($value) => "Success: $value",  // Called if Ok
    fn($error) => "Error: $error"     // Called if Err
); // "Success: 42"

// Transformations
$mapped = $ok->map(fn($x) => $x * 2);  // Ok(84) - only transforms Ok values

// ... And more
```

The Result type provides explicit and type-safe error handling:

- Makes success and error paths explicit in your code
- Enables composition of operations that might fail
- Forces comprehensive error handling at compile time
- Provides rich methods for working with potentially failed operations

For complete documentation with examples, see [Result Documentation](./docs/result.md).

### Str Type

The `Str` type provides immutable UTF-8 string operations with proper multi-byte character handling:

```php
// Create string instances
$str = Str::from('Hello World');
$empty = Str::new();

// Character access and content checks
$len = $str->len();                   // 11 (byte count for ASCII)
$first = $str->get(0);                // Option::some('H')
$contains = $str->contains('World');  // true
$starts = $str->startsWith('Hello');  // true

// String manipulation
$lowercase = $str->toLowercase();        // "hello world"
$replaced = $str->replace(Str::from('World'), Str::from('PHP')); // "Hello PHP"
$trimmed = Str::from('  Hello  ')->trim(); // "Hello"
$padded = $str->padStart(15, '-');       // "----Hello World"
$modified = $str->insertAt(5, Str::from(', ')); // "Hello, World"
$beginning = $str->take(5);              // "Hello"
$end = $str->drop(6);                    // "World"

// String splitting
$parts = $str->split(' ');               // Vec containing ["Hello", "World"]
$words = $str->splitWhitespace();        // Vec of words
$chars = $str->chars();                  // Vec containing ['H','e','l','l','o',...]

// Parsing to other types
$number = Str::from('42')->parseInt();    // Result::ok(42)
$float = Str::from('3.14')->parseFloat(); // Result::ok(3.14)
$bool = Str::from('true')->parseBool();   // Result::ok(true)

// ... And more
```

`Str` provides many more powerful operations for working with text, including:
- UTF-8 handling: byte counting, substring extraction, normalization, and encoding support
- Text transformation: case conversion, padding, trimming, replacement
- Text analysis: matching, finding, splitting in various ways
- Safety: immutable operations prevent accidental string mutations

For complete documentation with examples, see [Str Documentation](./docs/str.md).

### Double Type

The `Double` type provides an immutable wrapper around PHP floating-point numbers with mathematical operations:

```php
// Create Double instances
$double = Double::from(42.5);
$fromInt = Double::from(42);  // Convert int to double
$special = Double::pi();      // Constants like π, e, etc.

// Basic arithmetic operations
$sum = $double->add(10.5);          // Double::from(53.0)
$diff = $double->sub(5.5);          // Double::from(37.0)
$product = $double->mul(2.0);       // Double::from(85.0)
$quotient = $double->div(2.0);      // Result::ok(Double::from(21.25))

// Mathematical functions
$sqrt = Double::from(16.0)->sqrt();                 // Double::from(4.0)
$exp = Double::from(1.0)->exp();                    // Double::from(2.718...)
$log = Double::from(Math.E)->ln();                  // Double::from(1.0) - natural log

// Trigonometric functions
$sin = Double::from(Math.PI / 2)->sin();            // Double::from(1.0)
$cos = Double::from(0.0)->cos();                    // Double::from(1.0)
$tan = Double::from(Math.PI / 4)->tan();            // Double::from(1.0)

// Rounding and special operations
$rounded = Double::from(3.7)->round();              // Integer::from(4)
$isFinite = Double::from(42.5)->isFinite();         // true
$approxEqual = $double->approxEq(42.500000001);     // true - within epsilon

// ... And more
```

The `Double` type provides an immutable wrapper around floating-point numbers with mathematical capabilities:

- Complete set of arithmetic operations with proper error handling
- Extensive mathematical functions (trigonometric, logarithmic, exponential)
- Special value handling (NaN, infinity) and approximate equality comparisons
- Constants, rounding functions, and unit conversions

For complete documentation with examples, see [Double Documentation](./docs/double.md).

### Integer Type

The `Integer` type provides an immutable wrapper around PHP integers with safe arithmetic operations and overflow handling:

```php
// Create Integer instances
$int = Integer::from(42);

// Basic arithmetic operations
$sum = $int->add(10);               // Integer::from(52)
$diff = $int->sub(5);               // Integer::from(37)
$product = $int->mul(2);            // Integer::from(84)
$quotient = $int->div(2);           // Result::ok(Integer::from(21))

// Handling integer overflow
$max = Integer::from(PHP_INT_MAX);
$overflow = $max->overflowingAdd(1);     // Result::err(new \OverflowException('Integer overflow'))

// Saturating operations (clamp at min/max integer bounds)
$saturated = $max->saturatingAdd(1);     // Integer::from(PHP_INT_MAX) - doesn't overflow
$saturatedSub = Integer::from(PHP_INT_MIN)->saturatingSub(1); // Integer::from(PHP_INT_MIN)
$saturatedDiv = $int->saturatingDiv(0);   // Result::err(new \InvalidArgumentException('Division by zero'))

// Math operations
$absolute = Integer::from(-5)->abs();              // Integer::from(5)
$absDiff = Integer::from(10)->absDiff(7);          // Integer::from(3)
$absDiff = Integer::from(-5)->absDiff(-10);        // Integer::from(5)
$power = Integer::from(2)->pow(3);                 // Integer::from(8)
$sqrt = Integer::from(16)->sqrt();                 // Integer::from(4)

// Basic arithmetic
$sum = Integer::from(5)->add(3);                   // Integer::from(8)
$difference = Integer::from(10)->sub(4);           // Integer::from(6)
$product = Integer::from(6)->mul(7);               // Integer::from(42)
$quotient = Integer::from(10)->div(2);             // Result::ok(Integer::from(5))

// Mathematical operations
$abs = Integer::from(-5)->abs();                   // Integer::from(5)
$power = Integer::from(2)->pow(3);                 // Integer::from(8)
$sqrt = Integer::from(16)->sqrt();                 // Integer::from(4)

// Bitwise operations
$andResult = Integer::from(10)->and(6);            // Integer::from(2) - (1010 & 0110 = 0010)
$leftShift = Integer::from(5)->leftShift(1);       // Integer::from(10) - (101 << 1 = 1010)

// ... And more
```

The `Integer` type provides safe and predictable arithmetic operations with comprehensive error handling. Key features include:

- Arithmetic operations that explicitly handle errors through Result types
- Multiple arithmetic modes: standard, overflowing (with error reporting), and saturating
- Comprehensive mathematical functions and bitwise operations
- Immutable design that prevents unexpected side effects

For complete documentation with examples, see [Integer Documentation](./docs/integer.md).

### FileSystem Module

The FileSystem module provides file and directory operations with type-safe error handling, supporting both high-level operations and fine-grained file manipulation:

```php
// File operations with proper error handling
$result = FileSystem::read('/etc/hosts');
if ($result->isOk()) {
    $content = $result->unwrap();
    echo $content->toString();
}

// Directory operations
$entries = FileSystem::readDir('/var/log')->unwrap();
$logFiles = $entries->filter(fn($entry) => $entry->fileName()->unwrapOr(Str::from(''))->endsWith('.log'));

// Atomic file operations
$file = File::from('/path/to/config.json')->unwrap();
$file->writeAtomic($newConfig, true)->unwrap(); // Atomic write with sync

// File metadata and permissions
$metadata = $file->metadata()->unwrap();
if ($metadata->isWritable()) {
    echo "File is writable";
}
```

The FileSystem module includes:
- `File`: Immutable file handle with read, write, and metadata operations
- `FileSystem`: Static methods for common filesystem operations
- `DirectoryEntry`: Represents files and directories in directory listings
- `Metadata`: File metadata including size, timestamps, and permissions
- `Permissions`: Type-safe permission management
- Comprehensive error types for different failure modes

For complete documentation with examples, see [File Documentation](./docs/filesystem.md).

### Path Module

The Path module provides cross-platform path manipulation and validation:

```php
// Path creation and manipulation
$path = Path::from('/var/www/html/index.php');
$parent = $path->parent(); // Option<Path>
$fileName = $path->fileName(); // Option<Str>
$extension = $path->extension(); // Option<Str>

// Path joining and validation
$basePath = Path::from('/var/www');
$fullPath = $basePath->join(Path::from('uploads/image.jpg'));

// Path inspection
if ($path->isAbsolute()) {
    echo "This is an absolute path";
}

// Canonicalization (resolves symlinks and . / ..)
$canonical = $path->canonicalize()->unwrap();
```

> [!NOTE]
> **Documentation**: Detailed documentation for these modules is in development. For now, you can explore the examples in the [tests](tests) directory or read the inline documentation in the source code classes.

### Time Module

The Time module provides precise time handling with nanosecond precision:

```php
// System time operations
$now = SystemTime::now();
$timestamp = $now->timestamp(); // Integer (seconds since Unix epoch)

// Duration arithmetic
$oneHour = Duration::fromHours(1);
$futureTime = $now->add($oneHour)->unwrap();

// Measuring elapsed time
$start = SystemTime::now();
// ... some operation ...
$elapsed = SystemTime::now()->durationSince($start)->unwrap();

// High precision duration operations
$precise = Duration::new(1, 500_000_000); // 1.5 seconds
$doubled = $precise->mul(2)->unwrap(); // 3 seconds

// Converting between time representations
$dateTime = $now->toDateTimeImmutable()->unwrap();
$backToSystemTime = SystemTime::fromDateTimeImmutable($dateTime)->unwrap();
```

> [!NOTE]
> **Documentation**: Detailed documentation for these modules is in development. For now, you can explore the examples in the [tests](tests) directory or read the inline documentation in the source code classes.

## Type Safety

> **Important:** Type safety is enforced **only through static analysis**. There are no runtime type checks in this library.

This library uses PHPDoc annotations to provide type information that can be verified by static analysis tools. To ensure type safety, it is **strongly recommended** to use a static analyzer like Psalm.

Without static analysis, you lose most of the type safety benefits of this library.

For example, with the Vec collection, which is an ordered list of elements of the same type, nothing technically prevents you from adding mixed types to the collection, but most of the Vec APIs will not work as expected and might throw exceptions at runtime.

```php
$vec = Vec::from(1, 2, 3)->push('string');
$vec->map(fn($n) => $n * 2); // Uncaught TypeError: Unsupported operand types: string * int
```
This enforces you to really think about your implementation and the types you are using.

## Design Philosophy

- Type safety through static analysis (no runtime type checking)
- Immutable data structures with method chaining
- Functional programming patterns
- Error handling with Option and Result types instead of exceptions or nulls

### Why Immutability? — Divergences from Rust and the PHP Context

Unlike Rust, where **mutability is explicit** (`mut`) and tightly enforced by the ownership system, PHP operates with **mutable objects by default** and implicit reference semantics. This fundamental difference is the main reason why all types in this library are implemented as immutable structures.

### Why Immutability Makes Sense in PHP

- **Avoiding Side Effects**
In PHP, objects are passed by reference, so mutations can easily propagate unexpectedly across different parts of an application. For example, modifying a `Vec` or `Str` in one place could unintentionally affect other references to the same object. Immutability eliminates this risk by ensuring every operation returns a new instance, making code more predictable and robust.
- **No Native Mutability Controls**
Rust gives you fine-grained control over mutability and ownership (`&mut`, borrow checker), ensuring thread safety and preventing data races at compile time. PHP lacks these mechanisms. By enforcing immutability at the API level, this library provides a similar guarantee—purely through convention and design—mirroring patterns seen in PHP’s own `DateTimeImmutable`.
- **Memory and Lifecycle Management**
Rust can optimize in-place mutations thanks to its allocator and ownership model, minimizing allocations and copies. PHP relies on a garbage collector and reference counting, so creating new immutable objects is less problematic and often leads to clearer, safer code.


### Impact on the API — Key Divergences from Rust

This design choice leads to some important differences from Rust’s original APIs:


| Operation | Rust (Mutable) | PHP Core Library (Immutable)  |
| :-- | :-- |:------------------------------|
| Add element to Vec | `vec.push(item)` (in-place) | `$vec->push($item)` (new Vec) |
| String concatenation | `s1.push_str(&s2)` | `$s1->append($s2)` (new Str)  |

**Example with `Vec::map`:**

```php
// PHP: Immutable chaining
$result = $vec->map(fn($x) => $x * 2)->filter(...);

// Rust: Mutable iterators
let result: Vec<_> = vec.iter().map(|x| x * 2).filter(...).collect();
```

### Performance and Practical Trade-offs

Immutability can introduce a **memory and performance cost** (more frequent copies), but this is mitigated by:

- **Lazy copying** (copying only on modification)
- **Structural sharing** (reusing unchanged parts internally)
- The fact that, in PHP, clarity and safety often outweigh micro-optimizations

### Adapting to PHP Idioms

To remain idiomatic, some Rust APIs have been adapted:

- **Immutable Method chaining** is favored over in-place mutation
- **Fluent interfaces** inspired by the Builder pattern rather than Rust traits

**Example adaptation:**

```php
// Instead of mutating a String as in Rust:
let mut s = String::from("Hello");
s.push_str(" World");

// PHP Core Library uses immutability:
$s = Str::from("Hello");
$s = $s->append(Str::from(" World"));
```

This approach reflects a **hybrid** between Rust’s conceptual strengths and PHP’s practical realities, bringing the benefits of functional programming and data safety to a language not originally designed with these patterns in mind.

## Requirements

- PHP 8.3 or higher
- PHP Extensions:
  - `ext-mbstring`: For proper UTF-8 string handling (required for `Str` type)
  - `ext-intl`: For Unicode normalization and other internationalization features (required for `Str` type)
  - `ext-iconv`: For character encoding conversion (required for `Str` type)

## Standard Library Architecture

This library is designed as a cohesive ecosystem where modules complement each other:

- **Core Types** (`Vec`, `Option`, `Result`, `Str`, `Integer`, `Double`) provide the foundation
- **FileSystem** uses `Path`, `Result`, and core types for safe file operations
- **Path** integrates with `Option` and `Result` for path validation and manipulation
- **Time** provides `SystemTime` and `Duration` with overflow-safe arithmetic using `Integer`

All modules follow consistent patterns for error handling, immutability, and functional composition, making them work naturally together while remaining useful independently.

## Acknowledgments

Besides Rust, this project is also greatly inspired by the work of [Baptiste Langlade](https://github.com/baptouuuu) through his organization [Innmind](https://github.com/Innmind), which has pioneered bringing functional programming patterns and immutable data structures to PHP.

## Contributing

Contributions are welcome! This is an experimental project aiming to bring Rust-like patterns to PHP.

## License

MIT
