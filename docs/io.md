# IO Module

The IO module provides static methods for reading from stdin and writing to stdout/stderr. All operations return `Result` types for type-safe error handling. When format arguments are provided, messages are formatted via `Str::format()` with Rust-inspired `{}` placeholders.

This module handles the **current process's standard streams** — it is distinct from `FileSystem` (file operations) and `Process` (external process management).

## Table of Contents

- [Writing to stdout](#writing-to-stdout)
- [Writing to stderr](#writing-to-stderr)
- [Reading from stdin](#reading-from-stdin)
- [Formatting](#formatting)
- [Error Handling](#error-handling)

## Writing to stdout

### println()

Writes a message to stdout followed by a newline (`PHP_EOL`).

```php
use Jsadaa\PhpCoreLibrary\Modules\IO\IO;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;

// Simple output
IO::println('Hello, world!');
// stdout: "Hello, world!\n"

// With Str instance
IO::println(Str::of('Hello'));
// stdout: "Hello\n"

// With format arguments (positional)
IO::println('Hello, {}!', 'Alice');
// stdout: "Hello, Alice!\n"

IO::println('{} + {} = {}', 1, 2, 3);
// stdout: "1 + 2 = 3\n"

// With named arguments (PHP 8.3 named args)
IO::println('Connecting to {host}:{port}', host: 'localhost', port: 5432);
// stdout: "Connecting to localhost:5432\n"

// Returns Result<Unit, WriteFailed>
$result = IO::println('Hello');
$result->match(
    fn($unit) => null,                       // success, nothing to do
    fn($error) => handleError($error),       // pipe broken, etc.
);
```

### Parameters

| Parameter | Type | Default | Description |
|---|---|---|---|
| `$message` | `Str\|string` | (required) | The message or format template |
| `...$args` | `mixed` | (none) | Optional format arguments (positional and/or named) |

### print()

Same as `println()` but without a trailing newline.

```php
IO::print('Enter name: ');
// stdout: "Enter name: " (no newline — useful before readLine)

IO::print('Count: {}', 42);
// stdout: "Count: 42"
```

## Writing to stderr

### eprintln()

Writes a message to stderr followed by a newline. Same signature and formatting as `println()`.

```php
IO::eprintln('Error: file not found');
// stderr: "Error: file not found\n"

IO::eprintln('Failed to connect to {host}', host: 'db.example.com');
// stderr: "Failed to connect to db.example.com\n"

// Chain error reporting with operations
FileSystem::read('/etc/app/config.json')
    ->inspectErr(fn($e) => IO::eprintln('Config error: {}', $e->getMessage()))
    ->orElse(fn($e) => Result::ok(Str::of('{}')));
```

### eprint()

Same as `eprintln()` but without a trailing newline.

```php
IO::eprint('Warning: ');
IO::eprintln('deprecated function called');
// stderr: "Warning: deprecated function called\n"
```

## Reading from stdin

### readLine()

Reads a single line from stdin. Uses `readline` when available, falls back to `fgets(STDIN)`. The trailing newline is stripped.

```php
// Simple read
$result = IO::readLine();
// Result<Str, ReadFailed>

// With prompt
$result = IO::readLine(Str::of('Enter your name: '));
// Displays "Enter your name: " then waits for input

// EOF (Ctrl+D) returns ReadFailed
$result = IO::readLine();
$result->match(
    fn(Str $input) => IO::println('You entered: {}', $input),
    fn($error) => IO::eprintln('No input received'),
);
```

### Parameters

| Parameter | Type | Default | Description |
|---|---|---|---|
| `$prompt` | `Str\|null` | `null` | Optional prompt displayed before reading |

### Monadic chaining with readLine

`readLine` returns `Result<Str, ReadFailed>`, which integrates directly into monadic pipelines:

```php
// Read, parse, and validate — each step can fail independently
$port = IO::readLine(Str::of('Port: '))
    ->andThen(fn(Str $s) => $s->trim()->parseInteger())
    ->andThen(fn(Integer $n) => $n->gt(0) && $n->lt(65536)
        ? Result::ok($n)
        : Result::err(new \InvalidArgumentException('Port must be 1-65535'))
    );

$port->match(
    fn(Integer $p) => IO::println('Starting server on port {}', $p),
    fn($e) => IO::eprintln('Invalid port: {}', $e->getMessage()),
);

// Read a filename and load its content
$content = IO::readLine(Str::of('File path: '))
    ->map(fn(Str $s) => $s->trim())
    ->andThen(fn(Str $path) => FileSystem::read($path->toString()));

// Read and decode JSON input
$data = IO::readLine()
    ->andThen(fn(Str $s) => Json::decode($s->toString()))
    ->map(fn(array $d) => $d['value'] ?? null);
```

## Formatting

`IO::println`, `IO::print`, `IO::eprintln`, and `IO::eprint` all support inline formatting when extra arguments are provided. Formatting is delegated to `Str::format()`.

### Placeholder types

| Syntax | Behavior |
|---|---|
| `{}` | Positional — consumes arguments in order |
| `{name}` | Named — uses PHP named arguments |
| `{{` / `}}` | Escaped literal braces |

### Argument resolution

| Argument type | Resolution |
|---|---|
| `\Stringable` (Str, Path, Char, Integer, Double...) | Calls `__toString()` |
| `string`, `int`, `float` | Cast to string |
| `bool` | `'true'` or `'false'` |
| `null` | `'null'` |
| Other | `\InvalidArgumentException` |

```php
// Positional
IO::println('{} items found in {} ms', 42, 3.14);

// Named
IO::println('User {name} (id: {id})', name: 'Alice', id: 7);

// Stringable objects — including Integer and Double
IO::println('Path: {}', Path::of('/var/www'));
IO::println('Content: {}', Str::of('hello'));
IO::println('Count: {}', Integer::of(42));
IO::println('Ratio: {}', Double::of(3.14));

// Escaped braces
IO::println('Use {{}} for placeholders');
// stdout: "Use {} for placeholders\n"

// Integer and Double are Stringable — pass directly
IO::println('Count: {}', Integer::of(42));
IO::println('Ratio: {}', Double::of(3.14));
```

## Error Handling

All IO operations return `Result` types with dedicated error classes in the `Jsadaa\PhpCoreLibrary\Modules\IO\Error` namespace.

### Error types

| Error | Returned by | Cause |
|---|---|---|
| `WriteFailed` | `println`, `print`, `eprintln`, `eprint` | `fwrite` failure (broken pipe, closed stream) |
| `ReadFailed` | `readLine` | EOF or `fgets`/`readline` failure |

Both extend `\RuntimeException`.

### Error handling patterns

```php
// Pattern matching
IO::println('Hello')->match(
    fn($unit) => null,
    fn(WriteFailed $e) => error_log('Output failed: ' . $e->getMessage()),
);

// mapErr for error normalization
$result = IO::readLine()
    ->mapErr(fn(ReadFailed $e) => new \RuntimeException('User input required'));

// orElse for retry/fallback
$input = IO::readLine(Str::of('Name: '))
    ->orElse(fn($e) => Result::ok(Str::of('Anonymous')));

// Combined pipeline with error reporting
IO::readLine(Str::of('Config path: '))
    ->andThen(fn(Str $path) => FileSystem::read($path->toString()))
    ->andThen(fn(Str $json) => Json::decode($json->toString()))
    ->inspectErr(fn($e) => IO::eprintln('Error: {}', $e->getMessage()))
    ->match(
        fn(array $config) => IO::println('Loaded {} keys', \count($config)),
        fn($e) => IO::eprintln('Falling back to defaults'),
    );
```
