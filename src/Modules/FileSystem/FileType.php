<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\FileSystem;

use Jsadaa\PhpCoreLibrary\Modules\Path\Path;

/**
 * Represents the type of a filesystem entry (regular file, directory, or symbolic link).
 *
 * This class provides methods to determine what type of filesystem object a path represents.
 * It can distinguish between regular files, directories, and symbolic links.
 *
 * @psalm-immutable
 */
final readonly class FileType {
    private function __construct(
        private Path $path,
    ) {}

    /**
     * Create a new FileType instance from a path
     *
     * Detects the type of filesystem entry that the path points to.
     * If the path is a symlink, it will return a symlink type even if the
     * symlink target is a directory or file.
     *
     * @param string|Path $path The path to check the type of
     * @return self A new FileType instance representing the detected type
     * @psalm-pure
     */
    public static function of(string | Path $path): self
    {
        return new self($path instanceof Path ? $path : Path::of($path));
    }

    /**
     * Check if the file type is a regular file
     *
     * Regular files are normal data files in the filesystem, not directories or symbolic links.
     * This method returns false for directories, symbolic links, and other special file types.
     *
     * @return bool True if this represents a regular file, false otherwise
     */
    public function isFile(): bool
    {
        return $this->path->isFile();
    }

    /**
     * Check if the file type is a directory
     *
     * Directories are filesystem containers that can hold files and other directories.
     * This method returns false for regular files, symbolic links, and other file types.
     *
     * @return bool True if this represents a directory, false otherwise
     */
    public function isDir(): bool
    {
        return $this->path->isDir();
    }

    /**
     * Check if the file type is a symbolic link
     *
     * Symbolic links are special files that point to another file or directory.
     * This method only checks if the path itself is a symlink, not what it points to.
     * The target of the symlink could be a file, directory, or even a non-existent path.
     *
     * @return bool True if this represents a symbolic link, false otherwise
     */
    public function isSymLink(): bool
    {
        return $this->path->isSymLink();
    }
}
