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

- `Sequence`: An ordered collection of elements of the same type
- `Map`: A key-value collection with O(1) lookups, supporting both scalar and object keys
- `Set`: A collection of unique values with mathematical set operations
- `Option`: A type that represents optional values (Some or None)
- `Result`: A type that represents either success (Ok) or failure (Err)
- `Str`: A UTF-8 string type with extensive manipulation methods
- `Char`: An immutable Unicode character type with classification and conversion operations
- `Integer`: An immutable wrapper around PHP integers with safe operations and overflow handling
- `Double`: An immutable wrapper around PHP floats with safe operations and precision handling

### Standard Library Modules

- **FileSystem**: Complete file and directory manipulation with type-safe error handling
- **Process**: Safe process spawning, pipeline execution, and stream I/O with typed errors
- **Json**: Safe JSON encoding, decoding, and validation with `Result` types
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

### Sequence

A `Sequence` is an ordered, immutable collection of elements of the same type that provides many functional operations:

```php
// Create a Sequence and apply operations
$seq = Sequence::of(1, 2, 3, 4, 5);

// Map, filter, fold operations
$result = $seq
    ->map(fn($n) => $n * 2)                 // [2, 4, 6, 8, 10]
    ->filter(fn($n) => $n > 5)               // [6, 8, 10]
    ->fold(fn($acc, $n) => $acc + $n, 0);    // 24

// Get elements safely with Option type
$thirdItem = $seq
    ->get(2) // Option::some(3)
    ->match(
        fn($value) => "Found: $value",
        fn() => "Not found"
    ); // "Found: 3"

// andThen chaining on Option — get, find, first, last all return Option
$result = $seq
    ->find(fn($n) => $n > 3)                             // Option::some(4)
    ->andThen(fn(int $n) => $seq->get($n))               // Option::some(5) — use 4 as index
    ->map(fn(int $n) => $n * 10);                        // Option::some(50)

// first/last return Option — chain safely
$doubled = $seq->first()                                 // Option::some(1)
    ->map(fn(int $n) => $n * 2);                         // Option::some(2)

$doubled = Sequence::ofArray([])->first()                // Option::none()
    ->map(fn(int $n) => $n * 2);                         // Option::none() — never called

// ... And more
```

Sequence provides many more powerful operations for working with collections, including:
- Creation methods: `from()`, `fromArray()`, `new()`
- Inspection: `all()`, `any()`, `contains()`, `isEmpty()`
- Transformation: `map()`, `filter()`, `flatMap()`, `flatten()`
- Combination: `append()`, `zip()`, `push()`
- Advanced operations: `windows()`, `unique()`, `sortBy()`

For complete documentation with examples, see [Sequence Documentation](./docs/sequence.md).

### Map

A `Map` is an immutable, homogeneous key-value collection with O(1) lookups, supporting both scalar and object keys. All values must be of the same type `V`:

```php
// Create and manipulate a Map (Map<string, int>)
$scores = Map::of('Alice', 95)
    ->add('Bob', 78)
    ->add('Charlie', 92);

// Safe access with Option
$alice = $scores->get('Alice')->unwrapOr(0);   // 95
$eve = $scores->get('Eve')->unwrapOr(0);       // 0 (default)

// Transform, filter, fold
$curved = $scores
    ->filter(fn($name, $score) => $score >= 80)
    ->map(fn($name, $score) => $score + 5);

// Merge maps
$defaults = Map::of('Alice', 90)->add('Bob', 75);
$updates = Map::of('Bob', 82)->add('Diana', 95);
$merged = $defaults->append($updates); // updates take precedence

// Object keys with identity comparison
$user = new User('Alice');
$roles = Map::of($user, 'admin');
$roles->get($user)->unwrap(); // 'admin'
```

Map provides many operations for working with key-value data, including:
- Creation methods: `of()`, `fromKeys()`, `new()`
- Inspection: `containsKey()`, `containsValue()`, `isEmpty()`
- Access: `get()`, `find()`, `keys()`, `values()`
- Transformation: `map()`, `filter()`, `flatMap()`, `fold()`
- Combination: `append()`

For complete documentation with examples, see [Map Documentation](./docs/map.md).

### Set

A `Set` is an immutable collection of unique values with mathematical set operations:

```php
// Create a Set (duplicates are removed)
$languages = Set::of('PHP', 'Rust', 'Go', 'PHP');
$languages->size(); // Integer::of(3)

// Set operations
$backend = Set::of('PHP', 'Rust', 'Go', 'Java');
$systems = Set::of('Rust', 'C', 'Go', 'Zig');

$common = $backend->intersection($systems);   // Set { 'Rust', 'Go' }
$onlyBackend = $backend->difference($systems); // Set { 'PHP', 'Java' }
$all = $backend->append($systems);             // Union of both

// Functional operations
$lengths = $languages->map(fn($lang) => Str::of($lang)->size()->toInt()); // Set { 3, 4, 2 }
$short = $languages->filter(fn($lang) => Str::of($lang)->size()->le(3)); // Set { 'PHP', 'Go' }
$hasRust = $languages->contains('Rust'); // true

// Subset/superset checks
$small = Set::of('PHP', 'Rust');
$small->isSubset($backend); // true
```

