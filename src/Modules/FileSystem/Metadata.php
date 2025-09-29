<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\FileSystem;

use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\FileNotFound;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\InvalidMetadata;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Modules\Time\SystemTime;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;

/**
 * Represents metadata about a file or directory.
 *
 * This class provides access to various file attributes such as permissions,
 * file type, size, and timestamps. It offers a convenient way to retrieve
 * all metadata of a file or directory at once.
 *
 * Permission checks methods in this class (isReadable(), isWritable(), isExecutable()) evaluate contextual permissions based on the current user,
 * filesystem mount options, and other runtime factors rather than just raw file mode bits.
 *
 * @psalm-immutable
 */
final readonly class Metadata {
    private function __construct(
        private Path $path,
        private Permissions $permissions,
        private Integer $size,
        private SystemTime $modified,
        private SystemTime $accessed,
        private SystemTime $created,
    ) {}

    /**
     * Create a new Metadata instance from a path
     *
     * Retrieves all metadata for the given path from the filesystem.
     * Returns an error if the path doesn't exist or metadata cannot be read.
     *
     * @param string|Path $path The path to get metadata for
     * @return Result<self, FileNotFound|InvalidMetadata> Result containing the metadata or an error if the path doesn't exist or metadata can't be retrieved
     * @psalm-pure
     * @psalm-suppress ImpureMethodCall
     */
    public static function of(string | Path $path): Result {
        $path = $path instanceof Path ? $path : Path::of($path);

        if (!$path->exists()) {
            /** @var Result<self, FileNotFound|InvalidMetadata> */
            return Result::err(new FileNotFound(\sprintf('Path does not exist: %s', $path->toString())));
        }

        $filePath = $path->toString();

        // Get basic file information
        $size = @\filesize($filePath);

        if ($size === false) {
            $size = 0;
        }

        $modified = @\filemtime($filePath);

        if ($modified === false || $modified < 0) {
            $modified = 0;
        }

        $accessed = @\fileatime($filePath);

        if ($accessed === false || $accessed < 0) {
            $accessed = 0;
        }

        $created = @\filectime($filePath);

        if ($created === false || $created < 0) {
            $created = 0;
        }

        /** @var Result<Metadata, FileNotFound|InvalidMetadata> */
        return Result::ok(new self(
            $path,
            Permissions::of($path),
            Integer::of($size),
            SystemTime::fromTimestamp($modified),
            SystemTime::fromTimestamp($accessed),
            SystemTime::fromTimestamp($created),
        ));
    }

    /**
     * Get the path of this Metadata instance
     *
     * Returns the Path object associated with this metadata.
     *
     * @return Path The path for which this metadata was retrieved
     */
    public function path(): Path
    {
        return $this->path;
    }

    /**
     * Check if the file this Metadata instance represents is a directory
     *
     * Convenience method to quickly check if the path is a directory.
     *
     * @return bool True if this is a directory, false otherwise
     */
    public function isDir(): bool
    {
        return $this->path->isDir();
    }

    /**
     * Check if the file this Metadata instance represents is a regular file
     *
     * Convenience method to quickly check if the path is a regular file.
     *
     * @return bool True if this is a regular file, false otherwise
     */
    public function isFile(): bool
    {
        return $this->path->isFile();
    }

    /**
     * Check if the file this Metadata instance represents is a symbolic link
     *
     * Convenience method to quickly check if the path is a symbolic link.
     *
     * @return bool True if this is a symbolic link, false otherwise
     * ```
     */
    public function isSymLink(): bool
    {
        return $this->path->isSymLink();
    }

    /**
     * Get the size of the file this Metadata instance represents
     *
     * Returns the size of the file in bytes. For directories, the behavior is
     * platform-dependent and may not represent the total size of directory contents.
     * For symbolic links, this returns the size of the link itself, not its target.
     *
     * @return Integer The size of the file in bytes
     */
    public function size(): Integer
    {
        return $this->size;
    }

    /**
     * Get the permissions of the file this Metadata instance represents
     *
     * Returns the Permissions object associated with this file or directory.
     *
     * @return Permissions The file permissions
     */
    public function permissions(): Permissions
    {
        return $this->permissions;
    }

    /**
     * Get the last modified time of the file this Metadata instance represents
     *
     * Returns the last modification time as a SystemTime object.
     *
     * @return SystemTime The last modified time
     */
    public function modified(): SystemTime
    {
        return $this->modified;
    }

    /**
     * Get the last accessed time of the file this Metadata instance represents
     *
     * Returns the last access time as a SystemTime object.
     * Note that on some filesystems or configurations, access time might not be
     * updated for performance reasons (e.g., mounted with noatime option).
     *
     * @return SystemTime The last accessed time
     */
    public function accessed(): SystemTime
    {
        return $this->accessed;
    }

    /**
     * Get the created time of the file this Metadata instance represents
     *
     * Returns the creation time as a SystemTime object.
     * Note that on Unix/Linux filesystems, this often returns the inode change time
     * (ctime) rather than the actual creation time, as many Unix filesystems don't
     * track true creation time.
     *
     * @return SystemTime The creation time (or inode change time on Unix systems)
     */
    public function created(): SystemTime
    {
        return $this->created;
    }

    /**
     * Check if the file is readable
     *
     * Returns true if the file is (contextually) readable, false otherwise.
     *
     * @psalm-suppress ImpureFunctionCall
     */
    public function isReadable(): bool
    {
        return \is_readable($this->path->toString());
    }

    /**
     * Check if the file is writable
     *
     * Returns true if the file is (contextually) writable, false otherwise.
     *
     * @psalm-suppress ImpureFunctionCall
     */
    public function isWritable(): bool
    {
        return \is_writable($this->path->toString());
    }

    /**
     * Check if the file is executable
     *
     * Returns true if the file is (contextually) executable, false otherwise.
     * If this metadata refers to a directory, it may always return false on some systems (Windows).
     *
     * This check the PATH environment variable to determine if the file is executable.
     *
     * @psalm-suppress ImpureFunctionCall
     */
    public function isExecutable(): bool
    {
        if (\is_executable($this->path->toString())) {
            return true;
        }

        $pathEnv = \getenv('PATH');

        if ($pathEnv === false) {
            return false;
        }

        $paths = \explode(\PATH_SEPARATOR, $pathEnv);
        $fileName = $this->path->fileName();

        if ($fileName->isNone()) {
            return false;
        }

        foreach ($paths as $path) {
            if (\is_executable($path . \DIRECTORY_SEPARATOR . $fileName->unwrap()->toString())) {
                return true;
            }
        }

        return false;
    }
}
