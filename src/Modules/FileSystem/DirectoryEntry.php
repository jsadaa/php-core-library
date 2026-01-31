<?php

declare(strict_types=1);

namespace Jsadaa\PhpCoreLibrary\Modules\FileSystem;

use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\FileNotFound;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\InvalidMetadata;
use Jsadaa\PhpCoreLibrary\Modules\Option\Option;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;

/**
 * @psalm-immutable
 */
final readonly class DirectoryEntry
{
    private Path $path;

    private function __construct(Path $path)
    {
        $this->path = $path;
    }

    /**
     * Create a new DirectoryEntry instance from a Path
     *
     * This factory method creates a DirectoryEntry object that represents an entry in a directory,
     * whether that's a file, directory, or symlink.
     *
     * @param string|Path $path The path to create the DirectoryEntry from
     * @return self A new DirectoryEntry instance
     * @psalm-pure
     */
    public static function of(string|Path $path): self
    {
        return new self($path instanceof Path ? $path : Path::of($path));
    }

    /**
     * Return the path of the DirectoryEntry
     *
     * Gets the underlying Path object associated with this DirectoryEntry.
     *
     * @return Path The Path object representing this directory entry
     */
    public function path(): Path
    {
        return $this->path;
    }

    /**
     * Return the file name of the DirectoryEntry
     *
     * Gets just the file or directory name component of the path,
     * without any parent directories.
     *
     * @return Option<Str> The file name, or None if the path ends with a directory separator
     */
    public function fileName(): Option
    {
        return $this->path->fileName();
    }

    /**
     * Get the file type of this entry
     *
     * @return FileType The file type
     */
    public function fileType(): FileType
    {
        return FileType::of($this->path);
    }

    /**
     * Get metadata for this directory entry
     *
     * Retrieves complete metadata including size, permissions, timestamps, and file type.
     * This method can fail if the file no longer exists or cannot be accessed.
     *
     * @return Result<Metadata, FileNotFound|InvalidMetadata> The metadata or an error
     */
    public function metadata(): Result
    {
        return Metadata::of($this->path);
    }
}