Set provides many operations for working with unique collections, including:
- Creation methods: `of()`, `ofArray()`
- Set operations: `intersection()`, `difference()`, `append()`, `isSubset()`, `isSuperset()`, `isDisjoint()`
- Inspection: `contains()`, `any()`, `all()`, `isEmpty()`
- Transformation: `map()`, `filter()`, `flatMap()`, `filterMap()`, `fold()`
- Conversion: `toArray()`, `toSequence()`

For complete documentation with examples, see [Set Documentation](./docs/set.md).

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

// Pattern matching — exhaustive handling of both cases
$result = $some->match(
    fn($value) => "Got value: $value",  // Called if Some
    fn() => "No value present"          // Called if None
); // "Got value: 42"

// map — transform the inner value (stays None if None)
$mapped = $some->map(fn($x) => $x * 2);  // Some(84)
$mapped = $none->map(fn($x) => $x * 2);  // None — callback never called

// filter — keep Some only if predicate passes
$filtered = $some->filter(fn($x) => $x % 2 === 0); // Some(42) — 42 is even
$filtered = $some->filter(fn($x) => $x > 100);     // None — predicate fails
```

#### `andThen()` — monadic chaining for operations that return Option

The key method for composing operations that themselves return `Option`. Unlike `map()` which wraps the result in `Some`, `andThen()` expects the callback to return an `Option` — preventing nested `Option<Option<T>>`.

```php
// andThen chains operations that return Option
$seq = Sequence::of(10, 20, 30);

$result = $seq->get(1)                                  // Option::some(20)
    ->andThen(fn($val) => $val > 10                     // Chain only if Some
        ? Option::some($val * 2)
        : Option::none()
    );                                                   // Option::some(40)

// andThen propagates None automatically — short-circuit
$result = $seq->get(99)                                  // Option::none()
    ->andThen(fn($val) => Option::some($val * 2));       // Option::none() — never called

// Real-world: safe nested access on a Path
$extension = Path::of('/var/www/app/config.json')
    ->parent()                                           // Option::some(Path('/var/www/app'))
    ->andThen(fn(Path $p) => $p->parent())               // Option::some(Path('/var/www'))
    ->andThen(fn(Path $p) => $p->fileName())             // Option::some(Str('www'))
    ->map(fn(Str $name) => $name->toUppercase());        // Option::some(Str('WWW'))

// Chaining with Sequence::find and Str::find
$users = Sequence::of('alice@example.com', 'bob@test.org', 'carol@example.com');
$domain = $users
    ->find(fn(string $email) => str_contains($email, 'bob'))  // Option::some('bob@test.org')
    ->map(fn(string $email) => Str::of($email))               // Option::some(Str('bob@test.org'))
    ->andThen(fn(Str $s) => $s->find('@'))                    // Option::some(Integer(3))
    ->map(fn(Integer $pos) => $pos->toInt());                 // Option::some(3)

// Map::get returns Option — chain lookups across maps
$users = Map::of('alice', 42)->add('bob', 38);
$scores = Map::of(42, 'A+')->add(38, 'B');

$grade = $users->get('alice')                            // Option::some(42)
    ->andThen(fn(int $id) => $scores->get($id));         // Option::some('A+')

$grade = $users->get('unknown')                          // Option::none()
    ->andThen(fn(int $id) => $scores->get($id));         // Option::none() — short-circuit
```

#### Other useful methods

```php
// orElse — provide a fallback Option when None
$config = Map::of('port', '8080');
$port = $config->get('port')                             // Option::some('8080')
    ->orElse(fn() => Option::some('3000'));               // Option::some('8080') — not called

$port = $config->get('missing')                          // Option::none()
    ->orElse(fn() => Option::some('3000'));               // Option::some('3000') — fallback

// or — simpler version with a static fallback
$value = Option::none()->or(Option::some('default'));    // Option::some('default')

// okOr — convert Option to Result (bridge between the two monads)
$result = $seq->get(1)->okOr(new \RuntimeException('Index out of bounds'));
// Some(20) becomes Result::ok(20), None becomes Result::err(RuntimeException)

// inspect — side effect without altering the chain (useful for logging/debugging)
$value = $seq->get(0)
    ->inspect(fn($v) => error_log("Found value: $v"))    // Logs "Found value: 10"
    ->map(fn($v) => $v * 2);                             // Option::some(20)

// flatten — unwrap nested Option<Option<T>>
$nested = Option::some(Option::some(42));
$flat = $nested->flatten();                              // Option::some(42)

// isSomeAnd — check presence AND condition in one call
$isPositive = $some->isSomeAnd(fn($x) => $x > 0);      // true
$isPositive = $none->isSomeAnd(fn($x) => $x > 0);      // false — None
```

The Option type provides a safe and expressive way to handle optional values:

- Eliminates null reference errors by forcing explicit handling of absence
- Enables monadic chaining with `andThen()` for composing operations that return Options
- Bridges to `Result` via `okOr()` / `okOrElse()` for error contexts
- Integrates with all library types (`Sequence::get`, `Map::get`, `Path::parent`, `Str::find`, etc.)

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

// Pattern matching — exhaustive handling of both cases
$result = $ok->match(
    fn($value) => "Success: $value",  // Called if Ok
    fn($error) => "Error: $error"     // Called if Err
); // "Success: 42"

// map — transform the Ok value (stays Err if Err)
$mapped = $ok->map(fn($x) => $x * 2);   // Ok(84)
$mapped = $err->map(fn($x) => $x * 2);  // Err("Not found") — callback never called
```

#### `andThen()` — railway-oriented programming

