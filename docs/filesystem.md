# FileSystem Module

> [!NOTE]
> **Design Philosophy**: This module separates one-shot operations (`FileSystem` static methods) from handle-based streaming (`File` class). `FileSystem` uses `file_get_contents` / `file_put_contents` for simple read/write, while `File` wraps a persistent OS file handle for streaming, seeking, and atomic writes. This mirrors the Rust separation between `std::fs` free functions and `std::fs::File`.

The FileSystem module provides types and operations for safely handling files, directories, and filesystem metadata. It offers type-safe error handling via Result types.

## Table of Contents

- [File](#file)
- [FileSystem](#filesystem)
- [FileTimes](#filetimes)
- [FileType](#filetype)
- [Metadata](#metadata)
- [Permissions](#permissions)
- [Error Handling](#error-handling)

## File

> [!IMPORTANT]
> `File` wraps a mutable OS file handle. It is intentionally NOT immutable, following the same pattern as `Process`. The handle remains open until `close()` is called or the object is destroyed.

The `File` class represents an open file handle for streaming and complex operations.

### Creation

#### Open

> [!TIP]
> Accepts both string paths and Path objects. The file must exist and be a regular file.

Opens an existing file for reading and writing.

```php
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

#### Create

> [!IMPORTANT]
> The parent directory must exist. Fails if the file already exists (exclusive create).

Creates a new empty file for reading and writing.

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

> [!TIP]
> Ideal for processing large files in chunks without loading everything into memory.

Reads a chunk of bytes from the current position. May return fewer bytes than requested if EOF is reached.

```php
$file = File::open('/path/to/large-file.bin')->unwrap();
$chunkSize = 1024;

while (true) {
    $chunk = $file->readChunk($chunkSize)->unwrap();

    if ($chunk->size()->toInt() === 0) {
        break;
    }

    // Process chunk...
}

$file->close();
```

#### Bytes

> [!CAUTION]
> Creates a Sequence containing one Integer per byte. For large files, this can use significant memory.

Reads the entire file as a Sequence of byte values (0-255). Rewinds to the start and reads all content.

```php
$file = File::open('/path/to/image.jpg')->unwrap();
$bytes = $file->bytes()->unwrap();

// Check file signature
$first = $bytes->get(0)->unwrap()->toInt();
$second = $bytes->get(1)->unwrap()->toInt();

if ($first === 0xFF && $second === 0xD8) {
    echo 'This is a JPEG image';
}

$file->close();
```

### Writing Operations

#### Write

Writes data at the current position. Returns the number of bytes written.

```php
$file = File::create('/path/to/output.txt')->unwrap();
$bytesWritten = $file->write('Hello, world!')->unwrap();
echo $bytesWritten->toInt(); // 13

// Also accepts Str objects
$file->write(Str::of('more data'))->unwrap();

$file->close();
```

#### Append

Seeks to end of file, then writes data. Returns the number of bytes written.

```php
$file = File::open('/var/log/app.log')->unwrap();
$file->append(date('Y-m-d H:i:s') . " Application started\n")->unwrap();
$file->close();
```

#### Write Atomic

> [!IMPORTANT]
> Provides atomicity via temp-file + rename. Either the entire write succeeds or the original file remains unchanged.

Replaces the entire file content atomically. The handle is reopened after the rename.

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
echo 'File size: ' . $size->toInt() . ' bytes';
$file->close();
```

#### Metadata

Gets a metadata snapshot for this file.

```php
$file = File::open('/path/to/document.txt')->unwrap();
$metadata = $file->metadata()->unwrap();

if ($metadata->isFile()) {
    echo 'Size: ' . $metadata->size()->toInt() . ' bytes';
}

$file->close();
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
> Creation time setting only works on Windows and macOS. On Linux, this timestamp is ignored.

Sets multiple timestamps at once.

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

Closes the file handle. Can be called multiple times safely.

```php
$file = File::open('/path/to/file.txt')->unwrap();
// ... operations ...
$file->close();
$file->close(); // Safe, no-op
```

> [!NOTE]
> The destructor (`__destruct`) also closes the handle as a safety net, but explicit `close()` is recommended.

#### With Open (Scoped Pattern)

Opens a file, passes it to a callback, and closes it automatically.

```php
$result = File::withOpen('/path/to/data.txt', function (File $file): string {
    return $file->readAll()->unwrap()->toString();
});

if ($result->isOk()) {
    echo $result->unwrap();
}
```

## FileSystem

> [!NOTE]
> Provides static methods for one-shot filesystem operations. Uses `file_get_contents` / `file_put_contents` internally for simple read/write (no delegation to `File`). For streaming or complex operations, use `File` directly.

The `FileSystem` class provides static methods for file and directory operations.

### Reading Operations

#### Read

Reads file contents as a Str.

```php
$result = FileSystem::read('/etc/hosts');

if ($result->isOk()) {
    $content = $result->unwrap();

    $lines = $content->lines();
    echo 'Lines: ' . $lines->size()->toInt();
}
```

#### Read Bytes

> [!CAUTION]
> Each byte becomes an `Integer` object, resulting in significant memory overhead.

Reads file contents as a Sequence of bytes.

```php
$bytes = FileSystem::readBytes('/path/to/image.jpg')->unwrap();

$first = $bytes->get(0)->unwrap()->toInt();
if ($first === 0x89) {
    echo 'Likely a PNG file';
}
```

#### Read Directory

> [!NOTE]
> Returns `Sequence<Path>`. Automatically excludes `.` and `..` entries.

Reads directory contents.

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

Writes content to a file. Creates the file if it doesn't exist, overwrites if it does.

```php
$result = FileSystem::write('/path/to/test.txt', 'Hello, world!');

if ($result->isErr()) {
    echo 'Failed: ' . $result->unwrapErr()->getMessage();
}
```

### File Operations

#### Copy File

> [!NOTE]
> Uses PHP's native `copy()`, which handles streaming without loading the full content into memory. Overwrites the destination if it exists.

Copies a file.

```php
FileSystem::copyFile('/path/to/source.txt', '/path/to/destination.txt')->unwrap();
```

#### Rename File

Renames or moves a file. Replaces the destination if it exists.

```php
FileSystem::renameFile('/path/to/old.txt', '/path/to/new.txt')->unwrap();
```

#### Remove File

> [!CAUTION]
> Permanently deletes the file. Does not move to trash.

Removes a file.

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
> Permanently and recursively deletes all contents. Equivalent to `rm -rf`.

Removes a directory and all its contents.

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
echo 'Size: ' . $metadata->size()->toInt() . ' bytes';
echo 'Modified: ' . date('Y-m-d H:i:s', $metadata->modified()->seconds()->toInt());
```

#### Set Permissions

Sets permissions on a path.

```php
FileSystem::setPermissions('/path/to/file.txt', Permissions::create(0644))->unwrap();
```

## FileTimes

> [!NOTE]
> Uses an immutable builder pattern where each setter returns a new FileTimes instance.

The `FileTimes` class represents the various timestamps that can be set on a file.

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

## FileType

> [!IMPORTANT]
> `FileType` is a PHP native enum. If a path is a symlink, it reports `Symlink` regardless of the link target.

The `FileType` enum represents the type of a filesystem entry.

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

> [!IMPORTANT]
> `Metadata` is an immutable snapshot taken via a single `lstat()` call. All properties reflect the filesystem state at the time of creation — they do not update if the file changes.

The `Metadata` class represents a point-in-time snapshot of file metadata.

### Creation

```php
$result = Metadata::of('/etc/hosts');

if ($result->isOk()) {
    $metadata = $result->unwrap();
    echo 'Type: ' . ($metadata->isFile() ? 'file' : 'directory');
}
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
$metadata->size(); // Integer

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

$perms->mode(); // Integer (raw mode value)
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

All potentially failing operations return `Result` types. Error types in `Error/`:

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

```php
// Using if/else
$result = FileSystem::read('/path/to/file.txt');

if ($result->isOk()) {
    echo $result->unwrap()->toString();
} else {
    echo 'Error: ' . $result->unwrapErr()->getMessage();
}

// Using match
$content = FileSystem::read('/path/to/file.txt')->match(
    fn(Str $content) => $content->toString(),
    fn(\Exception $error) => 'Error: ' . $error->getMessage(),
);

// Using andThen for chaining
$result = FileSystem::read('/path/to/config.json')
    ->map(fn(Str $content) => $content->toString())
    ->andThen(fn(string $json) => Json::decode($json));
```

### Architecture

| | Immutable / Config | Mutable / OS Resource |
|---|---|---|
| **Process** | `Command` / `ProcessBuilder` | `Process` |
| **FileSystem** | `FileSystem` (static one-shot ops) | `File` (handle-based) |
