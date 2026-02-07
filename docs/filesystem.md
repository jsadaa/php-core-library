# FileSystem Module

> [!NOTE]
> **Design Philosophy**: This module provides immutable file operations with explicit error handling. All file handles are opened and closed for each operation to ensure data consistency and prevent resource leaks. The design prioritizes safety and predictability over performance.

The FileSystem module provides a set of immutable types and operations for safely handling files, directories, and filesystem metadata. It offers type-safe error handling via Result types and follows functional programming principles.

## Table of Contents

- [DirectoryEntry](#directoryentry)
- [File](#file)
- [FileSystem](#filesystem)
- [FileTimes](#filetimes)
- [FileType](#filetype)
- [Metadata](#metadata)
- [Permissions](#permissions)
- [Error Handling](#error-handling)

## DirectoryEntry

> [!IMPORTANT]
> DirectoryEntry objects are immutable snapshots of filesystem state at creation time. They don't automatically update if the underlying filesystem changes. Each method call may perform filesystem operations, so cache results if you need to access multiple properties repeatedly.

The `DirectoryEntry` class represents an entry in a directory, whether that's a file, directory, or symlink.

### Creation

#### From Path

> [!TIP]
> Accepts both string paths and Path objects for convenience. String paths are automatically converted to Path objects internally.

> [!NOTE]
> Creates point-in-time snapshots that don't update automatically. Uses sensible defaults (0) for timestamps when filesystem operations fail.

Creates a new DirectoryEntry instance from a Path.

```php
// Create a DirectoryEntry from a path string
$entry = DirectoryEntry::of('/var/www/config.php');

// Create a DirectoryEntry from a Path object
$path = Path::of('/var/www/config.php');
$entry = DirectoryEntry::of($path);

// Check properties of the entry
$fileType = $entry->fileType(); // FileType instance
```

### Basic Operations

#### Path



Returns the path of the DirectoryEntry.

```php
$entry = DirectoryEntry::of(Path::of('/var/www/config.php'));
$path = $entry->path();
echo $path->toString(); // Outputs: "/var/www/config.php"

// Use path methods on the result
if ($path->exists()) {
    echo "The path exists in the filesystem";
}
```

#### File Name

> [!NOTE]
> **Directory Separator Handling**: Paths ending with directory separators (like `/var/www/`) will return `None` as they don't have a meaningful file name component.

Returns the file name of the DirectoryEntry.

```php
$entry = DirectoryEntry::of('/var/www/config.php');
$fileName = $entry->fileName(); // Option::some(Str::of("config.php"))

if ($fileName->isSome()) {
    echo "File name: " . $fileName->unwrap()->toString(); // Outputs: "File name: config.php"
}

// For a directory with a trailing slash
$dirEntry = DirectoryEntry::of('/var/www/');
$dirName = $dirEntry->fileName(); // Option::none()
```

#### File Type

> [!IMPORTANT]
> If the path is a symbolic link, `FileType` will indicate it's a symlink regardless of what the link points to. Use target resolution methods to determine the type of the symlink target.

Returns the file type of the DirectoryEntry.

```php
$entry = DirectoryEntry::of('/var/www/config.php');
$fileType = $entry->fileType();

if ($fileType->isFile()) {
    echo "This is a regular file";
} elseif ($fileType->isDir()) {
    echo "This is a directory";
} elseif ($fileType->isSymLink()) {
    echo "This is a symbolic link";
}

// Check directly for a specific type
$isDir = $entry->fileType()->isDir(); // false for a file
```

## Metadata

> [!IMPORTANT]
> The `isReadable()`, `isWritable()`, and `isExecutable()` methods check actual permissions considering user context, while `permissions()` returns theoretical mode bits.

> [!WARNING]
> This method can fail if the file is deleted or becomes inaccessible between DirectoryEntry creation and metadata access. In multi-threaded environments, metadata may become stale if other processes modify the file.

> [!NOTE]
> Metadata is retrieved fresh from the filesystem each time, ensuring accuracy but potentially impacting performance. Consider caching for repeated access.

Gets metadata for this directory entry.

```php
$entry = DirectoryEntry::of('/var/log/app.log');
$result = $entry->metadata();

if ($result->isOk()) {
    $metadata = $result->unwrap();
    echo "Size: " . $metadata->size()->toInt() . " bytes";
    echo "Modified: " . date('Y-m-d', $metadata->modified()->timestamp()->toInt());
} else {
    echo "Cannot access metadata: " . $result->unwrapErr()->getMessage();
}
```

## File

> [!IMPORTANT]
> Unlike traditional file APIs, this class doesn't maintain open file descriptors. Each operation opens, performs its action, and closes the file, ensuring thread safety, no resource leaks, up-to-date metadata, and atomic operations.

> [!WARNING]
> While File objects are immutable, the underlying filesystem is not. Operations return new File instances but may modify the actual file on disk.

The `File` class provides an interface for file operations, including reading, writing, and metadata access.

### Creation

#### From Existing File

> [!TIP]
> Only accepts regular files, not directories or special files. Permissions are validated when actual I/O operations are performed, not during file opening.

Opens an existing file.

```php
// Open a file for reading
$result = File::from('/etc/hosts');

if ($result->isOk()) {
    $file = $result->unwrap();
    $content = $file->read()->unwrap();
    echo $content->toString();
} else {
    echo "Failed to open file: " . $result->unwrapErr()->getMessage();
}

// Using match pattern
$content = File::from('/etc/hosts')->match(
    function($file) { return $file->read()->unwrap()->toString(); },
    function($err) { return "Error: " . $err->getMessage(); }
);
```

#### Create

> [!IMPORTANT]
> The parent directory must exist and be writable. The file is created immediately when this method succeeds - if it returns an error, no file is created.

> [!NOTE]
> Uses standard Unix octal notation (e.g., 0644, 0755) for permission specification.

Creates a new empty file.

```php
// Create a new file
$result = File::new('/path/to/file.txt');

if ($result->isOk()) {
    $file = $result->unwrap();
    // Write content to the file
    $file->write("Hello, world!")->unwrap();
    echo "File created successfully";
} else {
    echo "Failed to create file: " . $result->unwrapErr()->getMessage();
}

// Create a log file
$logFile = File::new('/var/log/app.log')->unwrap();
$logFile->write(date('Y-m-d H:i:s') . " Application started\n")->unwrap();
```

### Reading Operations

#### Read

> [!WARNING]
> **Memory Usage**: This method loads the entire file into memory. For large files, consider using `readRange()` or streaming approaches.

Reads the entire file as a Str.

```php
$file = File::from('/etc/hosts')->unwrap();
$result = $file->read();

if ($result->isOk()) {
    $content = $result->unwrap()->toString();
    echo "File content:\n$content";

    // Count lines in the file
    $lineCount = Str::of($content)->lines()->size()->toInt();
    echo "The file has $lineCount lines";
} else {
    echo "Failed to read file: " . $result->unwrapErr()->getMessage();
}

// One-liner for simple reading
$content = File::from('/etc/hostname')->unwrap()->read()->unwrap();
echo "Hostname: " . $content->toString();
```

#### Read Range

> [!IMPORTANT]
> May return fewer bytes than requested if EOF is encountered. Check the returned data length if you need exact byte counts.

> [!TIP]
> Ideal for processing large files in chunks without loading everything into memory.

Reads a specific range of bytes from the file.

```php
$file = File::from('/path/to/data.bin')->unwrap();

// Read 10 bytes starting at byte 100
$headerResult = $file->readRange(100, 10);

if ($headerResult->isOk()) {
    $header = $headerResult->unwrap()->toString();
    echo "File section: " . bin2hex($header);
}

// Read portions of a large file without loading it all
$file = File::from('/path/to/large-file.txt')->unwrap();
$chunkSize = 1024;  // 1 KB chunks
$fileSize = $file->size()->unwrap()->toInt();

for ($offset = 0; $offset < $fileSize; $offset += $chunkSize) {
    $chunk = $file->readRange($offset, $chunkSize)->unwrap();
    echo "Read " . $chunk->size()->toInt() . " bytes from offset $offset\n";
    // Process each chunk
}
```

#### Read From

> [!NOTE]
> Reads until end of file, so the amount of data returned depends on file size and offset position.

Reads bytes from a specific offset until EOF.

```php
$file = File::from('/path/to/data.bin')->unwrap();
$result = $file->readFrom(100);

if ($result->isOk()) {
    $data = $result->unwrap()->toString();
    echo "Data read from offset 100: " . bin2hex($data);
} else {
    echo "Failed to read from file: " . $result->unwrapErr()->getMessage();
}
```

#### Read Exact

> [!WARNING]
> Unlike `readRange()`, this method fails if it cannot read exactly the requested number of bytes. Essential for reading fixed-size headers, magic numbers, or structured binary data where partial reads indicate corruption.

Reads exactly the requested number of bytes from a specific offset.

```php
// Read a fixed-size header from a binary file
$file = File::from('/path/to/binary-file.dat')->unwrap();
$headerResult = $file->readExact(0, 16);

if ($headerResult->isOk()) {
    $header = $headerResult->unwrap()->toString();
    echo "Header read successfully: " . bin2hex($header);
} else {
    echo "Failed to read header: " . $headerResult->unwrapErr()->getMessage();
    echo "The file might be truncated or corrupt";
}
```

#### Bytes

> [!CAUTION]
> Creates a Vec containing one Integer per byte. For large files, this can use significant memory (roughly 5x the file size due to Integer object overhead).

> [!TIP]
> Each byte is represented as an `Integer` (0-255), making it easy to perform byte-level operations and comparisons.

Reads the file as a Sequence of byte values.

```php
$file = File::from('/path/to/image.jpg')->unwrap();
$result = $file->bytes();

if ($result->isOk()) {
    $bytes = $result->unwrap();
    echo "File size: " . $bytes->size()->toInt() . " bytes";

    // Check file signature/magic numbers
    $firstByte = $bytes->get(0)->unwrapOr(Integer::of(0));
    $secondByte = $bytes->get(1)->unwrapOr(Integer::of(0));

    if ($firstByte->eq(0xFF) && $secondByte->eq(0xD8)) {
        echo "This is a JPEG image";
    }

    // Count occurrences of a specific byte
    $nullBytes = $bytes->filter(fn(Integer $byte) => $byte->eq(0))->size();
    echo "File contains " . $nullBytes->toInt() . " null bytes";
}

// Process binary data - calculate simple checksum
$dataFile = File::from('/path/to/data.bin')->unwrap();
$bytes = $dataFile->bytes()->unwrap();
$checksum = $bytes->fold(Integer::of(0), fn(Integer $sum, Integer $byte) => $sum->add($byte));
```

### Writing Operations

#### Write
> [!WARNING]
> This method completely replaces file content. Unlike `writeAtomic()`, this operation is not atomic and may leave the file in an inconsistent state if interrupted. Use `append()` to add data without losing existing content.

> [!NOTE]
> Creates the file if it doesn't exist, completely replaces content if it does exist.

Writes data to the file.

```php
// Create a new file and write to it
$file = File::new('/path/to/output.txt')->unwrap();
$result = $file->write("Hello, world!");

if ($result->isOk()) {
    echo "Data written successfully";
} else {
    echo "Failed to write data: " . $result->unwrapErr()->getMessage();
}

// Update a configuration file
$configFile = File::from('/path/to/config.json')->unwrap();
$config = Json::decode($configFile->read()->unwrap()->toString())->unwrap();
$config['debug'] = true;
$configFile->write(Json::encode($config, JSON_PRETTY_PRINT)->unwrap())->unwrap();
```

#### Append

> [!IMPORTANT]
> Data is appended atomically at the filesystem level. Multiple processes can safely append to the same file.

> [!TIP]
> Ideal for log files where you need to add entries without overwriting existing data.

Appends data to the file.

```php
// Append to a log file
$logFile = File::from('/var/log/app.log')->unwrap();
$logEntry = date('Y-m-d H:i:s') . " - Application started\n";
$result = $logFile->append($logEntry);

if ($result->isOk()) {
    echo "Log entry added";
} else {
    echo "Failed to append to log: " . $result->unwrapErr()->getMessage();
}

// Add more data to an existing file
$file = File::from('/path/to/data.txt')->unwrap();
$file->append("\n----------\nAdditional data")->unwrap();
```

#### Write Atomic

> [!IMPORTANT]
> Provides atomicity - either the entire write succeeds or the original file remains unchanged. Uses temporary files and atomic rename operations, requiring write permissions on the parent directory.

> [!TIP]
> The `sync` parameter forces data to disk, ensuring durability but with performance impact. Use `true` for critical data, `false` for performance.

Writes data to the file atomically.

```php
// Update a critical configuration file safely
$configFile = File::from('/path/to/database-config.json')->unwrap();
$config = Json::decode($configFile->read()->unwrap()->toString())->unwrap();
$config['password'] = 'new-secure-password';

// Write atomically and sync to ensure durability
$result = $configFile->writeAtomic(Json::encode($config, JSON_PRETTY_PRINT)->unwrap(), true);

if ($result->isOk()) {
    echo "Configuration updated safely";
} else {
    echo "Failed to update configuration: " . $result->unwrapErr()->getMessage();
}

// Fast atomic write without sync
$cacheFile = File::from('/path/to/cache.dat')->unwrap();
$cacheData = serialize($cachedObjects);
$cacheFile->writeAtomic($cacheData, false)->unwrap();
```

### Modification Operations

#### Set Length

> [!CAUTION]
> Truncating to a smaller size permanently deletes data beyond the new length. This operation cannot be undone.

> [!NOTE]
> When extending files, the new space is filled with null bytes (0x00).

Sets the length of the file.

```php
// Truncate a file to zero length (empty it)
$file = File::from('/path/to/log.txt')->unwrap();
$result = $file->setSize(0);

if ($result->isOk()) {
    echo "File emptied successfully";
    // Now write new content
    $file->write("New log started: " . date('Y-m-d H:i:s'))->unwrap();
}

// Truncate a file to keep only the first 1024 bytes
$file = File::from('/path/to/large-file.txt')->unwrap();
$file->setSize(1024)->unwrap();
echo "File truncated to 1 KB";

// Create a fixed-size file (useful for certain database formats)
$dbFile = File::new('/path/to/database.dat')->unwrap();
$dbFile->setSize(4096)->unwrap(); // Create a 4KB file
echo "Created fixed-size database file";
```

### Metadata Operations

#### Size

> [!NOTE]
> Directory size behavior is platform-dependent and typically doesn't reflect the total size of contained files. For symlinks, returns the size of the link itself, not the target size. Special files behavior is filesystem-dependent.

Gets the size of the file in bytes.

```php
$file = File::from('/path/to/document.pdf')->unwrap();
$sizeResult = $file->size();

if ($sizeResult->isOk()) {
    $size = $sizeResult->unwrap();
    echo "File size: " . $size->toInt() . " bytes";

    if ($size->gt(Integer::of(1024 * 1024))) {
        echo "File is larger than 1 MB";
    }
}
```

#### Metadata

Gets metadata for this file.

```php
// Get file metadata
$file = File::from('/path/to/document.txt')->unwrap();
$result = $file->metadata();

if ($result->isOk()) {
    $metadata = $result->unwrap();

    // Check file properties
    if ($metadata->isFile()) {
        echo "This is a regular file";
    }

    // Get file size
    $size = $metadata->size();
    echo "File size: $size bytes";

    // Check timestamps
    $created = date('Y-m-d H:i:s', $metadata->created());
    $modified = date('Y-m-d H:i:s', $metadata->modified());
    echo "Created: $created, Last modified: $modified";

    // Check permissions
    $perms = $metadata->permissions();
    if ($perms->isWritable()) {
        echo "File is writable";
    }
}

// Check if a file was modified recently
$file = File::from('/path/to/config.ini')->unwrap();
$meta = $file->metadata()->unwrap();
$lastModified = $meta->modified();
$now = SystemTime::now();

if ($now->durationSince($lastModified)->unwrap()->seconds()->lt(3600)) {
    echo "Warning: File was modified in the last hour";
}
```

#### Set Permissions

> [!WARNING]
> Permission behavior varies significantly between operating systems and filesystems. Some permission combinations may not work as expected on Windows.

> [!IMPORTANT]
> Setting certain permissions may require elevated privileges, especially for system files.

Applies permissions to this file.

```php
// Create a file and set its permissions
$file = File::new('/path/to/script.sh')->unwrap();
$file->write("#!/bin/bash\necho 'Hello, world!'")->unwrap();

// Make it executable (0755)
$execPerms = Permissions::create(0755);
$result = $file->setPermissions($execPerms);

if ($result->isOk()) {
    echo "Permissions set successfully";
} else {
    echo "Failed to set permissions: " . $result->unwrapErr()->getMessage();
}

// Make a file read-only
$file = File::from('/path/to/important.conf')->unwrap();
$readonlyPerms = Permissions::create(0444);
$file->setPermissions($readonlyPerms)->unwrap();

// Check current permissions and modify them
$file = File::from('/path/to/data.txt')->unwrap();
$currentPerms = $file->metadata()->unwrap()->permissions();

// Make writable if currently read-only
if (!$currentPerms->isWritable()) {
    $writablePerms = $currentPerms->setMode(0644);
    $file->setPermissions($writablePerms)->unwrap();
    echo "Made file writable";
}
```

### Time Operations

#### Set Modified

> [!TIP]
> This is a shorthand for creating a FileTimes object with only the modification time set. Modification time is supported on all platforms.

> [!NOTE]
> Some filesystems have limited timestamp precision. Nanosecond-level precision may be rounded down.

Sets the last modification time of the file.

```php
// Open a file and update its modification time to now
$file = File::from('/path/to/document.txt')->unwrap();
$result = $file->setModified(SystemTime::now());

if ($result->isOk()) {
    echo "File modification time updated to now";
} else {
    echo "Failed to update timestamp: " . $result->unwrapErr()->getMessage();
}

// Set modification time to a specific date
$datetime = new \DateTimeImmutable('2023-01-01 12:00:00');
$systemTime = SystemTime::fromDateTimeImmutable($datetime);
$file->setModified($systemTime)->unwrap();

// Backdate a file by one day
$file = File::from('/path/to/report.pdf')->unwrap();
$yesterday = SystemTime::now()->sub(Duration::fromDays(1))->unwrap();
$file->setModified($yesterday)->unwrap();
echo "File backdated by one day";
```

#### Set Times

> [!WARNING]
> Creation time setting only works on Windows and macOS. On Linux, this timestamp is ignored.

> [!IMPORTANT]
> If both access and modification times are None in the FileTimes object, no changes are made to the file.

> [!NOTE]
> PHP only provides microsecond precision, so nanosecond values in SystemTime are rounded to the nearest microsecond.

Changes the timestamps of the underlying file.

```php
// Create FileTimes with both access and modification times
$now = SystemTime::now();
$times = FileTimes::new()
    ->setAccessed($now)
    ->setModified($now);

// Apply to a file
$file = File::from('/path/to/document.txt')->unwrap();
$result = $file->setTimes($times);

if ($result->isOk()) {
    echo "File timestamps updated successfully";
} else {
    echo "Failed to update timestamps: " . $result->unwrapErr()->getMessage();
}

// Set different access and modification times
$oneHourAgo = SystemTime::now()->sub(Duration::fromHours(1))->unwrap();
$twoDaysAgo = SystemTime::now()->sub(Duration::fromDays(2))->unwrap();

$times = FileTimes::new()
    ->setAccessed($oneHourAgo)
    ->setModified($twoDaysAgo);

$file->setTimes($times)->unwrap();
echo "Set access time to 1 hour ago and modification time to 2 days ago";

// On Windows/macOS, you can also set creation time
$now = SystemTime::now();
$times = FileTimes::new()
    ->setCreated($now)
    ->setModified($now)
    ->setAccessed($now);

$file->setTimes($times)->unwrap();
```

## FileSystem

> [!NOTE]
> This class provides static methods for common filesystem operations without requiring object instantiation. All operations are designed to be thread-safe, but concurrent modifications to the same files by multiple processes may lead to race conditions.

The `FileSystem` class provides static methods for file and directory operations.

### Path Operations

#### Canonicalize

> [!IMPORTANT]
> Unlike path normalization, canonicalization requires the path to exist on the filesystem to resolve symlinks and validate the path. Resolves ALL symlinks, which can be important for security-sensitive applications to prevent symlink attacks.

Returns the canonicalized form of a path.

```php
$path = Path::of("../config/../config.ini");
$result = FileSystem::canonicalize($path);
if ($result->isOk()) {
    $canonicalPath = $result->unwrap();
    echo $canonicalPath->toString(); // Outputs: /absolute/path/to/config.ini
}
```

#### Exists

> [!WARNING]
> File existence can change between check and use (TOCTOU - Time-of-Check-Time-of-Use). Don't rely on this for security-critical decisions.

> [!TIP]
> This is a fast operation that only checks for existence, not file type or permissions.

Checks if a path exists.

```php
// Check if a file exists
if (FileSystem::exists('/etc/hosts')) {
    echo "The hosts file exists";
}

// Check if a directory exists
$dirPath = Path::of('/var/www');
if (FileSystem::exists($dirPath)) {
    echo "The web directory exists";
} else {
    echo "The web directory does not exist";
}

// Use in a condition to prevent errors
$configPath = '/path/to/config.json';
if (!FileSystem::exists($configPath)) {
    // Create a default config
    FileSystem::write($configPath, '{"default": true}');
}
```

### Reading Operations

#### Read

Reads file contents as a Str.

```php
// Read a text file as a Str
$result = FileSystem::read('/etc/passwd');

if ($result->isOk()) {
    $content = $result->unwrap();

    // Use Str methods for processing
    if ($content->contains('root')) {
        echo "Found root user";
    }

    // Split by lines and process each line
    $lines = $content->lines();
    $lines->forEach(function(Str $line) {
        if ($line->startsWith('#')) {
            echo "Comment: " . $line->drop(1)->toString() . "\n";
        }
    });

    // Count occurrences of a substring
    $count = $content->matches('/bin/sh')->size();
    echo "Found " . $count->toInt() . " users with /bin/sh shell";
}
```

#### Read Bytes

> [!CAUTION]
> Each byte becomes an `Integer` object, resulting in significant memory overhead. Consider streaming for large files.

Reads file contents as bytes.

```php
// Read a binary file as bytes
$result = FileSystem::readBytes('/path/to/image.jpg');

if ($result->isOk()) {
    $bytes = $result->unwrap();
    echo "File size: " . $bytes->size()->toInt() . " bytes";

    // Process binary data - for example, check file signature/magic numbers
    $firstByte = $bytes->get(0)->unwrapOr(Integer::of(0));
    $secondByte = $bytes->get(1)->unwrapOr(Integer::of(0));

    // Check if it's a PNG file (signature: 89 50 4E 47 ...)
    if ($firstByte->eq(0x89) && $secondByte->eq(0x50)) {
        echo "This is likely a PNG file";
    }
}
```

#### Read Directory

> [!WARNING]
> For directories with many entries, consider implementing pagination or filtering to avoid memory issues.

> [!NOTE]
> The order of directory entries is filesystem-dependent. Automatically excludes "." and ".." entries but includes other hidden files (those starting with "." on Unix systems).

Reads directory contents.

```php
// List all files in a directory
$result = FileSystem::readDir('/var/log');

if ($result->isOk()) {
    $entries = $result->unwrap();

    // Iterate through directory entries
    $entries->forEach(static function(DirectoryEntry $entry) {
        $path = $entry->path();
        $name = $entry->fileName()->unwrapOr(Str::of('[No Name]'));

        if ($entry->fileType()->isFile()) {
            echo "File: $name\n";
        } elseif ($entry->fileType()->isDir()) {
            echo "Directory: $name\n";
        } elseif ($entry->fileType()->isSymLink()) {
            echo "Symlink: $name\n";
        }
    });

    // Filter for only regular files with a .log extension
    $logFiles = $entries->filter(
        static fn(DirectoryEntry $entry) => $entry->fileType()->isFile() && $entry->path()->extension()->match(
            fn($ext) => $ext->toString() === 'log',
            fn() => false
        ),
    );

    echo "Found " . $logFiles->size()->toInt() . " log files";
}
```

#### Read Symlink

> [!IMPORTANT]
> This method only reads the symlink target path; it doesn't verify that the target exists or is accessible. If the target is also a symlink, this method doesn't resolve the chain.

Reads the target path of a symlink.

```php
// Assuming '/var/www/html' is a symlink to '/srv/www/html'
$result = FileSystem::readSymlink('/var/www/html');

if ($result->isOk()) {
    $target = $result->unwrap();
    echo "Symlink points to: " . $target->toString(); // Outputs: "/srv/www/html"

    if ($target->exists()) {
        echo "Target exists";
    } else {
        echo "Broken symlink - target does not exist";
    }
} else {
    // Will return an InvalidFileType error if the path is not a symlink
    echo "Error: " . $result->unwrapErr()->getMessage();
}
```

### Writing Operations

#### Write

Writes content to a file.

```php
// Write to a file
$content = "Hello, world!\nThis is a test file.";
$result = FileSystem::write('/path/to/test.txt', $content);

if ($result->isOk()) {
    echo "File written successfully";
} else {
    echo "Failed to write file: " . $result->unwrapErr()->getMessage();
}

// Create a configuration file
$config = Json::encode([
    'database' => [
        'host' => 'localhost',
        'user' => 'app_user',
        'password' => 'secret',
    ],
    'debug' => true
], JSON_PRETTY_PRINT)->unwrap();

$writeResult = FileSystem::write('/path/to/config.json', $config);
```

### File Operations

#### Copy File

> [!CAUTION]
> Loads the entire source file into memory during copying. Not suitable for very large files.

> [!NOTE]
> Copies file permissions from source to destination but doesn't preserve all metadata (timestamps, extended attributes, etc.).

Copies a file.

```php
// Copy a file
$result = FileSystem::copyFile('/path/to/source.txt', '/path/to/destination.txt');

if ($result->isOk()) {
    echo "File copied successfully";
} else {
    echo "Failed to copy file: " . $result->unwrapErr()->getMessage();
}

// Copy using Path objects
$sourcePath = Path::of('/etc/php.ini');
$backupPath = Path::of('/etc/php.ini.backup');
$copyResult = FileSystem::copyFile($sourcePath, $backupPath);
```

#### Rename File

> [!TIP]
> Rename operations are typically atomic at the filesystem level, making them faster and safer than copy+delete for moving files.

> [!WARNING]
> Renaming across different filesystems may fail. The operation works best within the same filesystem.

Renames or moves a file.

```php
// Rename a file
$result = FileSystem::renameFile('/path/to/old-name.txt', '/path/to/new-name.txt');

if ($result->isOk()) {
    echo "File renamed successfully";
} else {
    echo "Failed to rename file: " . $result->unwrapErr()->getMessage();
}

// Move a file to a different directory
$result = FileSystem::renameFile('/tmp/upload.txt', '/var/www/uploads/file.txt');
```

#### Remove File

> [!CAUTION]
> This operation permanently deletes the file. It doesn't move files to trash/recycle bin.

> [!NOTE]
> Only works on regular files. Attempting to remove directories will result in an error.

Removes a file.

```php
// Remove a file
$result = FileSystem::removeFile('/path/to/unwanted-file.txt');

if ($result->isOk()) {
    echo "File removed successfully";
} else {
    echo "Failed to remove file: " . $result->unwrapErr()->getMessage();
}
```

### Directory Operations

#### Create Directory

> [!NOTE]
> Only creates the specified directory, not parent directories. Use `createDirAll()` for recursive creation. Creates directories with 0777 permissions, which may be modified by the system umask.

Creates a new directory.

```php
// Create a directory
$result = FileSystem::createDir('/path/to/new-directory');

if ($result->isOk()) {
    echo "Directory created successfully";
} else {
    echo "Failed to create directory: " . $result->unwrapErr()->getMessage();
}

// Use with Path object
$dirPath = Path::of('/var/www/uploads');
$result = FileSystem::createDir($dirPath);
```

#### Create Directory All

> [!WARNING]
> If the operation fails partway through, some parent directories may have been created, leaving the filesystem in a partially completed state.

> [!TIP]
> Equivalent to `mkdir -p` on Unix systems.

Recursively creates a directory and all parent directories.

```php
// Create a deeply nested directory structure
$result = FileSystem::createDirAll('/path/to/deeply/nested/directory');

if ($result->isOk()) {
    echo "Directory and parents created successfully";
} else {
    echo "Failed to create directory structure: " . $result->unwrapErr()->getMessage();
}

// Practical usage when creating app directories
$appDataPath = '/var/www/app/data/user/123/uploads';
$result = FileSystem::createDirAll($appDataPath);
```

#### Remove Directory

> [!NOTE]
> Only removes empty directories. Use `removeDirAll()` to remove directories with contents. Checks for all files including hidden files when determining if a directory is empty.

Removes an empty directory.

```php
// Remove an empty directory
$result = FileSystem::removeDir('/path/to/empty-directory');

if ($result->isOk()) {
    echo "Directory removed successfully";
} else {
    $error = $result->unwrapErr();
    if ($error instanceof DirectoryNotEmpty) {
        echo "Directory is not empty";
    } else {
        echo "Failed to remove directory: " . $error->getMessage();
    }
}

// To remove a non-empty directory, use removeDirAll instead
```

#### Remove Directory All

> [!CAUTION]
> This operation permanently and recursively deletes all contents. Use with extreme caution. If the operation fails partway through, the directory structure may be partially removed.

> [!TIP]
> Equivalent to `rm -rf` on Unix systems.

Removes a directory and all its contents.

```php
// Remove a directory and all its contents
$result = FileSystem::removeDirAll('/path/to/directory');

if ($result->isOk()) {
    echo "Directory and its contents removed successfully";
} else {
    echo "Failed to remove directory: " . $result->unwrapErr()->getMessage();
}

// Remove a temporary directory
$tmpDir = '/tmp/app-temp-' . uniqid();
// ... do some work with the temp directory ...
$cleanupResult = FileSystem::removeDirAll($tmpDir);
```

### Link Operations

#### Hard Link

> [!IMPORTANT]
> Hard links can only be created within the same filesystem. Hard links share the same inode, meaning changes to one link affect all links to the same file.

> [!NOTE]
> Most systems don't allow hard links to directories (Linux/Unix restriction).

Creates a hard link.

```php
// Create a hard link
$result = FileSystem::hardLink('/path/to/original.txt', '/path/to/hardlink.txt');

if ($result->isOk()) {
    echo "Hard link created successfully";
} else {
    echo "Failed to create hard link: " . $result->unwrapErr()->getMessage();
}

// Modifications to either file will affect both since they share the same inode
FileSystem::write('/path/to/hardlink.txt', 'This updates both files');
$content = FileSystem::read('/path/to/original.txt')->unwrap();
// $content now contains "This updates both files"
```

#### Symbolic Link

> [!TIP]
> Unlike hard links, symbolic links can point across filesystem boundaries. Symbolic links can be created even if the target doesn't exist, resulting in "broken" links.

> [!NOTE]
> Relative paths in symlinks are resolved relative to the symlink's location, not the current working directory.

Creates a symbolic link.

```php
// Create a symbolic link to a file
$result = FileSystem::symLink('/path/to/original.txt', '/path/to/symlink.txt');

if ($result->isOk()) {
    echo "Symbolic link created successfully";
} else {
    echo "Failed to create symbolic link: " . $result->unwrapErr()->getMessage();
}

// Relative symlinks (useful when paths might change)
$result = FileSystem::symLink('../shared/config.php', 'config.php');
```

### Metadata Operations

#### Metadata

Gets metadata for a path.

```php
// Using a Path object
$result = FileSystem::metadata(Path::of('/etc/hosts'));

// Using a string path
$result = FileSystem::metadata('/etc/hosts');

if ($result->isOk()) {
    $metadata = $result->unwrap();
    echo "File size: " . $metadata->size() . " bytes";
    echo "Last modified: " . date('Y-m-d H:i:s', $metadata->modified());
} else {
    echo "Error: " . $result->unwrapErr()->getMessage();
}
```

#### Symlink Metadata

> [!IMPORTANT]
> Returns metadata for the symlink itself, not its target. Use regular `metadata()` to get target metadata.

Gets metadata for a symlink.

```php
// Assuming '/var/www/html' is a symlink to '/srv/www/html'
$result = FileSystem::symlinkMetadata('/var/www/html');

if ($result->isOk()) {
    $metadata = $result->unwrap();

    // This checks if the path is a symlink
    if ($metadata->isSymLink()) {
        echo "This is a symbolic link";
    }

    // Shows when the symlink was created, not when its target was created
    echo "Symlink created: " . date('Y-m-d H:i:s', $metadata->created());
} else {
    // This will happen if the path is not a symlink
    echo "Error: " . $result->unwrapErr()->getMessage();
}
```

#### Set Permissions

Sets permissions on a path.

```php
// Create writable permissions (0644)
$perms = Permissions::create(0644);

// Apply to a file
$result = FileSystem::setPermissions('/path/to/file.txt', $perms);

if ($result->isOk()) {
    echo "Permissions set successfully";
} else {
    echo "Failed to set permissions: " . $result->unwrapErr()->getMessage();
}

// Make a file executable (0755)
$execPerms = Permissions::create(0755);
$result = FileSystem::setPermissions('/path/to/script.sh', $execPerms);

// Make a file read-only
$readonlyPerms = Permissions::create(0444);
$result = FileSystem::setPermissions('/path/to/important.conf', $readonlyPerms);
```

## FileTimes

> [!NOTE]
> Uses an immutable builder pattern where each setter returns a new FileTimes instance with the specified timestamp set. Creation time is only meaningful on Windows and macOS.

The `FileTimes` class represents the various timestamps that can be set on a file.

### Creation

#### New

> [!NOTE]
> Creates a FileTimes with no timestamps set. Use setter methods to specify which timestamps to modify.

Creates a new FileTimes with no times set.

```php
// Create an empty FileTimes instance
$times = FileTimes::new();

// Verify that no times are set
$hasAccessTime = $times->accessed()->isSome(); // false
$hasModifiedTime = $times->modified()->isSome(); // false

// Setting timestamps later
$times = $times->setModified(SystemTime::now());
```

### Setting Times

#### Set Accessed

> [!NOTE]
> Each setter returns a new FileTimes object, preserving immutability of the original instance.

Sets the last access time.

```php
// Create a timestamp for one hour ago
$oneHourAgo = SystemTime::fromTimestamp(time() - 3600);
// (or)
$oneHourAgo = SystemTime::now()->sub(Duration::fromHours(1))->unwrap();

// Set the access time
$times = FileTimes::new()->setAccessed($oneHourAgo);

// Use with a file
$file->setTimes($times);

// Chain with other timestamp setters
$times = FileTimes::new()
    ->setAccessed($oneHourAgo)
    ->setModified(SystemTime::now());
```

#### Set Modified

Sets the last modified time.

```php
// Set the modification time to now
$times = FileTimes::new()->setModified(SystemTime::now());

// Apply to a file
$file->setTimes($times);

// Setting only the modification time is common enough that there's
// a convenience method on File:
$file->setModified(SystemTime::now());

// Set modified time to a specific date (via DateTimeImmutable)
$datetime = new \DateTimeImmutable('2023-01-01 12:00:00');
$systemTime = SystemTime::fromDateTimeImmutable($datetime);
$times = FileTimes::new()->setModified($systemTime);
```

#### Set Created

> [!WARNING]
> Only effective on Windows and macOS. Unix/Linux systems will ignore this setting.

Sets the creation time.

```php
// Set the creation time to now
$times = FileTimes::new()->setCreated(SystemTime::now());

// Set all available timestamps
$now = SystemTime::now();
$times = FileTimes::new()
    ->setCreated($now)
    ->setModified($now)
    ->setAccessed($now);

// Apply to a file
$file->setTimes($times);
```

### Getting Times

#### Accessed

> [!NOTE]
> On systems mounted with `noatime`, access times may not be updated for performance reasons. Returns None if no access time was set, allowing for selective timestamp updates.

Gets the access time, if it was set.

```php
$times = FileTimes::new()->setAccessed(SystemTime::now());

// Check if access time is set
if ($times->accessed()->isSome()) {
    $accessTime = $times->accessed()->unwrap();
    echo "Access time is set";
}

// Use with match pattern
$result = $times->accessed()->match(
    function(SystemTime $time) { return "Access time is set"; },
    function() { return "No access time set"; }
);
```

#### Modified

> [!NOTE]
> **Cross-Platform**: Modification time is supported and meaningful on all platforms.

Gets the modified time, if it was set.

```php
$times = FileTimes::new()->setModified(SystemTime::now());

// Check if modification time is set
if ($times->modified()->isSome()) {
    $modTime = $times->modified()->unwrap();
    echo "Modification time is set";
}

// Get a default value if not set
$modTime = $times->modified()->unwrapOr(SystemTime::now());

// Convert to DateTimeImmutable if set
$dateTime = $times->modified()->match(
    function(SystemTime $time) { return $time->toDateTimeImmutable()->unwrap(); },
    function() { return new \DateTimeImmutable(); }
);
```

#### Created

> [!WARNING]
> **ctime vs Creation**: On Unix systems, this often returns the inode change time (ctime) rather than true creation time, as many Unix filesystems don't track creation time.

Gets the creation time, if it was set.

```php
$times = FileTimes::new()->setCreated(SystemTime::now());

// Check if creation time is set
if ($times->created()->isSome()) {
    $createTime = $times->created()->unwrap();
    echo "Creation time is set";
}

// Early return pattern
$createTime = $times->created();
if ($createTime->isNone()) {
    echo "No creation time available";
    return;
}

// Now we know it has a value
$time = $createTime->unwrap();
```

## FileType

> [!IMPORTANT]
> If a path is a symlink, FileType will report it as such regardless of what the symlink points to. This is different from following the symlink to determine the target type.

The `FileType` class represents the type of a filesystem entry (regular file, directory, or symbolic link).

### Creation

#### From Path

Creates a new FileType instance from a path.

```php
// Using a string path
$type = FileType::of('/etc/hosts');
if ($type->isFile()) {
    echo "This is a regular file";
}

// Using a Path object
$path = Path::of('/etc');
$dirType = FileType::of($path);
$isDir = $dirType->isDir(); // true

// Check a symbolic link
$linkType = FileType::of('/usr/bin/python3');
if ($linkType->isSymLink()) {
    echo "This is a symbolic link";
}
```

### Type Checking

#### Is File

> [!IMPORTANT]
> **Regular Files Only**: Returns true only for regular files, excluding directories, symlinks, pipes, devices, and other special file types.

Checks if the file type is a regular file.

```php
$type = FileType::of('/etc/hosts');
if ($type->isFile()) {
    echo "This is a regular file";
    // Safe to read file contents
}

// Use directly in a condition
if (FileType::of('/var/log/syslog')->isFile()) {
    echo "This is a regular file";
}

// Check before processing
$path = '/path/to/unknown';
if (FileType::of($path)->isFile()) {
    $content = File::from($path)->read()->match(
        fn($content) => $content,
        fn($error) => $error->getMessage(),
    );
}
```

#### Is Directory

> [!NOTE]
> Convenience method equivalent to `fileType()->isDir()` but more concise. Checks if the path itself is a directory, not whether a symlink points to a directory.

Checks if the file type is a directory.

```php
$type = FileType::of('/var/log');
if ($type->isDir()) {
    echo "This is a directory";
    // Safe to list directory contents
}

// Check if a path is a directory directly
$isDir = FileType::of('/tmp')->isDir(); // true

// Use before attempting to read directory
$path = '/some/path';
if (FileType::of($path)->isDir()) {
    $entries = FileSystem::readDir($path)->unwrap();
    $entries->forEach(fn($entry) => echo $entry->path()->fileName()->toString());
}
```

#### Is Symbolic Link

> [!NOTE]
> Checks the path itself, not whether it points to other symlinks. Only checks if the path is a symlink, not whether the symlink target exists or what type it is.

Checks if the file type is a symbolic link.

```php
// Assuming '/var/www/html' is a symlink to '/srv/www/html'
$type = FileType::of('/var/www/html');
if ($type->isSymLink()) {
    echo "This is a symbolic link";
    // You might want to resolve the target path
}

// Direct usage in a condition
if (FileType::of('/usr/bin/python3')->isSymLink()) {
    echo "This is a symbolic link";
}

// Check symlink vs regular file
$path = '/some/path';
$type = FileType::of($path);
if ($type->isSymLink()) {
    echo "Symbolic link - target might not exist";
} elseif ($type->isFile()) {
    echo "Regular file - guaranteed to exist";
}
```

## Metadata

The `Metadata` class represents metadata about a file or directory.

### Creation

#### From Path

Creates a new Metadata instance from a path.

```php
// Using a string path
$result = Metadata::of('/etc/hosts');

// Using a Path object
$path = Path::of('/etc/hosts');
$result = Metadata::of($path);

if ($result->isOk()) {
    $metadata = $result->unwrap();
    echo "File type: " . ($metadata->isFile() ? "Regular file" : "Directory");
    echo "Created: " . date('Y-m-d H:i:s', $metadata->created()->timestamp()->toInt());
} else {
    echo "Error: " . $result->unwrapErr()->getMessage();
}
```

### Basic Properties

#### Path

Gets the path of this Metadata instance.

```php
$metadata = Metadata::of('/etc/hosts')->unwrap();

$originalPath = $metadata->path();
echo $originalPath->toString(); // Outputs: "/etc/hosts"

// Useful for chaining operations
$parentDir = $metadata->path()->parent();
```

#### File Type

Gets the file type of this Metadata instance.

```php
$metadata = Metadata::of('/etc/hosts')->unwrap();

$fileType = $metadata->fileType();
if ($fileType->isFile()) {
    echo "This is a regular file";
} elseif ($fileType->isDir()) {
    echo "This is a directory";
} elseif ($fileType->isSymLink()) {
    echo "This is a symbolic link";
}

// Use the convenience methods instead for simpler checks
if ($metadata->isFile()) {
    echo "Same result, more concise";
}
```

#### Size

Gets the size of the file.

```php
$metadata = Metadata::of('/path/to/large-file.zip')->unwrap();

$size = $metadata->size();
echo "File size: " . $size->toInt() . " bytes";

// Format size in human-readable format
$formattedSize = $size->gt(1024 * 1024)
    ? $size->div(1024 * 1024)->unwrap()->toInt() . " MB"
    : ($size->gt(1024) ? $size->div(1024)->unwrap()->toInt() . " KB" : $size->toInt() . " bytes");
echo "Size: " . $formattedSize;
```

### Type Checking

#### Is Directory

Checks if this is a directory.

```php
$metadata = Metadata::of('/var/log')->unwrap();

if ($metadata->isDir()) {
    echo "This is a directory";
    // Safe to read directory contents
    $entries = FileSystem::readDir($metadata->path())->unwrap();
} else {
    echo "This is not a directory";
}
```

#### Is File

Checks if this is a regular file.

```php
$metadata = Metadata::of('/etc/hosts')->unwrap();

if ($metadata->isFile()) {
    echo "This is a regular file";
    // Safe to read file contents
    $content = FileSystem::read($metadata->path())->unwrap();
} else {
    echo "This is not a regular file";
}
```

#### Is Symbolic Link

Checks if this is a symbolic link.

```php
// Assuming '/var/www/html' is a symlink to '/srv/www/html'
$metadata = Metadata::of('/var/www/html')->unwrap();

if ($metadata->isSymLink()) {
    echo "This is a symbolic link";
    // Read the target path
    $target = FileSystem::readSymlink($metadata->path())->unwrap();
    echo "Points to: " . $target->toString();
} else {
    echo "This is not a symbolic link";
}
```

## Permissions

> [!IMPORTANT]
> This class works with theoretical permissions (mode bits) and doesn't consider runtime context like user identity or mount options. For contextual permission checks, use the methods on Metadata class instead.

#### Permissions

> [!NOTE]
> Returns the theoretical permissions

Gets the permissions of the file.

```php
$metadata = Metadata::of('/etc/hosts')->unwrap();

$permissions = $metadata->permissions();

if ($permissions->isReadable()) {
    echo "File is readable";
}

if ($permissions->isWritable()) {
    echo "File is writable";
}

if ($permissions->isExecutable()) {
    echo "File is executable";
}

// Get the mode as octal
printf("Permissions: %04o\n", $permissions->mode()->toInt());
```

#### Is Readable

> [!IMPORTANT]
> **Mode Bit Analysis**: Checks read bits (0444) in the file mode, not actual filesystem accessibility.
> **Runtime Check**: Checks actual readability considering current user, mount options, and system state.

Checks if the file is readable.

```php
$metadata = Metadata::of('/home/user/document.txt')->unwrap();

if ($metadata->isReadable()) {
    echo "File is readable";
}
```

#### Is Writable

> [!IMPORTANT]
> **Write Permission Bits**: Examines write bits (0222) in the mode, regardless of actual user permissions or filesystem state.
> **Effective Permissions**: Considers the current user's effective permissions, not just mode bits.

Checks if the file is writable.

```php
$metadata = Metadata::of('/home/user/document.txt')->unwrap();

if ($metadata->isWritable()) {
    echo "File is writable";
}
```

#### Is Executable

> [!IMPORTANT]
> **Execute Permission Check**: Checks execute bits (0111) in the mode, not whether the file can actually be executed by the current user.

> [!NOTE]
> **PATH Integration**: For files not directly executable, searches the PATH environment variable.

> [!WARNING]
> **Platform Differences**: Directory executability may behave differently on Windows systems.

Checks if the file is executable.

```php
$metadata = Metadata::of('/home/user/script.sh')->unwrap();

if ($metadata->isExecutable()) {
    echo "File is executable";
}
```

### Timestamps

#### Modified

Gets the last modified time.

```php
$metadata = Metadata::of('/etc/hosts')->unwrap();

$modTime = $metadata->modified();

// Format as human-readable date
echo "Last modified: " . date('Y-m-d H:i:s', $modTime->timestamp()->toInt());

// Check if file was modified in the last 24 hours
$oneDayAgo = Integer::of(time())->sub(24 * 60 * 60);
if ($modTime->timestamp()->gt($oneDayAgo)) {
    echo "File was modified in the last 24 hours";
}
```

#### Accessed

Gets the last accessed time.

```php
$metadata = Metadata::of('/var/log/syslog')->unwrap();

$accessTime = $metadata->accessed();

// Format as human-readable date
echo "Last accessed: " . date('Y-m-d H:i:s', $accessTime->timestamp()->toInt());

// Check if file was accessed in the last hour
$oneHourAgo = Integer::of(time())->sub(60 * 60);
if ($accessTime->timestamp()->gt($oneHourAgo)) {
    echo "File was accessed in the last hour";
}
```

#### Created

Gets the created time.

```php
$metadata = Metadata::of('/home/user/document.txt')->unwrap();

$createTime = $metadata->created();

// Format as human-readable date
echo "Created: " . date('Y-m-d H:i:s', $createTime->timestamp()->toInt());

// Check if file was created in the last week
$oneWeekAgo = Integer::of(time())->sub(7 * 24 * 60 * 60);
if ($createTime->gt($oneWeekAgo)) {
    echo "File was created in the last week";
}
```

## Permissions

The `Permissions` class represents the permissions of a file or directory.

### Creation

#### Create

Creates a new Permissions instance.

```php
// Create writable permissions (0644)
$perms = Permissions::create(0644);

// Create readonly permissions (0444)
$readonly = Permissions::create(0444);

// Create executable permissions (0755)
$executable = Permissions::create(0755);
```

#### From Path

Creates a new Permissions instance from a path.

```php
// Using a string path
$perms = Permissions::of('/etc/hosts');

// Using a Path object
$path = Path::of('/etc/hosts');
$perms = Permissions::of($path);

// Check if the file is writable
if ($perms->isWritable()) {
    echo "File is writable";
}

// Check mode and display in octal format
$mode = $perms->mode();
printf("Current permissions: %04o\n", $mode->toInt()); // e.g., "0644"
```

### Properties

#### Mode

> [!NOTE]
> **Direct Mode Access**: Provides access to the raw mode bits for advanced permission manipulation.

Gets the file mode.

```php
$perms = Permissions::create(0644);
$mode = $perms->mode(); // Integer representing octal 0644

// Compare with an octal value
if ($perms->mode()->eq(0644)) {
    echo "Standard file permissions detected";
}

// Display in octal format
printf("Permissions: %04o\n", $perms->mode()->toInt()); // "Permissions: 0644"
```

### Permission Checking

#### Is Readable

Checks if the permissions indicate readability.

```php
$perms = Permissions::create(0444);
$isReadable = $perms->isReadable(); // true

$noAccess = Permissions::create(0000);
$isReadable = $noAccess->isReadable(); // false

// Check if a file is readable
$filePerms = Permissions::of('/etc/hosts');
if ($filePerms->isReadable()) {
    echo "File is readable";
}
```

#### Is Writable

Checks if the permissions indicate writability.

```php
$perms = Permissions::create(0644); // rw-r--r--
$isWritable = $perms->isWritable(); // true

$readonly = Permissions::create(0444); // r--r--r--
$isWritable = $readonly->isWritable(); // false

// Check if a file is writable
$filePerms = Permissions::of('/var/log/app.log');
if ($filePerms->isWritable()) {
    echo "Log file is writable";
} else {
    echo "Cannot write to log file";
}
```

#### Is Executable

Checks if the permissions indicate executability.

```php
$perms = Permissions::create(0755);
$isExecutable = $perms->isExecutable(); // true

$nonExecutable = Permissions::create(0644);
$isExecutable = $nonExecutable->isExecutable(); // false

// Check if a file is executable
$filePerms = Permissions::of('/usr/bin/php');
if ($filePerms->isExecutable()) {
    echo "File is executable";
} else {
    echo "File is not executable";
}
```

### Modification

#### Set Mode

Sets the file mode.

```php
$perms = Permissions::create(0644);

// Make it executable
$executablePerms = $perms->setMode(0755);
$isExecutable = $executablePerms->isExecutable(); // true

// Remove all permissions
$noAccess = $perms->setMode(0000);
$isReadable = $noAccess->isReadable(); // false
$isWritable = $noAccess->isWritable(); // false
$isExecutable = $noAccess->isExecutable(); // false
```

### Application

#### Apply

> [!IMPORTANT]
> **Direct Filesystem Modification**: Actually changes the file permissions on disk, unlike the theoretical operations of other methods.

> [!WARNING]
> **Permission Requirements**: May require appropriate privileges, especially for files owned by other users or system files.

Applies these permissions to a path on the filesystem.

```php
$perms = Permissions::create(0644);

$result = $perms->apply('/path/to/file.txt');
if ($result->isOk()) {
    echo "Permissions applied successfully";
} else {
    echo "Failed to apply permissions: " . $result->unwrapErr()->getMessage();
}

// Make a file executable in one step
$execResult = Permissions::create(0755)->apply('/path/to/script.sh');

// Apply readonly permissions
$readonlyResult = Permissions::create(0444)->apply('/path/to/config.conf');
```

### Error Handling Patterns

> [!NOTE]
> **Result Type Usage**: All potentially failing operations return Result types, forcing explicit error handling and preventing silent failures.
> **Specific Error Types**: Each operation can return specific error types that provide meaningful context about what went wrong, enabling appropriate error handling strategies.
> **Result Chaining**: Result types can be chained with `andThen()`, allowing for elegant error propagation without nested `match()` calls.

```php
// Using Result::match
$result = FileSystem::read('/path/to/file.txt');
$content = $result->match(
    function(Str $content) { return $content->toString(); },
    function(\Exception $error) { return "Error: " . $error->getMessage(); }
);

// Using if/else with Result
$result = FileSystem::createDir('/path/to/directory');
if ($result->isOk()) {
    echo "Directory created successfully";
} else {
    $error = $result->unwrapErr();
    if ($error instanceof AlreadyExists) {
        echo "Directory already exists";
    } else {
        echo "Failed to create directory: " . $error->getMessage();
    }
}
```

### Chaining with andThen

The `andThen()` method enables railway-oriented programming for file operations. Each step only executes if the previous one succeeded. If any step fails, the error propagates through and subsequent steps are skipped entirely.

```php
// Create, write, and set permissions in one safe pipeline
$result = File::new('/path/to/config.json')
    ->andThen(fn($file) => $file->write('{"setting": "value"}'))
    ->andThen(fn($file) => $file->setPermissions(Permissions::create(0644)));

// Handle the final result once
$result->match(
    fn($file) => "Config file created and secured",
    fn($error) => "Failed: " . $error->getMessage()
);
```

```php
// Read, transform, and write back
$result = File::from('/path/to/data.json')
    ->andThen(fn($file) => $file->read())
    ->map(fn($content) => $content->toString())
    ->map(fn($json) => Str::of($json)->toUppercase()->toString())
    ->andThen(fn($transformed) =>
        File::from('/path/to/data.json')
            ->andThen(fn($file) => $file->write($transformed))
    );
```

```php
// Combine with Option::andThen for directory traversal
$result = FileSystem::readDir('/var/log');

$firstLogContent = $result
    ->map(fn($entries) => $entries
        ->find(fn($entry) => $entry->path()->extension()
            ->match(fn($ext) => $ext->toString() === 'log', fn() => false)
        )
    )
    ->andThen(fn($optEntry) => $optEntry
        ->andThen(fn($entry) => File::from($entry->path())
            ->andThen(fn($file) => $file->read())
            ->option()   // Convert Result to Option
        )
        ->okOr(new \RuntimeException('No log file found'))
    );
```

> [!TIP]
> Use `andThen()` when the callback returns a `Result` (operations that can fail). Use `map()` when the callback returns a plain value (transformations that always succeed).

**Notes:**

- All FileSystem operations return Result types for safe error handling
- The module follows immutable principles - operations return new instances
- File handles are not kept open to ensure thread safety and prevent resource leaks
- Permissions checking considers both file mode bits and contextual permissions
