<?php

declare(strict_types = 1);

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
use Jsadaa\PhpCoreLibrary\Modules\Option\Option;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Modules\Time\SystemTime;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use Jsadaa\PhpCoreLibrary\Primitives\Unit;

/**
 * Represents an open file handle for streaming and complex operations.
 *
 * This class wraps a mutable OS file handle and is intentionally NOT
 * marked @psalm-immutable, following the same pattern as Process.
 */
final class File
{
    /** @var resource */
    private $handle;
    private Path $path;

    /**
     * @param resource $handle
     */
    private function __construct($handle, Path $path)
    {
        $this->handle = $handle;
        $this->path = $path;
    }

    // --- Factories ---

    /**
     * Open an existing file for reading and writing.
     *
     * The file must exist and be a regular file.
     *
     * @return Result<self, FileNotFound|InvalidFileType>
     */
    public static function open(string | Path $path): Result
    {
        $path = $path instanceof Path ? $path : Path::of($path);
        $pathStr = $path->toString();

        if (!\file_exists($pathStr)) {
            /** @var Result<self, FileNotFound|InvalidFileType> */
            return Result::err(new FileNotFound(\sprintf('File not found: %s', $pathStr)));
        }

        if (!\is_file($pathStr)) {
            /** @var Result<self, FileNotFound|InvalidFileType> */
            return Result::err(new InvalidFileType(\sprintf('Not a regular file: %s', $pathStr)));
        }

        $handle = @\fopen($pathStr, 'r+b');

        if ($handle === false) {
            /** @var Result<self, FileNotFound|InvalidFileType> */
            return Result::err(new FileNotFound(\sprintf('Failed to open file: %s', $pathStr)));
        }

        /** @var Result<self, FileNotFound|InvalidFileType> */
        return Result::ok(new self($handle, $path));
    }

    /**
     * Create a new file for reading and writing.
     *
     * Fails if the file already exists.
     *
     * @return Result<self, AlreadyExists|CreateFailed>
     */
    public static function create(string | Path $path): Result
    {
        $path = $path instanceof Path ? $path : Path::of($path);
        $pathStr = $path->toString();

        // x+b: exclusive create, read+write, binary
        $handle = @\fopen($pathStr, 'x+b');

        if ($handle === false) {
            if (\file_exists($pathStr)) {
                /** @var Result<self, AlreadyExists|CreateFailed> */
                return Result::err(new AlreadyExists(\sprintf('File already exists: %s', $pathStr)));
            }

            /** @var Result<self, AlreadyExists|CreateFailed> */
            return Result::err(new CreateFailed(\sprintf('Failed to create file: %s', $pathStr)));
        }

        /** @var Result<self, AlreadyExists|CreateFailed> */
        return Result::ok(new self($handle, $path));
    }

    // --- Reading ---

    /**
     * Read the entire file content from the beginning.
     *
     * Rewinds to the start, reads everything, then leaves the position at EOF.
     *
     * @return Result<Str, ReadFailed>
     */
    public function readAll(): Result
    {
        \rewind($this->handle);
        $content = @\stream_get_contents($this->handle);

        if ($content === false) {
            /** @var Result<Str, ReadFailed> */
            return Result::err(new ReadFailed(\sprintf('Failed to read file: %s', $this->path->toString())));
        }

        /** @var Result<Str, ReadFailed> */
        return Result::ok(Str::of($content));
    }

    /**
     * Read a single line from the current position.
     *
     * Returns None when EOF is reached.
     *
     * @return Result<Option<Str>, ReadFailed>
     */
    public function readLine(): Result
    {
        if (\feof($this->handle)) {
            /** @var Result<Option<Str>, ReadFailed> */
            return Result::ok(Option::none());
        }

        $line = @\fgets($this->handle);

        if ($line === false) {
            if (\feof($this->handle)) {
                /** @var Result<Option<Str>, ReadFailed> */
                return Result::ok(Option::none());
            }

            /** @var Result<Option<Str>, ReadFailed> */
            return Result::err(new ReadFailed(\sprintf('Failed to read line from: %s', $this->path->toString())));
        }

        /** @var Result<Option<Str>, ReadFailed> */
        return Result::ok(Option::some(Str::of($line)));
    }

    /**
     * Read a chunk of bytes from the current position.
     *
     * May return fewer bytes than requested if EOF is reached.
     *
     * @return Result<Str, ReadFailed>
     */
    public function readChunk(int | Integer $size): Result
    {
        $size = $size instanceof Integer ? $size->toInt() : $size;
        $data = @\fread($this->handle, $size);

        if ($data === false) {
            /** @var Result<Str, ReadFailed> */
            return Result::err(new ReadFailed(\sprintf('Failed to read chunk from: %s', $this->path->toString())));
        }

        /** @var Result<Str, ReadFailed> */
        return Result::ok(Str::of($data));
    }

