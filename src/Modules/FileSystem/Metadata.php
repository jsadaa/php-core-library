<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\FileSystem;

use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\FileNotFound;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\InvalidMetadata;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Modules\Time\SystemTime;

/**
 * Represents a snapshot of metadata about a file or directory.
 *
 * All values are captured at construction time via a single lstat() call.
 * No method on this class performs live filesystem queries.
 *
 * @psalm-immutable
 */
final readonly class Metadata {
    private function __construct(
        private Path $path,
        private FileType $fileType,
        private Permissions $permissions,
        private int $size,
        private SystemTime $modified,
        private SystemTime $accessed,
        private SystemTime $created,
    ) {}

    /**
     * Create a new Metadata instance from a path.
     *
     * Uses lstat() to capture all metadata atomically. Symlinks are not
     * followed -- the metadata describes the link itself.
     *
     * @param string|Path $path The path to get metadata for
     * @return Result<self, FileNotFound|InvalidMetadata>
     * @psalm-pure
     * @psalm-suppress ImpureFunctionCall
     * @psalm-suppress ImpureMethodCall
     */
    public static function of(string | Path $path): Result {
        $path = $path instanceof Path ? $path : Path::of($path);
        $pathStr = $path->toString();

        $stat = @\lstat($pathStr);

        if ($stat === false) {
            if (!\file_exists($pathStr) && !\is_link($pathStr)) {
                /** @var Result<self, FileNotFound|InvalidMetadata> */
                return Result::err(new FileNotFound(\sprintf('Path does not exist: %s', $pathStr)));
            }

            /** @var Result<self, FileNotFound|InvalidMetadata> */
            return Result::err(new InvalidMetadata(\sprintf('Failed to read metadata for: %s', $pathStr)));
        }

        /**
         * @var Result<self, FileNotFound|InvalidMetadata>
         * @psalm-suppress ArgumentTypeCoercion lstat() timestamps are always >= 0
         */
        return Result::ok(new self(
            $path,
            FileType::fromMode($stat['mode']),
            Permissions::create($stat['mode']),
            $stat['size'],
            SystemTime::fromTimestamp($stat['mtime']),
            SystemTime::fromTimestamp($stat['atime']),
            SystemTime::fromTimestamp($stat['ctime']),
        ));
    }

    /**
     * Get the path of this Metadata instance.
     */
    public function path(): Path
    {
        return $this->path;
    }

    /**
     * Get the file type captured at snapshot time.
     */
    public function fileType(): FileType
    {
        return $this->fileType;
    }

    /**
     * Check if the entry is a directory.
     *
     * @psalm-suppress ImpureMethodCall Enum comparison is inherently pure
     */
    public function isDir(): bool
    {
        return $this->fileType->isDir();
    }

    /**
     * Check if the entry is a regular file.
     *
     * @psalm-suppress ImpureMethodCall Enum comparison is inherently pure
     */
    public function isFile(): bool
    {
        return $this->fileType->isFile();
    }

    /**
     * Check if the entry is a symbolic link.
     *
     * @psalm-suppress ImpureMethodCall Enum comparison is inherently pure
     */
    public function isSymLink(): bool
    {
        return $this->fileType->isSymLink();
    }

    /**
     * Get the size in bytes.
     *
     * For directories, the value is platform-dependent.
     * For symbolic links, returns the size of the link itself.
     */
    public function size(): int
    {
        return $this->size;
    }

    /**
     * Get the permissions captured at snapshot time.
     */
    public function permissions(): Permissions
    {
        return $this->permissions;
    }

    /**
     * Get the last modification time.
     */
    public function modified(): SystemTime
    {
        return $this->modified;
    }

    /**
     * Get the last access time.
     *
     * May not be updated on filesystems mounted with noatime.
     */
    public function accessed(): SystemTime
    {
        return $this->accessed;
    }

    /**
     * Get the creation time (or inode change time on Unix).
     *
     * On Unix/Linux, this is typically the inode change time (ctime),
     * not the actual creation time.
     */
    public function created(): SystemTime
    {
        return $this->created;
    }
}
