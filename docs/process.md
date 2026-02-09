# Process Module

The Process module provides a safe, immutable-where-possible API for spawning and managing OS processes. Commands are built immutably following Rust's `std::process` patterns, shell interpretation is bypassed by default for security, and all I/O is wrapped in `Result` types. Classes that hold mutable OS resources (`Process`, `StreamReader`, `StreamWriter`) are intentionally not marked `@psalm-immutable`. The module covers child process spawning, I/O stream configuration, command pipelines, and output collection with timeout support. All operations use typed error classes instead of raw strings.

## Table of Contents

- [Command](#command)
- [ProcessBuilder](#processbuilder)
- [Process](#process)
- [Pipeline](#pipeline)
- [Stream I/O](#stream-io)
  - [StreamReader](#streamreader)
  - [StreamWriter](#streamwriter)
- [Stream Configuration](#stream-configuration)
  - [StreamDescriptor](#streamdescriptor)
  - [ProcessStreams](#processstreams)
  - [FileDescriptor](#filedescriptor)
- [Output and Status](#output-and-status)
  - [Output](#output)
  - [Status](#status)
- [Error Handling](#error-handling)

## Command

`Command` is the recommended entry point for most use cases. It provides a high-level, immutable, fluent API that wraps `ProcessBuilder` with sensible defaults (30-second timeout, default pipes, current working directory).

### Creation

```php
use Jsadaa\PhpCoreLibrary\Modules\Process\Command;

// Create a command
$cmd = Command::of('ls');

// With Str type
$cmd = Command::of(Str::of('grep'));
```

### Adding Arguments

```php
// Single argument
$cmd = Command::of('grep')
    ->withArg('-r')
    ->withArg('pattern');

// Fluent chaining
$result = Command::of('find')
    ->withArg('/var/log')
    ->withArg('-name')
    ->withArg('*.log')
    ->output();
```

### Configuration

```php
// Set working directory
$cmd = Command::of('ls')->atPath('/var/www');

// Set environment variables
$cmd = Command::of('node')
    ->withEnv('NODE_ENV', 'production')
    ->withEnv('PORT', '3000');

// Set timeout (int seconds, Integer, or Duration)
$cmd = Command::of('make')
    ->withTimeout(60)
    ->withTimeout(Duration::fromMinutes(2));
```

### Execution

#### run()

Executes the command and returns the full `Output` on success, or an error on failure.

> [!IMPORTANT]
> `run()` returns `Result<Output, Output|...>`. When the process exits with a non-zero code, the error variant contains the `Output` object itself (with stdout and stderr), allowing inspection of output even on failure.

```php
$result = Command::of('echo')
    ->withArg('hello')
    ->run();

$result->match(
    fn(Output $output) => $output->stdout()->toString(), // "hello\n"
    fn($error) => match (true) {
        $error instanceof Output => $error->stderr()->toString(),
        $error instanceof ProcessTimeout => 'Timed out',
        default => $error->getMessage(),
    },
);
```

Monadic composition on the result:

```php
// Chain on success: extract and transform output
$lines = Command::of('ls')->withArg('-la')->run()
    ->map(fn(Output $o) => $o->stdout())
    ->map(fn(Str $s) => $s->lines());

// andThen: run command then decode its output
$config = Command::of('cat')->withArg('config.json')->output()
    ->andThen(fn(Str $content) => Json::decode($content->toString()));
```

#### output()

Convenience method that returns only stdout as `Str`.

```php
$result = Command::of('date')->output();

if ($result->isOk()) {
    echo $result->unwrap()->toString(); // "Thu Feb 6 14:30:00 UTC 2026\n"
}
```

Monadic composition:

```php
// Transform output
$version = Command::of('php')->withArg('--version')->output()
    ->map(fn(Str $s) => $s->lines()->first())
    ->andThen(fn(Option $first) => $first->okOr(new \RuntimeException('No output')));

// Fallback with orElse
$content = Command::of('cat')->withArg('/etc/app.conf')->output()
    ->orElse(fn() => Command::of('cat')->withArg('/etc/app.conf.default')->output());
```

#### spawn()

Starts the process without waiting for completion. Returns a `Process` handle. Pipeline commands cannot use `spawn()`; use `run()` or `output()` instead.

```php
$result = Command::of('sleep')
    ->withArg('10')
    ->spawn();

if ($result->isOk()) {
    $process = $result->unwrap();
    // Process is running in the background
    $process->kill();
    $process->close();
}
```

Monadic composition with spawn and output collection:

```php
$result = Command::of('grep')->withArg('-r')->withArg('TODO')->withArg('src/')
    ->spawn()
    ->andThen(fn(Process $p) => $p->output(Duration::fromSeconds(10)))
    ->map(fn(Output $o) => $o->stdout()->lines()->size());
```

### I/O Redirection

```php
// Redirect stdin from a file
$cmd = Command::of('sort')->fromFile('/tmp/data.txt');

// Redirect stdout to a file
$cmd = Command::of('ls')->toFile('/tmp/listing.txt');

// Redirect stderr to a file
$cmd = Command::of('make')->errorToFile('/tmp/errors.log');

// Suppress all output
$cmd = Command::of('make')->quiet();

// Custom stream configuration
$cmd = Command::of('cat')->withStreams(ProcessStreams::defaults());
```

## ProcessBuilder

`ProcessBuilder` is the lower-level API used internally by `Command`. Use it directly when you need fine-grained control over process configuration that `Command` does not expose.

### Creation and Configuration

```php
use Jsadaa\PhpCoreLibrary\Modules\Process\ProcessBuilder;

$builder = ProcessBuilder::command('gcc')
    ->arg('-o')
    ->arg('output')
    ->arg('main.c')
    ->workingDirectory('/home/user/project')
    ->env('CC', 'gcc-13');

// Add multiple arguments at once
$builder = ProcessBuilder::command('docker')
    ->args(['build', '-t', 'myapp', '.']);
```

### Environment Control

> [!IMPORTANT]
> By default, the child process inherits the parent's environment variables. Custom variables set with `env()` are merged on top. Use `clearEnv()` or `inheritEnv(false)` to start with a clean environment.

```php
// Inherit parent env + add custom vars (default)
$builder = ProcessBuilder::command('node')
    ->env('NODE_ENV', 'production');

// Only custom vars, no inheritance
$builder = ProcessBuilder::command('env')
    ->inheritEnv(false)
    ->env('ONLY_THIS', 'value');

// Clear everything and start fresh
$builder = ProcessBuilder::command('env')
    ->clearEnv()
    ->env('CLEAN', 'slate');
```

### Spawning

`ProcessBuilder` uses `bypass_shell => true` with array command format, executing the command directly without shell interpretation. This prevents shell injection attacks; arguments are passed as-is to the process.

```php
$result = $builder->spawn();

$result->match(
    fn(Process $process) => 'Process started with PID: ' . $process->pid()->unwrap(),
    fn($error) => match (true) {
        $error instanceof InvalidCommand => 'Empty command',
        $error instanceof InvalidWorkingDirectory => $error->getMessage(),
        $error instanceof ProcessSpawnFailed => 'Failed to start process',
    },
);
```

## Process

The `Process` class represents a running or finished OS process. It holds mutable resources (process handle, pipe file descriptors).

> [!CAUTION]
> `Process` wraps OS resources that must be explicitly released. Always call `close()` when done, even if the process has already finished. The `output()` method closes stdout and stderr pipes automatically, but `close()` must still be called to release the process handle itself.

### Lifecycle

```php
$process = ProcessBuilder::command('sleep')
    ->arg('5')
    ->spawn()
    ->unwrap();

// Check if running
$process->isRunning(); // true

// Get PID
$pid = $process->pid()->unwrap();

// Wait for completion (with optional timeout)
$status = $process->wait(Duration::fromSeconds(10));

// Kill the process
$process->kill();           // SIGTERM
$process->kill(\SIGKILL);   // SIGKILL

// Release resources
$process->close();
```

### Reading Output

The `output()` method closes stdin, reads stdout and stderr simultaneously using `stream_select()` for non-blocking I/O multiplexing, and waits for the process to exit.

```php
$process = ProcessBuilder::command('echo')
    ->arg('hello')
    ->spawn()
    ->unwrap();

// Collect all output
$output = $process->output(Duration::fromSeconds(5));

if ($output->isOk()) {
    echo $output->unwrap()->stdout()->toString(); // "hello\n"
    echo $output->unwrap()->stderr()->toString(); // ""
    echo $output->unwrap()->exitCode();  // 0
}

$process->close();
```

### Writing to stdin

```php
$process = ProcessBuilder::command('cat')
    ->spawn()
    ->unwrap();

// Write data to stdin
$process->writeStdin('Hello from PHP');

// Or get a StreamWriter for more control
$writer = $process->stdinWriter()->unwrap();
$writer->writeLine('Line 1');
$writer->writeLine('Line 2');
```

### Direct Stream Access

```php
// Access raw pipe resources via Option
$stdin  = $process->stdin();  // Option<resource>
$stdout = $process->stdout(); // Option<resource>
$stderr = $process->stderr(); // Option<resource>

// Read directly
$result = $process->readStdout(); // Result<Str, StreamReadFailed>
$result = $process->readStderr(); // Result<Str, StreamReadFailed>
```

## Pipeline

Pipelines connect the stdout of one command to the stdin of the next, similar to Unix pipes.

> [!IMPORTANT]
> Pipeline commands can only be executed with `run()` or `output()`. Calling `spawn()` on a pipeline returns a `PipelineSpawnFailed` error.

```php
// Simple pipeline: echo | grep
$result = Command::of('echo')
    ->withArg('hello world')
    ->pipe(Command::of('grep')->withArg('world'))
    ->output();

echo $result->unwrap()->toString(); // "hello world\n"

// Multi-stage pipeline: echo | tr | sort | uniq
$result = Command::of('echo')
    ->withArg('banana apple cherry apple banana')
    ->pipe(Command::of('tr')->withArg(' ')->withArg("\n"))
    ->pipe(Command::of('sort'))
    ->pipe(Command::of('uniq'))
    ->output();

// "apple\nbanana\ncherry\n"
```

Monadic composition on pipeline results:

```php
// Chain pipeline result: count PHP files
$count = Command::of('find')->withArg('.')->withArg('-name')->withArg('*.php')
    ->pipe(Command::of('wc')->withArg('-l'))
    ->output()
    ->map(fn(Str $s) => $s->trim())
    ->andThen(fn(Str $s) => $s->parseInteger());
```

## Stream I/O

### StreamReader

Non-blocking stream reader with timeout support using `stream_select()`.

```php
use Jsadaa\PhpCoreLibrary\Modules\Process\StreamReader;

$process = ProcessBuilder::command('cat')
    ->arg('/var/log/syslog')
    ->spawn()
    ->unwrap();

$reader = StreamReader::from($process->stdout()->unwrap());

// Read all available data (non-blocking)
$data = $reader->readAvailable(); // Result<Str, StreamReadFailed>

// Read until EOF with timeout
$all = $reader->readAll(Duration::fromSeconds(5)); // Result<Str, StreamReadFailed|ProcessTimeout|TimeOverflow>

// Read until a delimiter
$line = $reader->readUntil("\n", Duration::fromSeconds(2));

// Check EOF
$reader->isEof();

// Close the stream
$reader->close();
```

### StreamWriter

Stream writer with buffering, auto-flush, and chunked writing support. For interactive processes where data must be sent immediately, use `StreamWriter::createAutoFlushing()` -- this is what `Process::stdinWriter()` uses internally.

```php
use Jsadaa\PhpCoreLibrary\Modules\Process\StreamWriter;

$process = ProcessBuilder::command('cat')
    ->spawn()
    ->unwrap();

$writer = StreamWriter::from($process->stdin()->unwrap());

// Write data
$writer->write('Hello'); // Result<int, StreamWriteFailed>

// Write a line (appends line ending)
$writer->writeLine('World'); // Result<int, StreamWriteFailed>

// Write multiple lines
$writer->writeLines(['Line 1', 'Line 2', 'Line 3']);

// Write large data in chunks
$writer->writeChunked($largeData);

// Configure the writer
$writer = $writer
    ->withAutoFlush(true)
    ->withBufferSize(4096)
    ->withLineEnding(Str::of("\r\n"));

// Flush manually
$writer->flush(); // Result<null, StreamFlushFailed>
```

## Stream Configuration

### StreamDescriptor

Describes how a file descriptor should be set up for a process before it starts.

```php
use Jsadaa\PhpCoreLibrary\Modules\Process\StreamDescriptor;

// Pipe for inter-process communication
$desc = StreamDescriptor::pipe('r');   // read pipe
$desc = StreamDescriptor::pipe('w');   // write pipe

// File descriptor
$desc = StreamDescriptor::file('/tmp/output.log', 'w');
$desc = StreamDescriptor::file(Path::of('/tmp/input.txt'), 'r');

// From existing resource
$desc = StreamDescriptor::resource(\STDIN);

// Inherit from parent process
$desc = StreamDescriptor::inherit();

// Redirect to /dev/null
$desc = StreamDescriptor::null();

// Type checks
$desc->isPipe();     // bool
$desc->isFile();     // bool
$desc->isResource(); // bool
$desc->isInherit();  // bool
$desc->isNull();     // bool
```

### ProcessStreams

Immutable collection of stream descriptors for configuring process I/O.

```php
use Jsadaa\PhpCoreLibrary\Modules\Process\ProcessStreams;

// Default: all pipes (stdin=r, stdout=w, stderr=w)
$streams = ProcessStreams::defaults();

// Inherit all from parent
$streams = ProcessStreams::inherit();

// Redirect all to /dev/null
$streams = ProcessStreams::null();

// Customize individual streams
$streams = ProcessStreams::defaults()
    ->withStdin(StreamDescriptor::file('/tmp/input.txt', 'r'))
    ->withStdout(StreamDescriptor::file('/tmp/output.txt', 'w'))
    ->withStderr(StreamDescriptor::null());

// Custom file descriptor
$streams = $streams->withDescriptor(
    FileDescriptor::custom(3),
    StreamDescriptor::pipe('w'),
);

// Convert to proc_open format
$descriptorArray = $streams->toDescriptorArray();
```

### FileDescriptor

Represents a file descriptor number in a process.

```php
use Jsadaa\PhpCoreLibrary\Modules\Process\FileDescriptor;

$stdin  = FileDescriptor::stdin();    // fd 0
$stdout = FileDescriptor::stdout();   // fd 1
$stderr = FileDescriptor::stderr();   // fd 2
$custom = FileDescriptor::custom(3);  // fd 3

// Inspection
$fd->toInt();     // int
$fd->isStdin();   // bool
$fd->isStdout();  // bool
$fd->isStderr();  // bool

// Equality
$fd->eq(FileDescriptor::stdin()); // bool
```

## Output and Status

### Output

Immutable value object holding the result of a process execution.

```php
$output = Command::of('ls')->withArg('-la')->run()->unwrap();

$output->stdout();    // Str - standard output content
$output->stderr();    // Str - standard error content
$output->exitCode();  // int - exit code
$output->isSuccess(); // bool - exit code == 0
$output->isFailure(); // bool - exit code != 0

// toString() returns stdout on success, stderr on failure
$output->toString();
```

### Status

Immutable snapshot of process state from `proc_get_status`.

> [!CAUTION]
> `proc_get_status()` only returns the correct exit code on the **first** call after process termination. Subsequent calls return `-1`. The `Status` object captures this snapshot, so always use it from the first call.

```php
$process = ProcessBuilder::command('sleep')
    ->arg('1')
    ->spawn()
    ->unwrap();

$status = $process->status();

$status->command();    // Str
$status->pid();        // int
$status->isRunning();  // bool
$status->isSignaled(); // bool
$status->isStopped();  // bool
$status->exitCode();   // int
$status->termSignal(); // int
$status->stopSignal(); // int
$status->isSuccess();  // bool (exitCode == 0)
$status->isFailure();  // bool (exitCode != 0)
```

## Error Handling

All error types extend standard PHP exception classes and follow the project's established pattern for typed errors. The Process module uses dedicated error classes in `Process\Error\`:

| Error Class | Extends | Description |
|---|---|---|
| `InvalidCommand` | `InvalidArgumentException` | Empty command string |
| `InvalidWorkingDirectory` | `InvalidArgumentException` | Working directory does not exist |
| `ProcessSpawnFailed` | `RuntimeException` | `proc_open` returned false |
| `ProcessTimeout` | `RuntimeException` | Process or read operation exceeded timeout |
| `ProcessSignalFailed` | `RuntimeException` | `proc_terminate` failed |
| `PipelineSpawnFailed` | `RuntimeException` | Attempted to `spawn()` a pipeline |
| `StreamReadFailed` | `RuntimeException` | Failed to read from stream or stream not available |
| `StreamWriteFailed` | `RuntimeException` | Failed to write to stream |
| `StreamFlushFailed` | `RuntimeException` | Failed to flush stream buffer |
| `InvalidPid` | `RuntimeException` | Process has no valid PID |

All error types can be pattern-matched in `Result` handlers:

```php
$result = Command::of('nonexistent-command')->run();

$result->match(
    fn(Output $output) => 'Success: ' . $output->stdout()->toString(),
    fn($error) => match (true) {
        $error instanceof Output => 'Process failed: ' . $error->stderr()->toString(),
        $error instanceof ProcessSpawnFailed => 'Could not start process',
        $error instanceof ProcessTimeout => 'Process timed out',
        default => 'Error: ' . $error->getMessage(),
    },
);
```

Monadic composition for error normalization and fallback:

```php
// mapErr to normalize errors
$output = Command::of('make')->run()
    ->mapErr(fn($e) => match(true) {
        $e instanceof Output => new \RuntimeException($e->stderr()->toString()),
        $e instanceof ProcessTimeout => new \RuntimeException('Build timed out'),
        default => $e,
    });

// orElse for retry/fallback
$result = Command::of('npm')->withArg('ci')->run()
    ->orElse(fn() => Command::of('npm')->withArg('install')->run());
```