    /**
     * Read the entire file as a Sequence of byte values (0-255).
     *
     * Rewinds to the start and reads all content.
     *
     * @return Result<Sequence<Integer>, ReadFailed>
     */
    public function bytes(): Result
    {
        $readResult = $this->readAll();

        if ($readResult->isErr()) {
            /** @var Result<Sequence<Integer>, ReadFailed> */
            return Result::err($readResult->unwrapErr());
        }

        $content = $readResult->unwrap()->toString();

        if ($content === '') {
            /** @var Result<Sequence<Integer>, ReadFailed> */
            return Result::ok(Sequence::ofArray([]));
        }

        $bytes = \unpack('C*', $content);

        if ($bytes === false) {
            /** @var Result<Sequence<Integer>, ReadFailed> */
            return Result::err(new ReadFailed(\sprintf('Failed to unpack bytes from: %s', $this->path->toString())));
        }

        /** @var Result<Sequence<Integer>, ReadFailed> */
        return Result::ok(
            Sequence::ofArray($bytes)->map(static fn(int $byte) => Integer::of($byte)),
        );
    }

    // --- Writing ---

    /**
     * Write data at the current position.
     *
     * @return Result<Integer, WriteFailed>
     */
    public function write(string | Str $data): Result
    {
        $data = $data instanceof Str ? $data->toString() : $data;
        $bytes = @\fwrite($this->handle, $data);

        if ($bytes === false) {
            /** @var Result<Integer, WriteFailed> */
            return Result::err(new WriteFailed(\sprintf('Failed to write to: %s', $this->path->toString())));
        }

        /** @var Result<Integer, WriteFailed> */
        return Result::ok(Integer::of($bytes));
    }

    /**
     * Append data at the end of the file.
     *
     * Seeks to end, writes, then stays at the new position.
     *
     * @return Result<Integer, WriteFailed>
     */
    public function append(string | Str $data): Result
    {
        \fseek($this->handle, 0, \SEEK_END);

        return $this->write($data);
    }

    /**
     * Write data atomically using temp-file + rename.
     *
     * Replaces the entire file content. The handle is reopened after
     * the rename to reflect the new file.
     *
     * @return Result<Unit, WriteFailed>
     */
    public function writeAtomic(string | Str $data, bool $sync = false): Result
    {
        $pathStr = $this->path->toString();
        $tempPath = $pathStr . '.tmp.' . \uniqid();
        $dataStr = $data instanceof Str ? $data->toString() : $data;

        $result = @\file_put_contents($tempPath, $dataStr);

        if ($result === false) {
            @\unlink($tempPath);

            /** @var Result<Unit, WriteFailed> */
            return Result::err(new WriteFailed(\sprintf(
                'Failed to write temp file during atomic write: %s',
                $pathStr,
            )));
        }

        if ($sync) {
            $tmpHandle = @\fopen($tempPath, 'r+');

            if ($tmpHandle !== false) {
                @\fsync($tmpHandle);
                \fclose($tmpHandle);
            }
        }

        // Close current handle before rename
        /** @psalm-suppress InvalidPropertyAssignmentValue Handle is immediately reassigned */
        \fclose($this->handle);

        if (!@\rename($tempPath, $pathStr)) {
            @\unlink($tempPath);

            // Attempt to reopen the original file
            $reopen = @\fopen($pathStr, 'r+b');

            if ($reopen !== false) {
                $this->handle = $reopen;
            }

            /** @var Result<Unit, WriteFailed> */
            return Result::err(new WriteFailed(\sprintf(
                'Failed to rename temp file during atomic write: %s',
                $pathStr,
            )));
        }

        if ($sync) {
            $dirPath = \dirname($pathStr);
            $dirHandle = @\opendir($dirPath);

            if ($dirHandle !== false) {
                \closedir($dirHandle);
            }
        }

        // Reopen the handle on the new file
        $newHandle = @\fopen($pathStr, 'r+b');

        if ($newHandle === false) {
            /** @var Result<Unit, WriteFailed> */
            return Result::err(new WriteFailed(\sprintf(
                'Failed to reopen file after atomic write: %s',
                $pathStr,
            )));
        }

        $this->handle = $newHandle;

        /** @var Result<Unit, WriteFailed> */
        return Result::ok(Unit::new());
    }

    /**
     * Flush buffered data to the OS.
     *
     * @return Result<Unit, WriteFailed>
     */
    public function flush(): Result
    {
        if (!@\fflush($this->handle)) {
            /** @var Result<Unit, WriteFailed> */
            return Result::err(new WriteFailed(\sprintf('Failed to flush: %s', $this->path->toString())));
        }

        /** @var Result<Unit, WriteFailed> */
        return Result::ok(Unit::new());
    }

    // --- Navigation ---

