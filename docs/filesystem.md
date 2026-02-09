# FileSystem Module

This module separates one-shot operations (`FileSystem` static methods) from handle-based streaming (`File` class). `FileSystem` uses `file_get_contents` / `file_put_contents` for simple read/write, while `File` wraps a persistent OS file handle for streaming, seeking, and atomic writes. This mirrors the Rust separation between `std::fs` free functions and `std::fs::File`.

The FileSystem module provides types and operations for safely handling files, directories, and filesystem metadata. All potentially failing operations return `Result` types, enabling monadic composition with `andThen`, `map`, `orElse`, and `mapErr`.

## Table of Contents

- [File](#file)
- [FileSystem](#filesystem)
- [FileTimes](#filetimes)
- [FileType](#filetype)
- [Metadata](#metadata)
- [Permissions](#permissions)
- [Error Handling](#error-handling)

## File

`File` wraps a mutable OS file handle. It is intentionally not immutable, following the same pattern as `Process`. The handle remains open until `close()` is called or the object is garbage-collected. All methods that accept a path argument accept both `string` and `Path` objects.

### Creation

#### Open

Opens an existing file for reading and writing. The file must exist and be a regular file.

```php
// Imperative style
$result = File::open('/etc/hosts');

if ($result->isOk()) {
    $file = $result->unwrap();
    $content = $file->readAll()->unwrap();
    echo $content->toString();
    $file->close();
}

// Using a Path object
$path = Path::of('/etc/hosts');
$file = File::open($path)->unwrap();
```

Monadic chaining -- open, read, and transform in a single pipeline:

```php
$upper = File::withOpen('/etc/hosts', fn(File $f) =>
    $f->readAll()->map(fn(Str $c) => $c->toUppercase())
)->andThen(fn(Result $r) => $r);
```

#### Create

Creates a new empty file for reading and writing. The parent directory must exist. Fails with `AlreadyExists` if the file already exists (exclusive create).

```php
$result = File::create('/path/to/file.txt');

if ($result->isOk()) {
    $file = $result->unwrap();
    $file->write('Hello, world!')->unwrap();
    $file->close();
}
```

### Reading Operations

#### Read All

Reads the entire file content from the beginning. Rewinds to the start, reads everything, then leaves the position at EOF.

```php
$file = File::open('/path/to/data.txt')->unwrap();
$content = $file->readAll()->unwrap();
echo $content->toString();
$file->close();
```

Chaining open and read into a transformation:

```php
// Open -> read -> transform content
$lineCount = File::withOpen('/path/to/data.txt', fn(File $f) =>
    $f->readAll()->map(fn(Str $c) => $c->lines()->size())
);

// Open -> read -> parse JSON via andThen
$config = File::withOpen('/path/to/config.json', fn(File $f) =>
    $f->readAll()
        ->map(fn(Str $c) => $c->toString())
        ->andThen(fn(string $json) => Json::decode($json))
);
```

#### Read Line

Reads a single line from the current position. Returns `Option::none()` when EOF is reached.

```php
$file = File::open('/path/to/data.txt')->unwrap();

while (true) {
    $line = $file->readLine()->unwrap();

    if ($line->isNone()) {
        break; // EOF
    }

    echo $line->unwrap()->toString();
}

$file->close();
```

#### Read Chunk

Reads a chunk of bytes from the current position. May return fewer bytes than requested if EOF is reached. This is the preferred approach for processing large files without loading the entire content into memory.

```php
$file = File::open('/path/to/large-file.bin')->unwrap();
$chunkSize = 1024;

while (true) {
    $chunk = $file->readChunk($chunkSize)->unwrap();

    if ($chunk->size() === 0) {
        break;
    }

    // Process chunk...
}

$file->close();
```

#### Bytes

Reads the entire file as a `Result<Sequence<int>, ReadFailed>`. Each element is a native `int` in the range 0--255. Rewinds to the start and reads all content. For large files, prefer `readChunk()` to avoid holding every byte in memory simultaneously.

```php
$file = File::open('/path/to/image.jpg')->unwrap();
$bytes = $file->bytes()->unwrap();

// Check JPEG file signature
$first = $bytes->get(0)->unwrap();
$second = $bytes->get(1)->unwrap();

if ($first === 0xFF && $second === 0xD8) {
    echo 'This is a JPEG image';
}

$file->close();
```

If you need `Integer` wrappers for interoperability with APIs expecting them, map over the sequence:

```php
$integers = $file->bytes()
    ->map(fn(Sequence $seq) => $seq->map(fn(int $b) => Integer::of($b)));
```

### Writing Operations

#### Write

Writes data at the current position. Accepts both `string` and `Str` values. Returns the number of bytes written as `Result<int, WriteFailed>`.

```php
$file = File::create('/path/to/output.txt')->unwrap();
$bytesWritten = $file->write('Hello, world!')->unwrap();
echo $bytesWritten; // 13

// Also accepts Str objects
$file->write(Str::of('more data'))->unwrap();

$file->close();
```

#### Append

Seeks to end of file, then writes data. Returns the number of bytes written as `Result<int, WriteFailed>`.

```php
$file = File::open('/var/log/app.log')->unwrap();
$file->append(date('Y-m-d H:i:s') . " Application started\n")->unwrap();
$file->close();
```

#### Write Atomic

> [!IMPORTANT]
> Provides atomicity via temp-file + rename. Either the entire write succeeds or the original file remains unchanged. The handle is reopened after the rename.

```php
$file = File::open('/path/to/config.json')->unwrap();

$config = ['database' => ['host' => 'localhost']];
$json = Json::encode($config, JSON_PRETTY_PRINT)->unwrap();

// Write atomically with fsync for durability
$file->writeAtomic($json, sync: true)->unwrap();
$file->close();
```

#### Flush

Flushes buffered data to the OS.

```php
$file = File::create('/path/to/output.txt')->unwrap();
$file->write('important data')->unwrap();
$file->flush()->unwrap();
$file->close();
```

### Navigation

#### Seek

Seeks to a specific byte offset from the beginning.

```php
$file = File::open('/path/to/data.bin')->unwrap();
$file->seek(100)->unwrap(); // Jump to byte 100
$chunk = $file->readChunk(16)->unwrap(); // Read 16 bytes from that position
$file->close();
```

#### Rewind

Rewinds to the beginning of the file.

```php
$file = File::open('/path/to/data.txt')->unwrap();
$file->readAll()->unwrap(); // Position is now at EOF
$file->rewind()->unwrap();  // Back to start
$first = $file->readChunk(4)->unwrap(); // Read first 4 bytes again
$file->close();
```

### Metadata Operations

#### Path

Gets the path of this file.

```php
$file = File::open('/etc/hosts')->unwrap();
echo $file->path()->toString(); // /etc/hosts
$file->close();
```

#### Size

Gets the file size using the open handle (via `fstat`).

```php
$file = File::open('/path/to/document.pdf')->unwrap();
$size = $file->size()->unwrap();
echo 'File size: ' . $size . ' bytes';
$file->close();
```

#### Metadata

Gets a metadata snapshot for this file. See the [Metadata](#metadata) section for details on the returned object.

```php
$file = File::open('/path/to/document.txt')->unwrap();
$metadata = $file->metadata()->unwrap();

if ($metadata->isFile()) {
    echo 'Size: ' . $metadata->size() . ' bytes';
}

$file->close();
```

Chaining metadata retrieval:

```php
$sizeInKb = File::withOpen('/path/to/doc.txt', fn(File $f) =>
    $f->metadata()->map(fn(Metadata $m) => $m->size() / 1024)
);
```

#### Set Permissions

Applies permissions to this file.

```php
$file = File::open('/path/to/script.sh')->unwrap();
$file->setPermissions(Permissions::create(0755))->unwrap();
$file->close();
```

#### Set Modified

Sets the last modification time.

```php
$file = File::open('/path/to/document.txt')->unwrap();
$file->setModified(SystemTime::now())->unwrap();
$file->close();
```

#### Set Times

> [!WARNING]
> Creation time (`setCreated`) only takes effect on Windows and macOS. On Linux, this timestamp is silently ignored.

Sets multiple timestamps at once. `FileTimes` uses an immutable builder pattern where each setter returns a new instance.

```php
$now = SystemTime::now();
$times = FileTimes::new()
    ->setAccessed($now)
    ->setModified($now);

$file = File::open('/path/to/document.txt')->unwrap();
$file->setTimes($times)->unwrap();
$file->close();
```

### Lifecycle

#### Close

Closes the file handle. Can be called multiple times safely (subsequent calls are no-ops). The destructor also closes the handle as a safety net, but explicit `close()` is preferred.

```php
$file = File::open('/path/to/file.txt')->unwrap();
// ... operations ...
$file->close();
$file->close(); // Safe, no-op
```

#### With Open (Scoped Pattern)

Opens a file, passes it to a callback, and closes it automatically. This is the recommended pattern for short-lived file operations because it guarantees cleanup.

```php
$result = File::withOpen('/path/to/data.txt', function (File $file): string {
    return $file->readAll()->unwrap()->toString();
});

if ($result->isOk()) {
    echo $result->unwrap();
}
```

Monadic style with `withOpen`:

```php
// Read and transform in one expression
$upper = File::withOpen('/path/to/data.txt', fn(File $f) =>
    $f->readAll()->map(fn(Str $c) => $c->toUppercase()->toString())
)->andThen(fn(Result $r) => $r);
```

## FileSystem

The `FileSystem` class provides static methods for one-shot filesystem operations. It uses `file_get_contents` / `file_put_contents` internally for simple read/write, with no delegation to `File`. For streaming or complex multi-step operations on the same file, use `File` directly. All path parameters accept both `string` and `Path` objects.

### Reading Operations

#### Read

Reads file contents as a `Result<Str, FileNotFound|ReadFailed|InvalidFileType>`.

```php
// Monadic pipeline: read -> transform -> chain
$lineCount = FileSystem::read('/etc/hosts')
    ->map(fn(Str $content) => $content->lines()->size());

// With fallback via orElse
$content = FileSystem::read('/etc/hosts.local')
    ->orElse(fn() => FileSystem::read('/etc/hosts'));
```

Imperative style:

```php
$result = FileSystem::read('/etc/hosts');

if ($result->isOk()) {
    $content = $result->unwrap();
    $lines = $content->lines();
    echo 'Lines: ' . $lines->size();
}
```

#### Read Bytes

Reads file contents as a `Result<Sequence<int>, FileNotFound|ReadFailed|InvalidFileType>`. Each element is a native `int` in the range 0--255.

```php
$bytes = FileSystem::readBytes('/path/to/image.jpg')->unwrap();

$first = $bytes->get(0)->unwrap();
if ($first === 0x89) {
    echo 'Likely a PNG file';
}
```

If you need `Integer` wrappers:

```php
$integers = FileSystem::readBytes('/path/to/file.bin')
    ->map(fn(Sequence $seq) => $seq->map(fn(int $b) => Integer::of($b)));
```

#### Read Directory

Reads directory contents as a `Sequence<Path>`, automatically excluding `.` and `..` entries.

```php
$entries = FileSystem::readDir('/var/log')->unwrap();

$entries->forEach(static function (Path $entry) {
    $name = $entry->fileName()->unwrapOr(Str::of('[No Name]'));

    if ($entry->isFile()) {
        echo "File: $name\n";
    } elseif ($entry->isDir()) {
        echo "Directory: $name\n";
    }
});

// Filter for .log files
$logFiles = $entries->filter(
    static fn(Path $entry) => $entry->isFile() && $entry->extension()->match(
        fn($ext) => $ext->toString() === 'log',
        fn() => false,
    ),
);
```

#### Read Symlink

Reads the target path of a symlink.

```php
$target = FileSystem::readSymlink('/var/www/html')->unwrap();
echo 'Points to: ' . $target->toString();
```

### Writing Operations

#### Write

Writes content to a file. Creates the file if it does not exist, overwrites if it does.

```php
$result = FileSystem::write('/path/to/test.txt', 'Hello, world!');

if ($result->isErr()) {
    echo 'Failed: ' . $result->unwrapErr()->getMessage();
}
```

Chaining a read-modify-write cycle:

```php
$result = FileSystem::read('/path/to/data.txt')
    ->map(fn(Str $c) => $c->toUppercase())
    ->andThen(fn(Str $c) => FileSystem::write('/path/to/data.txt', $c));
```

### File Operations

#### Copy File

Copies a file using PHP's native `copy()`, which streams the content without loading it entirely into memory. Overwrites the destination if it already exists.

```php
FileSystem::copyFile('/path/to/source.txt', '/path/to/destination.txt')->unwrap();
```

#### Rename File

Renames or moves a file. Replaces the destination if it exists.

```php
FileSystem::renameFile('/path/to/old.txt', '/path/to/new.txt')->unwrap();
```

#### Remove File

Permanently deletes the file. This does not move the file to a trash folder.

```php
FileSystem::removeFile('/path/to/unwanted.txt')->unwrap();
```

### Directory Operations

#### Create Directory

Creates a new directory. Does not create parent directories.

```php
FileSystem::createDir('/path/to/new-directory')->unwrap();
```

#### Create Directory All

Recursively creates a directory and all parent directories. Equivalent to `mkdir -p`.

```php
FileSystem::createDirAll('/path/to/deeply/nested/directory')->unwrap();
```

#### Remove Directory

Removes an empty directory.

```php
$result = FileSystem::removeDir('/path/to/empty-directory');

if ($result->isErr()) {
    $error = $result->unwrapErr();

    if ($error instanceof DirectoryNotEmpty) {
        echo 'Directory is not empty';
    }
}
```

#### Remove Directory All

> [!CAUTION]
> Permanently and recursively deletes all contents. Equivalent to `rm -rf`. There is no undo.

```php
FileSystem::removeDirAll('/path/to/directory')->unwrap();
```

#### Rename Directory

Renames or moves a directory. Falls back to system `mv`/`move` if PHP's `rename()` fails across filesystems.

```php
FileSystem::renameDir(Path::of('/old/dir'), Path::of('/new/dir'))->unwrap();
```

### Link Operations

#### Hard Link

Creates a hard link. Must be on the same filesystem.

```php
FileSystem::hardLink('/path/to/original.txt', '/path/to/hardlink.txt')->unwrap();
```

#### Symbolic Link

Creates a symbolic link. Can cross filesystem boundaries.

```php
FileSystem::symLink('/path/to/target', '/path/to/link')->unwrap();
```

### Metadata Operations

#### Metadata

Gets metadata for a path.

```php
$metadata = FileSystem::metadata('/etc/hosts')->unwrap();
echo 'Size: ' . $metadata->size() . ' bytes';
echo 'Modified: ' . date('Y-m-d H:i:s', $metadata->modified()->seconds());
```

Chaining metadata retrieval with transformation:

```php
$isLarge = FileSystem::metadata('/path/to/file.bin')
    ->map(fn(Metadata $m) => $m->size() > 1_000_000);

// Retrieve permissions, falling back to a default
$perms = FileSystem::metadata('/path/to/file.txt')
    ->map(fn(Metadata $m) => $m->permissions())
    ->orElse(fn() => Result::ok(Permissions::create(0644)));
```

#### Set Permissions

Sets permissions on a path.

```php
FileSystem::setPermissions('/path/to/file.txt', Permissions::create(0644))->unwrap();
```

## FileTimes

The `FileTimes` class represents the various timestamps that can be set on a file. It uses an immutable builder pattern: each setter returns a new `FileTimes` instance, leaving the original unchanged.

### Creation

```php
$times = FileTimes::new(); // No timestamps set
```

### Setting Times

```php
$now = SystemTime::now();

$times = FileTimes::new()
    ->setAccessed($now)
    ->setModified($now)
    ->setCreated($now); // Only effective on Windows/macOS
```

### Getting Times

Each getter returns `Option<SystemTime>`.

```php
$times = FileTimes::new()->setModified(SystemTime::now());

$times->modified()->isSome(); // true
$times->accessed()->isSome(); // false
$times->created()->isSome();  // false
```

Chaining with Option:

```php
$modifiedEpoch = $times->modified()
    ->map(fn(SystemTime $t) => $t->seconds());

$modifiedEpoch->match(
    fn(int $ts) => date('Y-m-d H:i:s', $ts),
    fn() => 'No modification time set',
);
```

## FileType

`FileType` is a PHP native enum representing the type of a filesystem entry. It is determined via `lstat`-level checks.

> [!IMPORTANT]
> If a path is a symlink, `FileType::of()` reports `Symlink` regardless of the link target. To inspect the target type, resolve the symlink first with `FileSystem::readSymlink()`.

### Creation

```php
// From a path (uses lstat-level checks)
$type = FileType::of('/etc/hosts');

// From a stat mode value
$type = FileType::fromMode($stat['mode']);
```

### Type Checking

```php
$type = FileType::of('/path');

$type->isFile();    // true for regular files
$type->isDir();     // true for directories
$type->isSymLink(); // true for symbolic links
```

### Enum Cases

```php
FileType::RegularFile
FileType::Directory
FileType::Symlink
```

## Metadata

`Metadata` is an immutable snapshot taken via a single `lstat()` call. All properties reflect the filesystem state at the time of creation and do not update if the underlying file changes afterward.

> [!IMPORTANT]
> Because `Metadata` is a point-in-time snapshot, properties may become stale. If you need fresh data, call `Metadata::of()` again.

### Creation

```php
$result = Metadata::of('/etc/hosts');

if ($result->isOk()) {
    $metadata = $result->unwrap();
    echo 'Type: ' . ($metadata->isFile() ? 'file' : 'directory');
}
```

Monadic style:

```php
$fileSize = Metadata::of('/etc/hosts')
    ->map(fn(Metadata $m) => $m->size());

// Chain into further operations
$isRecent = Metadata::of('/var/log/syslog')
    ->map(fn(Metadata $m) => $m->modified()->seconds())
    ->map(fn(int $ts) => $ts > time() - 3600);
```

### Properties

```php
$metadata = Metadata::of('/path/to/file.txt')->unwrap();

// Path
$metadata->path();

// File type (returns FileType enum)
$metadata->fileType();

// Convenience type checks
$metadata->isFile();
$metadata->isDir();
$metadata->isSymLink();

// Size
$metadata->size(); // int

// Permissions (mode bits from stat)
$metadata->permissions(); // Permissions

// Timestamps
$metadata->modified();  // SystemTime
$metadata->accessed();  // SystemTime
$metadata->created();   // SystemTime (ctime on Unix)
```

## Permissions

The `Permissions` class represents file permission mode bits.

### Creation

```php
// From mode value
$perms = Permissions::create(0644);

// From filesystem path
$perms = Permissions::of('/etc/hosts');
```

### Checking

```php
$perms = Permissions::create(0755);

$perms->isReadable();   // true (checks 0444 bits)
$perms->isWritable();   // true (checks 0222 bits)
$perms->isExecutable(); // true (checks 0111 bits)

$perms->mode(); // int (raw mode value)
```

### Modification

```php
$perms = Permissions::create(0644);
$execPerms = $perms->setMode(0755); // Returns new Permissions
```

### Application

```php
$perms = Permissions::create(0644);
$perms->apply('/path/to/file.txt')->unwrap();
```

## Error Handling

All potentially failing operations return `Result` types. The error types reside in the `Error/` namespace:

| Error | When |
|---|---|
| `AlreadyExists` | File/directory already exists |
| `CreateFailed` | Failed to create file/directory |
| `DirectoryNotEmpty` | Directory is not empty (for `removeDir`) |
| `DirectoryNotFound` | Directory does not exist |
| `FileNotFound` | File does not exist |
| `InvalidFileType` | Path is not the expected type (e.g., directory when file expected) |
| `InvalidMetadata` | `stat()` failed for reasons other than missing file |
| `LinkFailed` | Hard link creation failed |
| `PermissionDenied` | Insufficient permissions |
| `ReadFailed` | Read operation failed |
| `RemoveFailed` | File/directory removal failed |
| `RenameFailed` | Rename operation failed |
| `SymlinkFailed` | Symbolic link creation failed |
| `TimestampFailed` | Timestamp modification failed |
| `WriteFailed` | Write operation failed |

### Patterns

#### Imperative (if/else)

```php
$result = FileSystem::read('/path/to/file.txt');

if ($result->isOk()) {
    echo $result->unwrap()->toString();
} else {
    echo 'Error: ' . $result->unwrapErr()->getMessage();
}
```

#### Pattern Matching (match)

```php
$content = FileSystem::read('/path/to/file.txt')->match(
    fn(Str $content) => $content->toString(),
    fn(\Exception $error) => 'Error: ' . $error->getMessage(),
);
```

#### Monadic Chaining (andThen, map)

`map` transforms the success value without changing the Result wrapper. `andThen` chains into another operation that itself returns a Result, flattening the nesting.

```php
// map: transform the Ok value
$lineCount = FileSystem::read('/path/to/file.txt')
    ->map(fn(Str $content) => $content->lines()->size());

// andThen: chain into another Result-returning operation
$config = FileSystem::read('/path/to/config.json')
    ->map(fn(Str $content) => $content->toString())
    ->andThen(fn(string $json) => Json::decode($json));
```

#### Error Recovery (orElse)

`orElse` provides a fallback when the initial operation fails. The callback receives the error and must return a new Result.

```php
// Try primary path, fall back to secondary
$content = FileSystem::read('/etc/app/config.local.json')
    ->orElse(fn() => FileSystem::read('/etc/app/config.json'));
```

#### Error Transformation (mapErr)

`mapErr` transforms the error value while leaving Ok values untouched. This is useful for unifying error types or adding context.

```php
$result = FileSystem::read('/path/to/file.txt')
    ->mapErr(fn(\Exception $e) => new \RuntimeException(
        'Configuration load failed: ' . $e->getMessage(),
        previous: $e,
    ));
```

#### Combined Pipeline

A complete example combining multiple combinators:

```php
$settings = FileSystem::read('/etc/app/settings.json')
    ->orElse(fn() => FileSystem::read('/etc/app/settings.defaults.json'))
    ->map(fn(Str $content) => $content->toString())
    ->andThen(fn(string $json) => Json::decode($json))
    ->mapErr(fn(\Exception $e) => new \RuntimeException(
        'Failed to load settings: ' . $e->getMessage(),
        previous: $e,
    ))
    ->match(
        fn(array $config) => $config,
        fn(\RuntimeException $e) => throw $e,
    );
```

### Architecture

| | Immutable / Config | Mutable / OS Resource |
|---|---|---|
| **Process** | `Command` / `ProcessBuilder` | `Process` |
| **FileSystem** | `FileSystem` (static one-shot ops) | `File` (handle-based) |
