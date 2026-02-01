<?php

declare(strict_types=1);

namespace Jsadaa\PhpCoreLibrary\Modules\FileSystem;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\AlreadyExists;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\CreateFailed;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\FileNotFound;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\InvalidFileType;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\InvalidMetadata;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\PermissionDenied;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\ReadFailed;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\TimestampFailed;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\WriteFailed;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\File\Time;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Modules\Time\SystemTime;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;

/**
 * File provides an interface for file operations, including reading, writing,
 * and metadata access. This class represents a file with various
 * methods to manipulate the file content and properties.
 *
 * It provides a safer, more ergonomic way to work with files compared to PHP's
 * built-in file functions, with proper error handling via Result types and true
 * immutability.
 *
 * This class does not keep an open file handle to ensure:
 * - Immutability (of the current instance, not the underlying filesystem)
 * - Filesystem operations are atomic and thread-safe
 * - File metadata is always up-to-date
 * - File operations are secure and prevent collisions
 *
 * @psalm-immutable
 */
final readonly class File
{
    private Path $path;

    public function __construct(Path $path)
    {
        $this->path = $path;
    }

    /**
     * Get the path of the file
     *
     * Returns the Path object associated with this file.
     *
     * @return Path The path of the file
     */
    public function path(): Path
    {
        return $this->path;
    }

    /**
     * Open an existing file
     *
     * Returns an error if the file doesn't exist or if there's an error opening the file.
     * The file must exist and be a regular file (not a directory or symlink).
     *
     * Note: This method does not check if the file is readable, writable, or executable.
     * Permissions are only checked when side-effect methods are called (read(), write(), etc.)
     *
     * @param string|Path $path The path of the file to open
     * @return Result<File, FileNotFound|InvalidFileType> A Result containing the opened File or an error
     */
    public static function from(string|Path $path): Result
    {
        if (\is_string($path)) {
            $path = Path::of($path);
        }

        if (!$path->exists()) {
            /** @var Result<File, FileNotFound|InvalidFileType> */
            return Result::err(new FileNotFound(\sprintf(
                'Failed to open file: %s (File not found or not readable)',
                $path->toString(),
            )));
        }

        if (!$path->isFile()) {
            /** @var Result<File, FileNotFound|InvalidFileType> */
            return Result::err(new InvalidFileType(\sprintf(
                'Failed to open file: %s (Not a file)',
                $path->toString(),
            )));
        }

        /** @var Result<File, FileNotFound|InvalidFileType> */
        return Result::ok(new self($path));
    }

    /**
     * Create a new file
     *
     * Creates a new empty file at the specified path. The file must not already exist,
     * and the parent directory must be writable.
     *
     * @param string|Path $path The path of the file to create
     * @return Result<File, CreateFailed|AlreadyExists> A Result containing the created File or an error
     */
    public static function new(string|Path $path): Result
    {
        if (\is_string($path)) {
            $path = Path::of($path);
        }

        if ($path->exists()) {
            /** @var Result<File, CreateFailed|AlreadyExists> */
            return Result::err(new AlreadyExists(\sprintf(
                'Failed to create file: %s (The file already exists)',
                $path,
            )));
        }

        $result = @\file_put_contents($path->toString(), '');

        if ($result === false) {
            /** @var Result<File, CreateFailed|AlreadyExists> */
            return Result::err(new CreateFailed(\sprintf(
                'Failed to create file: %s',
                $path,
            )));
        }

        /** @var Result<File, CreateFailed|AlreadyExists> */
        return Result::ok(new self($path));
    }

    /**
     * Read the entire file as a Str
     *
     * Reads the entire content of the file into a Str object
     *
     * @return Result<Str, ReadFailed|PermissionDenied> A Result containing the file content or a read error
     * @psalm-suppress ImpureFunctionCall
     */
    public function read(): Result
    {
        if (!Permissions::of($this->path)->isReadable()) {
            /** @var Result<Str, ReadFailed|PermissionDenied> */
            return Result::err(new PermissionDenied(\sprintf(
                'Failed to read file: %s (Permission denied)',
                $this->path->toString(),
            )));
        }

        $content = @\file_get_contents($this->path->toString());

        if ($content === false) {
            /** @var Result<Str, ReadFailed|PermissionDenied> */
            return Result::err(new ReadFailed(\sprintf(
                'Failed to read file: %s',
                $this->path->toString(),
            )));
        }

        /** @var Result<Str, ReadFailed|PermissionDenied> */
        return Result::ok(Str::of($content));
    }

    /**
     * Read a specific range of bytes from the file
     *
     * Reads up to the specified number of bytes from the specified offset.
     * This allows for reading specific portions of a file without loading the entire file into memory.
     * May return fewer bytes than requested if EOF is reached.
     *
     * @param int|Integer $offset The byte offset to start reading from
     * @param int|Integer $length The maximum number of bytes to read
     * @return Result<Str, ReadFailed|PermissionDenied> A Result containing the read bytes or a read error
     * @psalm-suppress ImpureFunctionCall
     * @psalm-suppress ImpureMethodCall
     */
    public function readRange(int|Integer $offset, int|Integer $length): Result
    {
        $offset = $offset instanceof Integer ? $offset->toInt() : $offset;
        $length = $length instanceof Integer ? $length->toInt() : $length;

        if (!Permissions::of($this->path)->isReadable()) {
            /** @var Result<Str,ReadFailed|PermissionDenied> */
            return Result::err(new PermissionDenied(\sprintf(
                'Failed to read file: %s (Permission denied)',
                $this->path->toString(),
            )));
        }

        $handle = @\fopen($this->path->toString(), 'r');

        if ($handle === false) {
            /** @var Result<Str,ReadFailed|PermissionDenied> */
            return Result::err(new ReadFailed(\sprintf(
                'Failed to open file for reading: %s',
                $this->path->toString(),
            )));
        }

        if (@\fseek($handle, $offset) === -1) {
            \fclose($handle);

            /** @var Result<Str,ReadFailed|PermissionDenied> */
            return Result::err(new ReadFailed(\sprintf(
                'Failed to seek in file: %s (invalid offset: %d)',
                $this->path->toString(),
                $offset,
            )));
        }

        $content = @\fread($handle, $length);
        \fclose($handle);

        if ($content === false) {
            /** @var Result<Str,ReadFailed|PermissionDenied> */
            return Result::err(new ReadFailed(\sprintf(
                'Failed to read from file: %s',
                $this->path->toString(),
            )));
        }

        /** @var Result<Str,ReadFailed|PermissionDenied> */
        return Result::ok(Str::of($content));
    }

    /**
     * Read bytes from a specific offset until EOF
     *
     * Reads the file content starting from the specified offset until EOF.
     * This is useful for reading data from a specific point in a file without
     * loading the entire file into memory first.
     *
     * @param int|Integer $offset The byte offset to start reading from
     * @return Result<Str, ReadFailed|PermissionDenied> The bytes read or an error if reading fails
     * @psalm-suppress ImpureFunctionCall
     * @psalm-suppress ImpureMethodCall
     */
    public function readFrom(int|Integer $offset): Result
    {
        $offset = $offset instanceof Integer ? $offset->toInt() : $offset;

        if (!Permissions::of($this->path)->isReadable()) {
            /** @var Result<Str, ReadFailed|PermissionDenied> */
            return Result::err(new PermissionDenied(\sprintf(
                'Failed to read file: %s (Permission denied)',
                $this->path->toString(),
            )));
        }

        $content = @\file_get_contents($this->path->toString(), false, null, $offset);

        if ($content === false) {
            /** @var Result<Str, ReadFailed|PermissionDenied> */
            return Result::err(new ReadFailed(\sprintf(
                'Failed to read from file: %s at offset %d',
                $this->path->toString(),
                $offset,
            )));
        }

        /** @var Result<Str, ReadFailed|PermissionDenied> */
        return Result::ok(Str::of($content));
    }

    /**
     * Read exactly the requested number of bytes from a specific offset
     *
     * This method reads the exact number of bytes required. If EOF is encountered before
     * reading all requested bytes, an error is returned. This is different from the
     * readRange() method, which will return fewer bytes if EOF is reached.
     *
     * @param int|Integer $offset The byte offset to start reading from
     * @param int|Integer $length Exact number of bytes to read
     * @return Result<Str, ReadFailed|PermissionDenied> The bytes read or an error if EOF is encountered before reading all requested bytes
     */
    public function readExact(int|Integer $offset, int|Integer $length): Result
    {
        $offset = $offset instanceof Integer ? $offset->toInt() : $offset;
        $length = $length instanceof Integer ? $length->toInt() : $length;

        $range = $this->readRange($offset, $length);

        if ($range->isErr()) {
            /** @var Result<Str, ReadFailed|PermissionDenied> */
            return $range;
        }

        $data = $range->unwrap();

        if ($data->size()->lt($length)) {
            /** @var Result<Str, ReadFailed|PermissionDenied> */
            return Result::err(new ReadFailed(\sprintf(
                'Unexpected end of file: %s (requested %d bytes but got %d)',
                $this->path->toString(),
                $length,
                $data->size()->toInt(),
            )));
        }

        /** @var Result<Str, ReadFailed|PermissionDenied> */
        return Result::ok($data);
    }

    /**
     * Read the file as a Sequence of byte values
     *
     * Reads the entire file and returns its content as a Sequence of bytes (integers 0-255).
     * This is useful for binary file processing or when you need to work with individual bytes.
     *
     * @return Result<Sequence<Integer>, ReadFailed|PermissionDenied> A Result containing a Sequence of bytes or a read error
     * @psalm-suppress ImpureFunctionCall
     */
    public function bytes(): Result
    {
        if (!Permissions::of($this->path)->isReadable()) {
            /** @var Result<Sequence<Integer>, ReadFailed|PermissionDenied> */
            return Result::err(new PermissionDenied(\sprintf(
                'Failed to read file: %s (Permission denied)',
                $this->path->toString(),
            )));
        }

        $content = @\file_get_contents($this->path->toString());

        if ($content === false) {
            /** @var Result<Sequence<Integer>, ReadFailed|PermissionDenied> */
            return Result::err(new ReadFailed(\sprintf(
                'Failed to read file: %s',
                $this->path->toString(),
            )));
        }

        $bytes = \unpack('C*', $content);

        if ($bytes === false) {
            /** @var Result<Sequence<Integer>, ReadFailed|PermissionDenied> */
            return Result::err(new ReadFailed(\sprintf(
                'Failed to unpack bytes from file: %s',
                $this->path->toString(),
            )));
        }

        /** @var Result<Sequence<Integer>, ReadFailed|PermissionDenied> */
        return Result::ok(
            Sequence::ofArray($bytes)->map(static fn(int $byte) => Integer::of($byte)),
        );
    }

    /**
     * Write data to the file
     *
     * Writes the supplied string to the file, replacing any existing content.
     * If the file doesn't exist, it will be created.
     *
     * @param string|Str $data The data to write to the file
     * @return Result<File, WriteFailed|PermissionDenied> A Result containing the File or a write error
     * @psalm-suppress ImpureFunctionCall
     */
    public function write(string|Str $data): Result
    {
        if (!Permissions::of($this->path)->isWritable()) {
            /** @var Result<File, WriteFailed|PermissionDenied> */
            return Result::err(new PermissionDenied(\sprintf(
                'Failed to write to file: %s (Permission denied)',
                $this->path->toString(),
            )));
        }

        $result = @\file_put_contents(
            $this->path->toString(),
            $data instanceof Str ? $data->toString() : $data,
        );

        if ($result === false) {
            /** @var Result<File, WriteFailed|PermissionDenied> */
            return Result::err(new WriteFailed(\sprintf(
                'Failed to write to file: %s',
                $this->path->toString(),
            )));
        }

        /** @var Result<File, WriteFailed|PermissionDenied> */
        return Result::ok(new self($this->path));
    }

    /**
     * Append data to the file
     *
     * Adds the supplied string to the end of the file, preserving existing content.
     * If the file doesn't exist, it will be created.
     *
     * @param string|Str $data The data to append to the file
     * @return Result<File, WriteFailed|PermissionDenied> A Result containing the File or a write error
     * @psalm-suppress ImpureFunctionCall
     */
    public function append(string|Str $data): Result
    {
        if (!Permissions::of($this->path)->isWritable()) {
            /** @var Result<File, WriteFailed|PermissionDenied> */
            return Result::err(new PermissionDenied(\sprintf(
                'Failed to append to file: %s (Permission denied)',
                $this->path->toString(),
            )));
        }

        $result = @\file_put_contents(
            $this->path->toString(),
            $data instanceof Str ? $data->toString() : $data,
            \FILE_APPEND,
        );

        if ($result === false) {
            /** @var Result<File, WriteFailed|PermissionDenied> */
            return Result::err(new WriteFailed(\sprintf(
                'Failed to append to file: %s',
                $this->path->toString(),
            )));
        }

        /** @var Result<File, WriteFailed|PermissionDenied> */
        return Result::ok(new self($this->path));
    }

    /**
     * Set the size of the file
     *
     * Truncates or extends the file to the specified length. If the file is extended,
     * the extended area is filled with null bytes. If the file is truncated, all data
     * beyond the specified length is lost.
     *
     * @param int|Integer $length The new length of the file in bytes
     * @return Result<File, WriteFailed|PermissionDenied> A Result containing the modified File or a write error
     * @psalm-suppress ImpureFunctionCall
     */
    public function setSize(int|Integer $length): Result
    {
        if (!Permissions::of($this->path)->isWritable()) {
            /** @var Result<File, WriteFailed|PermissionDenied> */
            return Result::err(new PermissionDenied(\sprintf(
                'Failed to set length of file: %s (Permission denied)',
                $this->path->toString(),
            )));
        }

        $handle = @\fopen($this->path->toString(), 'r+');

        if ($handle === false) {
            /** @var Result<File, WriteFailed|PermissionDenied> */
            return Result::err(new WriteFailed(\sprintf(
                'Failed to open file for truncation: %s',
                $this->path->toString(),
            )));
        }

        $result = @\ftruncate(
            $handle,
            $length instanceof Integer ? $length->toInt() : $length,
        );
        \fclose($handle);

        if ($result === false) {
            /** @var Result<File, WriteFailed|PermissionDenied> */
            return Result::err(new WriteFailed(\sprintf(
                'Failed to set length of file: %s',
                $this->path->toString(),
            )));
        }

        /** @var Result<File, WriteFailed|PermissionDenied> */
        return Result::ok(new self($this->path));
    }

    /**
     * Write data to the file atomically
     *
     * Writes the supplied string to the file using a safe technique that prevents data
     * corruption if the process is interrupted during writing. The write operation uses
     * a temporary file and atomic rename to ensure that the file is either completely
     * updated or not changed at all.
     *
     * This method ensures atomicity at the filesystem level. Either the entire write
     * succeeds or the original file remains unchanged. This is critical for configuration
     * files or other data where partial writes could cause corruption.
     *
     * @param string|Str $data The data to write to the file
     * @param bool $sync Whether to synchronize the data to disk to ensure durability (slower but safer)
     * @return Result<File, WriteFailed|PermissionDenied> A Result containing the File or a write error
     * @psalm-suppress ImpureFunctionCall
     */
    public function writeAtomic(string|Str $data, bool $sync = false): Result
    {
        $pathString = $this->path->toString();
        $tempPath = $pathString . '.tmp.' . \uniqid();

        if (!Permissions::of($this->path)->isWritable()) {
            /** @var Result<File, WriteFailed|PermissionDenied> */
            return Result::err(new PermissionDenied(\sprintf(
                'Failed to write to file: %s (Permission denied)',
                $pathString,
            )));
        }

        $result = @\file_put_contents(
            $tempPath,
            $data instanceof Str ? $data->toString() : $data,
        );

        if ($result === false) {
            @\unlink($tempPath);

            /** @var Result<File, WriteFailed|PermissionDenied> */
            return Result::err(new WriteFailed(\sprintf(
                'Failed to write to temporary file during atomic write: %s',
                $pathString,
            )));
        }

        // Synchronize the temporary file if requested
        if ($sync) {
            $handle = @\fopen($tempPath, 'r+');

            if ($handle === false) {
                @\unlink($tempPath);

                /** @var Result<File, WriteFailed|PermissionDenied> */
                return Result::err(new WriteFailed(\sprintf(
                    'Failed to open temporary file for syncing: %s',
                    $tempPath,
                )));
            }

            @\fsync($handle);
            \fclose($handle);
        }

        // Perform atomic rename
        if (!@\rename($tempPath, $pathString)) {
            @\unlink($tempPath);

            /** @var Result<File, WriteFailed|PermissionDenied> */
            return Result::err(new WriteFailed(\sprintf(
                'Failed to rename temporary file during atomic write: %s',
                $pathString,
            )));
        }

        // Sync directory if requested to ensure the rename is durable
        if ($sync) {
            $dirPath = \dirname($pathString);
            $dirHandle = @\opendir($dirPath);

            if ($dirHandle !== false) {
                @\fsync($dirHandle);
                \closedir($dirHandle);
            }
        }

        /** @var Result<File, WriteFailed|PermissionDenied> */
        return Result::ok(new self($this->path));
    }

    /**
     * Get the size of the file in bytes
     *
     * @return Result<Integer, ReadFailed> The file size in bytes or a read error
     */
    public function size(): Result
    {
        $size = @\filesize($this->path->toString());

        if ($size === false) {
            /** @var Result<Integer, ReadFailed> */
            return Result::err(new ReadFailed(\sprintf(
                'Failed to get size of file: %s',
                $this->path->toString(),
            )));
        }

        /** @var Result<Integer, ReadFailed> */
        return Result::ok(Integer::of($size));
    }

    /**
     * Get metadata for this file
     *
     * Retrieves file metadata including size, permissions, timestamps, and type.
     * This provides access to information about the file beyond its contents.
     *
     * @return Result<Metadata, InvalidMetadata> A Result containing the file metadata or an error
     */
    public function metadata(): Result
    {
        /** @var Result<Metadata, InvalidMetadata> */
        return Metadata::of($this->path);
    }

    /**
     * Apply permissions to this file
     *
     * Sets the permissions of the file according to the provided Permissions object.
     * This can be used to change read, write, and execute permissions of the file.
     *
     * @param Permissions $permissions The permissions to apply
     * @return Result<File, PermissionDenied> A Result containing the File or a permissions error
     */
    public function setPermissions(Permissions $permissions): Result
    {
        $result = $permissions->apply($this->path);

        if ($result->isErr()) {
            /** @var Result<File, PermissionDenied> */
            return $result;
        }

        /** @var Result<File, PermissionDenied> */
        return Result::ok(new self($this->path));
    }

    /**
     * Sets the last modification time of the file.
     *
     * Updates the modification timestamp of the file to the specified time.
     * This is a convenience method that creates a FileTimes object and calls setTimes.
     *
     * @param SystemTime $time The new modification time to set
     * @return Result<File, TimestampFailed> Result containing the File or an error message
     */
    public function setModified(SystemTime $time): Result
    {
        return $this->setTimes(Time::new()->setModified($time));
    }

    /**
     * Changes the timestamps of the underlying file.
     *
     * This method allows changing multiple timestamps at once, including access time,
     * modification time, and (on certain platforms) creation time.
     *
     * @param Time $times The timestamps to set
     * @return Result<File, TimestampFailed> Result containing the File or an error message
     * @psalm-suppress ImpureFunctionCall
     */
    public function setTimes(Time $times): Result
    {
        $pathStr = $this->path->toString();

        // Get current timestamps to use as defaults if not specified
        $currentAccessed = @\fileatime($pathStr);
        $currentModified = @\filemtime($pathStr);

        $currentAccessed = $currentAccessed === false ? \time() : $currentAccessed;
        $currentModified = $currentModified === false ? \time() : $currentModified;

        // Extract timestamps from FileTimes object
        $accessed = $times->accessed();
        $modified = $times->modified();

        // Convert to Unix timestamps (seconds)
        $accessedTimestamp = $accessed->isSome() ? $accessed->unwrap()->seconds()->toInt() : $currentAccessed;
        $modifiedTimestamp = $modified->isSome() ? $modified->unwrap()->seconds()->toInt() : $currentModified;

        // If both timestamps are null, no changes needed
        if ($accessed->isNone() && $modified->isNone()) {
            /** @var Result<File, TimestampFailed> */
            return Result::ok(new self($this->path));
        }

        // Apply timestamps
        $success = @\touch($pathStr, $modifiedTimestamp, $accessedTimestamp);

        if ($success === false) {
            /** @var Result<File, TimestampFailed> */
            return Result::err(new TimestampFailed(\sprintf(
                'Failed to set timestamps for file: %s',
                $pathStr,
            )));
        }

        /** @var Result<File, TimestampFailed> */
        return Result::ok(new self($this->path));
    }
}