    /**
     * Seek to a specific byte offset from the beginning.
     *
     * @return Result<Unit, ReadFailed>
     */
    public function seek(int | Integer $offset): Result
    {
        $offset = $offset instanceof Integer ? $offset->toInt() : $offset;

        if (\fseek($this->handle, $offset) === -1) {
            /** @var Result<Unit, ReadFailed> */
            return Result::err(new ReadFailed(\sprintf('Failed to seek in: %s', $this->path->toString())));
        }

        /** @var Result<Unit, ReadFailed> */
        return Result::ok(Unit::new());
    }

    /**
     * Rewind to the beginning of the file.
     *
     * @return Result<Unit, ReadFailed>
     */
    public function rewind(): Result
    {
        if (!\rewind($this->handle)) {
            /** @var Result<Unit, ReadFailed> */
            return Result::err(new ReadFailed(\sprintf('Failed to rewind: %s', $this->path->toString())));
        }

        /** @var Result<Unit, ReadFailed> */
        return Result::ok(Unit::new());
    }

    // --- Metadata ---

    /**
     * Get the path of this file.
     */
    public function path(): Path
    {
        return $this->path;
    }

    /**
     * Get a metadata snapshot for this file.
     *
     * @return Result<Metadata, FileNotFound|InvalidMetadata>
     */
    public function metadata(): Result
    {
        /** @var Result<Metadata, FileNotFound|InvalidMetadata> */
        return Metadata::of($this->path);
    }

    /**
     * Get the file size using the open handle.
     *
     * @return Result<Integer, ReadFailed>
     */
    public function size(): Result
    {
        $stat = @\fstat($this->handle);

        if ($stat === false) {
            /** @var Result<Integer, ReadFailed> */
            return Result::err(new ReadFailed(\sprintf('Failed to get size of: %s', $this->path->toString())));
        }

        /** @var Result<Integer, ReadFailed> */
        return Result::ok(Integer::of($stat['size']));
    }

    /**
     * Apply permissions to this file.
     *
     * @return Result<Unit, PermissionDenied>
     */
    public function setPermissions(Permissions $permissions): Result
    {
        return $permissions->apply($this->path);
    }

    /**
     * Set the last modification time.
     *
     * @return Result<Unit, TimestampFailed>
     */
    public function setModified(SystemTime $time): Result
    {
        return $this->setTimes(FileTimes::new()->setModified($time));
    }

    /**
     * Set multiple timestamps at once.
     *
     * @return Result<Unit, TimestampFailed>
     */
    public function setTimes(FileTimes $times): Result
    {
        $pathStr = $this->path->toString();

        $currentAccessed = @\fileatime($pathStr);
        $currentModified = @\filemtime($pathStr);

        $currentAccessed = $currentAccessed === false ? \time() : $currentAccessed;
        $currentModified = $currentModified === false ? \time() : $currentModified;

        $accessed = $times->accessed();
        $modified = $times->modified();

        if ($accessed->isNone() && $modified->isNone()) {
            /** @var Result<Unit, TimestampFailed> */
            return Result::ok(Unit::new());
        }

        $accessedTimestamp = $accessed->isSome() ? $accessed->unwrap()->seconds()->toInt() : $currentAccessed;
        $modifiedTimestamp = $modified->isSome() ? $modified->unwrap()->seconds()->toInt() : $currentModified;

        if (!@\touch($pathStr, $modifiedTimestamp, $accessedTimestamp)) {
            /** @var Result<Unit, TimestampFailed> */
            return Result::err(new TimestampFailed(\sprintf('Failed to set timestamps for: %s', $pathStr)));
        }

        /** @var Result<Unit, TimestampFailed> */
        return Result::ok(Unit::new());
    }

    // --- Lifecycle ---

    /**
     * Close the file handle.
     */
    public function close(): void
    {
        /**
         * @psalm-suppress RedundantConditionGivenDocblockType
         * @psalm-suppress InvalidPropertyAssignmentValue Handle becomes closed-resource
         */
        if (\is_resource($this->handle)) {
            \fclose($this->handle);
        }
    }

    /**
     * Safety net: close the handle if not explicitly closed.
     */
    public function __destruct()
    {
        $this->close();
    }

    // --- Scoped pattern ---

    /**
     * Open a file, pass it to a callback, and close it automatically.
     *
     * @template T
     * @param callable(File): T $callback
     * @return Result<T, FileNotFound|InvalidFileType>
     */
    public static function withOpen(string | Path $path, callable $callback): Result
    {
        $fileResult = self::open($path);

        if ($fileResult->isErr()) {
            /** @var Result<T, FileNotFound|InvalidFileType> */
            return $fileResult;
        }

        $file = $fileResult->unwrap();

        try {
            $result = $callback($file);

            /** @var Result<T, FileNotFound|InvalidFileType> */
            return Result::ok($result);
        } finally {
            $file->close();
        }
    }
}