The key method for composing operations that themselves return `Result`. Each step only executes if the previous one succeeded. Once an `Err` appears, all subsequent steps are skipped and the error propagates.

```php
// Basic chaining — short-circuit on first error
$result = Integer::of(10)
    ->div(2)                                             // Result::ok(Integer::of(5))
    ->andThen(fn(Integer $val) => $val->div(0));         // Result::err(DivisionByZero)
    // div(0) fails => the chain stops here

// Validate and transform user input — each step can fail independently
$result = Str::of('42')
    ->parseInteger()                                     // Result::ok(Integer::of(42))
    ->andThen(fn(Integer $n) => $n->gt(0)
        ? Result::ok($n)
        : Result::err('Must be positive')
    )
    ->andThen(fn(Integer $n) => $n->lt(100)
        ? Result::ok($n)
        : Result::err('Must be less than 100')
    );                                                   // Result::ok(Integer::of(42))

// FileSystem: read, parse, and validate a config file
$result = FileSystem::read('/path/to/config.json')       // Result<Str, FileNotFound|...>
    ->map(fn(Str $content) => $content->toString())      // Result<string, ...>
    ->andThen(fn(string $json) => Json::decode($json))   // Result<array, DecodingError>
    ->map(fn(array $config) => $config['port'] ?? 3000); // Result<int, ...>

// Process: run a command and parse its output
$result = Command::of('git')
    ->withArg('rev-parse')->withArg('HEAD')
    ->output()                                           // Result<Output, ...>
    ->map(fn(Output $o) => $o->stdout())                 // Result<Str, ...>
    ->map(fn(Str $s) => $s->trim());                     // Result<Str, ...>

// Chaining across modules — read file, decode JSON, extract a value
$dbHost = FileSystem::read('/etc/app/db.json')
    ->andThen(fn(Str $s) => Json::decode($s->toString()))
    ->map(fn(array $c) => $c['database']['host'] ?? 'localhost');

$dbHost->match(
    fn(string $host) => "Connecting to $host",
    fn($error) => "Config error: " . $error->getMessage(),
);
```

#### Other useful methods

```php
// mapErr — transform the error without touching the Ok value
$result = Str::of('not a number')
    ->parseInteger()                                     // Result::err(ParseError)
    ->mapErr(fn($e) => new \RuntimeException(            // Result::err(RuntimeException)
        "Invalid input: " . $e->getMessage()
    ));

// orElse — try an alternative on error
$config = FileSystem::read('/etc/app/config.json')
    ->orElse(fn($e) => FileSystem::read('/etc/app/config.default.json'))
    ->orElse(fn($e) => Result::ok(Str::of('{}')));      // Ultimate fallback

// option() — convert Result to Option (bridge between the two monads)
$maybeContent = FileSystem::read('/optional/file.txt')
    ->option();                                          // Ok(x) => Some(x), Err(_) => None

// inspect / inspectErr — side effects without altering the chain (logging, debugging)
$result = FileSystem::read('/path/to/file.txt')
    ->inspect(fn(Str $s) => error_log("Read " . $s->size()->toInt() . " chars"))
    ->inspectErr(fn($e) => error_log("Read failed: " . $e->getMessage()))
    ->map(fn(Str $s) => $s->toUppercase());

// flatten — unwrap nested Result<Result<T, E>, E>
$nested = Result::ok(Result::ok(42));
$flat = $nested->flatten();                              // Result::ok(42)

// isOkAnd / isErrAnd — check variant AND condition
$isPositive = Integer::of(10)->div(2)
    ->isOkAnd(fn(Integer $n) => $n->gt(0));              // true
```

The Result type provides explicit and type-safe error handling:

- Enables railway-oriented programming with `andThen()` for composing failable operations
- Bridges to `Option` via `option()` for contexts where the error doesn't matter
- Transforms errors with `mapErr()` for error normalization across module boundaries
- Falls back with `orElse()` for retry / default strategies
- Integrates with all library modules (`FileSystem`, `Json`, `Process`, `Integer::div`, `Str::parseInteger`, etc.)

For complete documentation with examples, see [Result Documentation](./docs/result.md).

### Str Type

The `Str` type provides immutable UTF-8 string operations with proper multi-byte character handling:

```php
// Create string instances
$str = Str::of('Hello World');
$empty = Str::new();

// Character access and content checks
$len = $str->size();                   // 11 (character count)
$first = $str->get(0);                // Option::some('H')
$contains = $str->contains('World');  // true
$starts = $str->startsWith('Hello');  // true

// String manipulation
$lowercase = $str->toLowercase();        // "hello world"
$replaced = $str->replace(Str::of('World'), Str::of('PHP')); // "Hello PHP"
$trimmed = Str::of('  Hello  ')->trim(); // "Hello"
$padded = $str->padStart(15, '-');       // "----Hello World"
$modified = $str->insertAt(5, Str::of(', ')); // "Hello, World"
$beginning = $str->take(5);              // "Hello"
$end = $str->drop(6);                    // "World"

// String splitting
$parts = $str->split(' ');               // Sequence containing ["Hello", "World"]
$words = $str->splitWhitespace();        // Sequence of words
$chars = $str->chars();                  // Sequence containing ['H','e','l','l','o',...]

// Parsing to other types — returns Result for safe error handling
$number = Str::of('42')->parseInteger();    // Result::ok(Integer::of(42))
$float = Str::of('3.14')->parseDouble();    // Result::ok(Double::of(3.14))
$bool = Str::of('true')->parseBool();       // Result::ok(true)

// andThen chaining on parse results
$result = Str::of('  42  ')
    ->trim()                                             // Str('42')
    ->parseInteger()                                     // Result::ok(Integer::of(42))
    ->andThen(fn(Integer $n) => $n->mul(2)->div(3));     // Result::ok(Integer::of(28))

// find returns Option — chain with andThen
$atPos = Str::of('user@example.com')
    ->find('@')                                          // Option::some(Integer(4))
    ->map(fn(Integer $pos) => $pos->toInt());            // Option::some(4)

// get returns Option — chain for safe character access
$initial = Str::of('Hello')
    ->get(0)                                             // Option::some(Char('H'))
    ->map(fn(Char $c) => $c->toLowercase());             // Option::some(Char('h'))

// ... And more
```

`Str` provides many more powerful operations for working with text, including:
- UTF-8 handling: byte counting, substring extraction, normalization, and encoding support
- Text transformation: case conversion, padding, trimming, replacement
- Text analysis: matching, finding, splitting in various ways
- `parseInteger()`, `parseDouble()`, `parseBool()` return `Result` for safe chaining with `andThen()`
- `find()`, `get()` return `Option` for safe chaining with `andThen()`

For complete documentation with examples, see [Str Documentation](./docs/str.md).

### Char Type

The `Char` type represents a single Unicode codepoint with Unicode-aware classification and conversion operations:

```php
// Create Char instances
$char = Char::of('A');
$digit = Char::ofDigit(5);    // Char '5'
$unicode = Char::of('é');

// Unicode-aware classification (powered by IntlChar)
$char->isAlphabetic();         // true
$char->isUppercase();          // true
$char->isAscii();              // true
Char::of('é')->isLowercase();  // true

// Case conversion (returns new Char instance)
$lower = Char::of('A')->toLowercase();  // Char 'a'
$upper = Char::of('é')->toUppercase();  // Char 'É'
```

`Char` provides:
- Unicode-aware classification: alphabetic, digit, whitespace, case, punctuation, and more
- Case conversion with full Unicode support
- Single codepoint validation (not grapheme clusters)
- Immutable design consistent with other library types

For complete documentation with examples, see [Char Documentation](./docs/char.md).

### Double Type

The `Double` type provides an immutable wrapper around PHP floating-point numbers with mathematical operations:

```php
// Create Double instances
$double = Double::of(42.5);
$fromInt = Double::of(42);  // Convert int to double
$special = Double::pi();      // Constants like π, e, etc.

// Basic arithmetic operations
$sum = $double->add(10.5);          // Double::of(53.0)
$diff = $double->sub(5.5);          // Double::of(37.0)
$product = $double->mul(2.0);       // Double::of(85.0)
$quotient = $double->div(2.0);      // Result::ok(Double::of(21.25))

// Mathematical functions
$sqrt = Double::of(16.0)->sqrt();                 // Double::of(4.0)
$exp = Double::of(1.0)->exp();                    // Double::of(2.718...)
$log = Double::of(Math.E)->ln();                  // Double::of(1.0) - natural log

// Trigonometric functions
$sin = Double::of(Math.PI / 2)->sin();            // Double::of(1.0)
$cos = Double::of(0.0)->cos();                    // Double::of(1.0)
$tan = Double::of(Math.PI / 4)->tan();            // Double::of(1.0)

// Rounding and special operations
$rounded = Double::of(3.7)->round();              // Integer::of(4)
$isFinite = Double::of(42.5)->isFinite();         // true
$approxEqual = $double->approxEq(42.500000001);     // true - within epsilon

// andThen chaining on arithmetic results — div() returns Result
$result = Double::of(100.0)
    ->div(3.0)                                           // Result::ok(Double::of(33.333...))
    ->andThen(fn(Double $d) => $d->div(0.0));            // Result::err(DivisionByZero)

// ... And more
```

The `Double` type provides an immutable wrapper around floating-point numbers with mathematical capabilities:

- `div()` returns `Result` for safe chaining with `andThen()`
- Extensive mathematical functions (trigonometric, logarithmic, exponential)
- Special value handling (NaN, infinity) and approximate equality comparisons
- Constants, rounding functions, and unit conversions

For complete documentation with examples, see [Double Documentation](./docs/double.md).

### Integer Type

The `Integer` type provides an immutable wrapper around PHP integers with safe arithmetic operations and overflow handling:

```php
// Create Integer instances
$int = Integer::of(42);

// Basic arithmetic operations
$sum = $int->add(10);               // Integer::of(52)
$diff = $int->sub(5);               // Integer::of(37)
$product = $int->mul(2);            // Integer::of(84)
$quotient = $int->div(2);           // Result::ok(Integer::of(21))

// Handling integer overflow
$max = Integer::of(PHP_INT_MAX);
$overflow = $max->overflowingAdd(1);     // Result::err(new \OverflowException('Integer overflow'))

// Saturating operations (clamp at min/max integer bounds)
$saturated = $max->saturatingAdd(1);     // Integer::of(PHP_INT_MAX) - doesn't overflow
$saturatedSub = Integer::of(PHP_INT_MIN)->saturatingSub(1); // Integer::of(PHP_INT_MIN)
$saturatedDiv = $int->saturatingDiv(0);   // Result::err(new \InvalidArgumentException('Division by zero'))

// Math operations
$absolute = Integer::of(-5)->abs();              // Integer::of(5)
$absDiff = Integer::of(10)->absDiff(7);          // Integer::of(3)
$absDiff = Integer::of(-5)->absDiff(-10);        // Integer::of(5)
$power = Integer::of(2)->pow(3);                 // Integer::of(8)
$sqrt = Integer::of(16)->sqrt();                 // Integer::of(4)

// Basic arithmetic
$sum = Integer::of(5)->add(3);                   // Integer::of(8)
$difference = Integer::of(10)->sub(4);           // Integer::of(6)
$product = Integer::of(6)->mul(7);               // Integer::of(42)
$quotient = Integer::of(10)->div(2);             // Result::ok(Integer::of(5))

// Mathematical operations
$abs = Integer::of(-5)->abs();                   // Integer::of(5)
$power = Integer::of(2)->pow(3);                 // Integer::of(8)
$sqrt = Integer::of(16)->sqrt();                 // Integer::of(4)

// Bitwise operations
$andResult = Integer::of(10)->and(6);            // Integer::of(2) - (1010 & 0110 = 0010)
$leftShift = Integer::of(5)->leftShift(1);       // Integer::of(10) - (101 << 1 = 1010)

// andThen chaining on arithmetic results — div() returns Result
$result = Integer::of(100)
    ->div(3)                                             // Result::ok(Integer::of(33))
    ->andThen(fn(Integer $n) => $n->div(2))              // Result::ok(Integer::of(16))
    ->andThen(fn(Integer $n) => $n->div(0));             // Result::err(DivisionByZero)
    // Short-circuit: the chain stops at the first error

// Combine with map for transformations that can't fail
$result = Integer::of(42)
    ->div(2)                                             // Result::ok(Integer::of(21))
    ->map(fn(Integer $n) => $n->mul(3))                  // Result::ok(Integer::of(63))
    ->map(fn(Integer $n) => $n->toInt());                // Result::ok(63)

// ... And more
```

The `Integer` type provides safe and predictable arithmetic operations with comprehensive error handling. Key features include:

- `div()` returns `Result` for safe chaining with `andThen()` on failable arithmetic
- Multiple arithmetic modes: standard, overflowing (with error reporting), and saturating
- Comprehensive mathematical functions and bitwise operations
- Immutable design that prevents unexpected side effects

For complete documentation with examples, see [Integer Documentation](./docs/integer.md).

### FileSystem Module

The FileSystem module separates one-shot operations (`FileSystem` static methods) from handle-based streaming (`File` class), mirroring the Rust `std::fs` / `std::fs::File` split:

```php
// One-shot operations via FileSystem
$content = FileSystem::read('/etc/hosts')->unwrap();
echo $content->toString();

// Handle-based streaming via File
$file = File::open('/path/to/large-file.csv')->unwrap();

while (true) {
    $line = $file->readLine()->unwrap();

    if ($line->isNone()) {
        break;
    }

    // Process line...
}

$file->close();

// Atomic writes for critical data
$file = File::open('/path/to/config.json')->unwrap();
$file->writeAtomic($newJson, sync: true)->unwrap();
$file->close();

// Scoped pattern: auto-close on exit
$result = File::withOpen('/path/to/data.txt', fn(File $f) => $f->readAll()->unwrap());

// Directory operations return Sequence<Path>
$entries = FileSystem::readDir('/var/log')->unwrap();
$logFiles = $entries->filter(fn(Path $entry) => $entry->isFile());

// andThen chaining — read, decode JSON, extract value in one pipeline
$dbConfig = FileSystem::read('/etc/app/config.json')         // Result<Str, ...>
    ->andThen(fn(Str $s) => Json::decode($s->toString()))    // Result<array, ...>
    ->map(fn(array $c) => $c['database'] ?? []);             // Result<array, ...>

// orElse — fallback to default config on error
$config = FileSystem::read('/app/config.json')
    ->orElse(fn($e) => FileSystem::read('/app/config.default.json'))
    ->orElse(fn($e) => Result::ok(Str::of('{}')));          // Ultimate fallback
```

The FileSystem module includes:
- `File`: Mutable handle-based file I/O (streaming, seeking, atomic writes)
- `FileSystem`: Static methods for one-shot filesystem operations
- `Metadata`: Immutable snapshot via `lstat()` (size, timestamps, permissions, file type)
- `FileType`: PHP native enum (`RegularFile`, `Directory`, `Symlink`)
- `Permissions`: Type-safe permission management
- `FileTimes`: Immutable builder for setting file timestamps
- Comprehensive error types for different failure modes

For complete documentation with examples, see [FileSystem Documentation](./docs/filesystem.md).

### Process Module

The Process module provides safe process spawning and execution, inspired by Rust's `std::process`. Commands are built immutably, shell interpretation is bypassed for security, and all I/O uses `Result` types with dedicated error classes:

```php
// Simple command execution
$result = Command::of('echo')
    ->withArg('hello')
    ->output();

echo $result->unwrap()->toString(); // "hello\n"

// Pipeline: connect commands with pipes
$result = Command::of('echo')
    ->withArg('banana apple cherry apple')
    ->pipe(Command::of('tr')->withArg(' ')->withArg("\n"))
    ->pipe(Command::of('sort'))
    ->pipe(Command::of('uniq'))
    ->output();

// "apple\nbanana\ncherry\n"

// Full control with ProcessBuilder
$process = ProcessBuilder::command('cat')
    ->workingDirectory('/tmp')
    ->env('LANG', 'en_US.UTF-8')
    ->spawn()
    ->unwrap();

$process->writeStdin('Hello from PHP');
$process->kill();
$process->close();

// andThen chaining — run a command and transform output
$branch = Command::of('git')
    ->withArg('rev-parse')->withArg('--abbrev-ref')->withArg('HEAD')
    ->output()                                           // Result<Output, ...>
    ->map(fn(Output $o) => $o->stdout())                 // Result<Str, ...>
    ->map(fn(Str $s) => $s->trim()->toString());         // Result<string, ...>

// mapErr — normalize errors from different operations
$result = Command::of('node')
    ->withArg('--version')
    ->output()
    ->mapErr(fn($e) => new \RuntimeException('Node.js not installed'));

// Typed error handling with match
$result = Command::of('sleep')
    ->withArg('60')
    ->withTimeout(Duration::fromMillis(100))
    ->run();

$result->match(
    fn(Output $output) => $output->stdout()->toString(),
    fn($error) => match (true) {
        $error instanceof ProcessTimeout => 'Timed out',
        $error instanceof Output => 'Failed: ' . $error->stderr()->toString(),
        default => $error->getMessage(),
    },
);
```

The Process module includes:
- `Command`: High-level fluent API with pipeline support and timeout
- `ProcessBuilder`: Low-level immutable builder with environment and stream control
- `Process`: Running process handle with stdin/stdout/stderr access
- `StreamReader` / `StreamWriter`: Non-blocking I/O with `stream_select()`
- `ProcessStreams` / `StreamDescriptor`: Configurable I/O (pipes, files, inherit, /dev/null)
- Typed error classes: `ProcessTimeout`, `ProcessSpawnFailed`, `InvalidCommand`, etc.

For complete documentation with examples, see [Process Documentation](./docs/process.md).

### Json Module

The Json module provides safe wrappers around PHP's native JSON functions, returning `Result` types instead of throwing exceptions:

```php
// Encode to string
$result = Json::encode(['name' => 'Alice', 'age' => 30]);
echo $result->unwrap(); // '{"name":"Alice","age":30}'

// Encode to Str type
$result = Json::encodeToStr(['key' => 'value']);
$json = $result->unwrap(); // Str instance
$json->contains('key');    // true

// Decode
$result = Json::decode('{"name":"Alice"}');
$data = $result->unwrap(); // ['name' => 'Alice']

// Validate without decoding (faster)
Json::validate('{"valid": true}')->isOk();  // true
Json::validate('{invalid}')->isErr();        // true

// Typed errors for each operation
Json::encode($resource);       // Result::err(EncodingError)
Json::decode('{bad}');         // Result::err(DecodingError)
Json::validate('{bad}');       // Result::err(ValidationError)

// andThen chaining — validate then decode
$config = Json::validate($rawJson)                       // Result<string, ValidationError>
    ->andThen(fn(string $json) => Json::decode($json))   // Result<array, DecodingError>
    ->map(fn(array $data) => $data['settings'] ?? []);   // Result<array, ...>

// Cross-module: read file, decode, transform, re-encode
$result = FileSystem::read('/app/settings.json')
    ->andThen(fn(Str $s) => Json::decode($s->toString()))
    ->map(fn(array $c) => array_merge($c, ['updated' => true]))
    ->andThen(fn(array $c) => Json::encode($c, JSON_PRETTY_PRINT));
```

For complete documentation with examples, see [Json Documentation](./docs/json.md).

### Path Module

The Path module provides cross-platform path manipulation and validation:

```php
// Path creation and manipulation
$path = Path::of('/var/www/html/index.php');
$parent = $path->parent(); // Option<Path>
$fileName = $path->fileName(); // Option<Str>
$extension = $path->extension(); // Option<Str>

// Path joining and validation
$basePath = Path::of('/var/www');
$fullPath = $basePath->join(Path::of('uploads/image.jpg'));

// Path inspection
if ($path->isAbsolute()) {
    echo "This is an absolute path";
}

// Canonicalization (resolves symlinks and . / ..)
$canonical = $path->canonicalize()->unwrap();

// andThen chaining on Option — safe nested navigation
$grandParentName = Path::of('/var/www/html/index.php')
    ->parent()                                           // Option::some(Path('/var/www/html'))
    ->andThen(fn(Path $p) => $p->parent())               // Option::some(Path('/var/www'))
    ->andThen(fn(Path $p) => $p->fileName())             // Option::some(Str('www'))
    ->map(fn(Str $name) => $name->toString());           // Option::some('www')

// extension returns Option — chain safely
$isPhp = Path::of('/var/www/index.php')
    ->extension()                                        // Option::some(Str('php'))
    ->map(fn(Str $ext) => $ext->toString() === 'php')    // Option::some(true)
    ->unwrapOr(false);                                   // true

// okOr — convert Option to Result when you need error context
$parent = Path::of('/')
    ->parent()                                           // Option::none() (root has no parent)
    ->okOr(new \RuntimeException('Path has no parent')); // Result::err(RuntimeException)
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

// andThen chaining on Result — time arithmetic can fail (negative durations, overflow)
$elapsed = SystemTime::now()
    ->durationSince($start)                              // Result<Duration, ...>
    ->map(fn(Duration $d) => $d->seconds())              // Result<Integer, ...>
    ->map(fn(Integer $s) => $s->toInt());                // Result<int, ...>

// Duration arithmetic with andThen
$timeout = Duration::fromSeconds(30)
    ->div(0)                                             // Result::err(DivisionByZero)
    ->orElse(fn($e) => Result::ok(Duration::fromSeconds(1))); // Fallback to 1s
```

> [!NOTE]
> **Documentation**: Detailed documentation for these modules is in development. For now, you can explore the examples in the [tests](tests) directory or read the inline documentation in the source code classes.

## Type Safety

> **Important:** Type safety is enforced **only through static analysis**. There are no runtime type checks in this library.

This library uses PHPDoc annotations to provide type information that can be verified by static analysis tools. To ensure type safety, it is **strongly recommended** to use a static analyzer like Psalm.

Without static analysis, you lose most of the type safety benefits of this library.

For example, with the Sequence collection, which is an ordered list of elements of the same type, nothing technically prevents you from adding mixed types to the collection, but most of the Sequence APIs will not work as expected and might throw exceptions at runtime.

```php
$seq = Sequence::of(1, 2, 3)->add('string');
$seq->map(fn($n) => $n * 2); // Uncaught TypeError: Unsupported operand types: string * int
```
This enforces you to really think about your implementation and the types you are using.

## Design Philosophy

- Type safety through static analysis (no runtime type checking)
- Immutable data structures with method chaining
- Functional programming patterns
- Error handling with Option and Result types instead of exceptions or nulls

### Monadic Composition with `Option` and `Result`

A central pattern in this library is **monadic composition** — chaining operations that may fail or return absent values, without nested `if` / `try` / `null` checks. Two types carry this pattern: `Option<T>` (presence/absence) and `Result<T, E>` (success/failure).

#### The key methods

| Method | On `Option` | On `Result` | Purpose |
|---|---|---|---|
| `map()` | `(T) -> U` | `(T) -> U` | Transform the inner value. Can't fail. |
| `andThen()` | `(T) -> Option<U>` | `(T) -> Result<U, E>` | Chain an operation that itself returns Option/Result. **This is flatMap.** |
| `orElse()` | `() -> Option<T>` | `(E) -> Result<T, F>` | Provide a fallback on None/Err. |
| `match()` | `(T)->U, ()->U` | `(T)->U, (E)->V` | Exhaustive pattern matching. |
| `mapErr()` | — | `(E) -> F` | Transform the error without touching the value. |
| `option()` | — | → `Option<T>` | Drop the error, keep only presence. |
| `okOr()` | → `Result<T, E>` | — | Attach an error to absence. |

#### `map()` vs `andThen()` — when to use which

`map()` transforms the inner value with a function that **always succeeds** (returns a plain value). `andThen()` chains an operation that **can itself fail or be absent** (returns `Option` or `Result`). Using `map()` where `andThen()` is needed produces nested types (`Option<Option<T>>` or `Result<Result<T, E>, E>`).

```php
// map: transform the value (callback returns a plain value)
$seq->get(0)->map(fn(int $n) => $n * 2);                // Option<int>

// andThen: chain another Optional operation (callback returns an Option)
$seq->get(0)->andThen(fn(int $n) => $seq->get($n));     // Option<int> — not Option<Option<int>>

// Same logic with Result
Integer::of(10)->div(2)->map(fn(Integer $n) => $n->mul(3));         // Result<Integer, ...>
Integer::of(10)->div(2)->andThen(fn(Integer $n) => $n->div(0));     // Result<Integer, ...>
```

#### Bridging `Option` and `Result`

```php
// Option -> Result with okOr (attach an error to None)
$port = $config->get('port')
    ->okOr(new \RuntimeException('Missing port'));       // Result<string, RuntimeException>

// Result -> Option with option() (drop the error)
$content = FileSystem::read('/optional.txt')
    ->option();                                          // Some(Str) or None — error discarded
```

#### Cross-module pipelines

The real power emerges when chaining across modules — each step can fail independently, and errors propagate automatically:

```php
// File -> JSON -> Validation -> Domain object
$settings = FileSystem::read('/app/config.json')         // Result<Str, FileNotFound|...>
    ->andThen(fn(Str $s) => Json::decode($s->toString()))// Result<array, DecodingError>
    ->andThen(fn(array $c) => isset($c['port'])
        ? Result::ok($c)
        : Result::err(new \RuntimeException('Missing port'))
    )
    ->map(fn(array $c) => new AppConfig($c))             // Result<AppConfig, ...>
    ->inspectErr(fn($e) => error_log($e->getMessage())); // Log error without breaking the chain

// Sequence -> Option -> Result (bridge between the two monads)
$result = $users
    ->find(fn(User $u) => $u->isAdmin())                 // Option<User>
    ->okOr(new \RuntimeException('No admin found'))      // Result<User, RuntimeException>
    ->andThen(fn(User $u) => $u->loadProfile());         // Result<Profile, ...>
```

### Why Immutability? — Divergences from Rust and the PHP Context

Unlike Rust, where **mutability is explicit** (`mut`) and tightly enforced by the ownership system, PHP operates with **mutable objects by default** and implicit reference semantics. This fundamental difference is the main reason why all types in this library are implemented as immutable structures.

### Why Immutability Makes Sense in PHP

- **Avoiding Side Effects**
In PHP, objects are passed by reference, so mutations can easily propagate unexpectedly across different parts of an application. For example, modifying a `Sequence` or `Str` in one place could unintentionally affect other references to the same object. Immutability eliminates this risk by ensuring every operation returns a new instance, making code more predictable and robust.
- **No Native Mutability Controls**
Rust gives you fine-grained control over mutability and ownership (`&mut`, borrow checker), ensuring thread safety and preventing data races at compile time. PHP lacks these mechanisms. By enforcing immutability at the API level, this library provides a similar guarantee—purely through convention and design—mirroring patterns seen in PHP’s own `DateTimeImmutable`.
- **Memory and Lifecycle Management**
Rust can optimize in-place mutations thanks to its allocator and ownership model, minimizing allocations and copies. PHP relies on a garbage collector and reference counting, so creating new immutable objects is less problematic and often leads to clearer, safer code.


### Impact on the API — Key Divergences from Rust

This design choice leads to some important differences from Rust’s original APIs:


| Operation | Rust (Mutable) | PHP Core Library (Immutable)  |
| :-- | :-- |:------------------------------|
| Add element to collection | `vec.push(item)` (in-place) | `$seq->add($item)` (new Sequence) |
| String concatenation | `s1.push_str(&s2)` | `$s1->append($s2)` (new Str)  |

**Example with `Sequence::map`:**

```php
// PHP: Immutable chaining
$result = $seq->map(fn($x) => $x * 2)->filter(...);

// Rust: Mutable iterators
let result: Sequence<_> = Sequence.iter().map(|x| x * 2).filter(...).collect();
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
$s = Str::of("Hello");
$s = $s->append(Str::of(" World"));
```

This approach reflects a **hybrid** between Rust's conceptual strengths and PHP's practical realities, bringing the benefits of functional programming and data safety to a language not originally designed with these patterns in mind.

### The Exception: Mutable Handles for OS Resources

While immutability is the default for all value types, some objects wrap **mutable OS resources** (file descriptors, process handles) that are inherently stateful. Forcing immutability on these would introduce real costs with no benefit:

- **TOCTOU vulnerabilities**: Opening and closing a file for each operation creates a window where another process can modify or delete the file between the check and the action.
- **No streaming**: Without a persistent handle, the only option is to load entire files into memory, which is impractical for large files.
- **Artificial complexity**: Returning a new "immutable" object that re-opens the same OS resource on each call is a leaky abstraction — the underlying state is mutable regardless.

The library addresses this with a **two-tier architecture** that mirrors Rust's own separation:

| | Configuration (immutable) | OS Resource (mutable) |
|---|---|---|
| **Process** | `Command` / `ProcessBuilder` | `Process` |
| **FileSystem** | `FileSystem` (static one-shot ops) | `File` (handle-based) |

**Immutable tier** — Configuration and one-shot operations. `Command` builds process configurations immutably (each `withArg()` returns a new instance). `FileSystem` provides stateless static methods (`read()`, `write()`, `copyFile()`) that open, act, and close in a single call.

**Mutable tier** — Persistent OS handles. `Process` wraps a running process with its stdin/stdout/stderr streams. `File` wraps an open file descriptor with seek, read, and write operations. Both follow the same pattern: explicit `close()` with a `__destruct` safety net.

This separation is deliberate and consistent. Value types (`Str`, `Integer`, `Sequence`, `Metadata`, `Permissions`, `FileTimes`) remain `final readonly class` with `@psalm-immutable`. Resource wrappers (`File`, `Process`) are `final class` without immutability annotations, reflecting their true nature.

**Advantages**:
- Eliminates TOCTOU between read and write on the same file
- Enables streaming (line-by-line, chunk-by-chunk) for large files
- Atomic writes via temp-file + rename through the persistent handle
- Consistent with how Rust separates `std::fs` functions from `std::fs::File`

**Limitations**:
- The caller is responsible for closing handles (mitigated by `__destruct` and `withOpen()`)
- Operations on a closed handle will fail at runtime, not at compile time (PHP has no borrow checker)
- Mutable objects cannot be used in `@psalm-immutable` contexts without suppressions

## Requirements

- PHP 8.3 or higher
- PHP Extensions:
  - `ext-mbstring`: For proper UTF-8 string handling (required for `Str` type)
  - `ext-intl`: For Unicode normalization and character classification (required for `Str` and `Char` types)
  - `ext-iconv`: For character encoding conversion (required for `Str` type)

## Standard Library Architecture

This library is designed as a cohesive ecosystem where modules complement each other:

- **Core Types** (`Sequence`, `Map`, `Set`, `Option`, `Result`, `Str`, `Char`, `Integer`, `Double`) provide the foundation
- **FileSystem** uses `Path`, `Result`, and core types for safe file operations
- **Process** uses `Result`, `Option`, `Str`, `Duration`, and typed errors for safe process execution
- **Json** wraps PHP native JSON functions with `Result` and `Str` integration
- **Path** integrates with `Option` and `Result` for path validation and manipulation
- **Time** provides `SystemTime` and `Duration` with overflow-safe arithmetic using `Integer`

All modules follow consistent patterns for error handling, immutability, and functional composition, making them work naturally together while remaining useful independently.

## Acknowledgments

Besides Rust, this project is also greatly inspired by the work of [Baptiste Langlade](https://github.com/baptouuuu) through his organization [Innmind](https://github.com/Innmind), which has pioneered bringing functional programming patterns and immutable data structures to PHP.

## Contributing

Contributions are welcome! This is an experimental project aiming to bring Rust-like patterns to PHP.

## License

MIT
