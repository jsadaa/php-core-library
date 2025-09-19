<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Path;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use Jsadaa\PhpCoreLibrary\Modules\Option\Option;
use Jsadaa\PhpCoreLibrary\Modules\Path\Error\PathInvalid;
use Jsadaa\PhpCoreLibrary\Modules\Path\Error\PrefixNotFound;
use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;

/**
 * @psalm-immutable
 */
final readonly class Path {
    private string $path;

    private function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * Returns the string representation of the path
     *
     * @return string The string representation of the path
     */
    public function __toString(): string
    {
        return $this->path;
    }

    /**
     * Create a new Path instance from a string
     *
     * This is the primary factory method for creating Path objects.
     *
     * @param string|Str $path A string path to convert to a Path object
     * @return self A new Path instance
     * @psalm-pure
     */
    public static function from(string | Str $path): self
    {
        return new self($path instanceof Str ? $path->toString() : $path);
    }

    /**
     * Check if the path exists in the filesystem
     *
     * This method checks both files and directories.
     *
     * @return bool True if the path exists, false otherwise
     */
    public function exists(): bool
    {
        /** @psalm-suppress ImpureFunctionCall */
        return \file_exists($this->path);
    }

    /**
     * Check if the path is a regular file
     *
     * This returns false for directories, symlinks, and non-existent paths.
     *
     * @return bool True if the path is a file, false otherwise
     */
    public function isFile(): bool
    {
        /** @psalm-suppress ImpureFunctionCall */
        return \is_file($this->path) && !\is_link($this->path);
    }

    /**
     * Check if the path is a directory
     *
     * This returns false for regular files, symlinks pointing to non-directories, and non-existent paths.
     *
     * @return bool True if the path is a directory, false otherwise
     */
    public function isDir(): bool
    {
        /** @psalm-suppress ImpureFunctionCall */
        return \is_dir($this->path);
    }

    /**
     * Check if the path is a symbolic link
     *
     * @return bool True if the path is a symbolic link, false otherwise
     */
    public function isSymLink(): bool
    {
        return \is_link($this->path);
    }

    /**
     * Check if the path is absolute
     *
     * An absolute path starts with the root directory and specifies the complete path.
     *
     * Note: This method currently only supports unix-like systems where an absolute path
     * starts with a forward slash ('/').
     *
     * @return bool True if the path is absolute, false if it's relative
     */
    public function isAbsolute(): bool
    {
        return \str_starts_with($this->path, '/'); // Support only unix-like systems for now
    }

    /**
     * Check if the path is relative
     *
     * A relative path specifies a location relative to the current directory.
     * This is the opposite of isAbsolute().
     *
     * @return bool True if the path is relative, false if it's absolute
     */
    public function isRelative(): bool
    {
        return !$this->isAbsolute();
    }

    /**
     * Canonicalize the path
     *
     * This resolves all symbolic links, extra slashes, and references to '.' and '..' in the path.
     * The path must exist on the filesystem for canonicalization to succeed.
     *
     * @return Result<Path, PathInvalid> The canonicalized path or an error if the path doesn't exist
     */
    public function canonicalize(): Result
    {
        /** @psalm-suppress ImpureFunctionCall */
        $canonicalPath = \realpath($this->path);

        if ($canonicalPath === false) {
            /** @var Result<Path, PathInvalid> */
            return Result::err(new PathInvalid(\sprintf("Failed to canonicalize path '%s'", $this->path)));
        }

        /** @var Result<Path, PathInvalid> */
        return Result::ok(new self($canonicalPath));
    }

    /**
     * Get the string representation of the path
     *
     * This returns the raw string path that was used to create this Path object.
     *
     * @return string The string representation of the path
     */
    public function toString(): string
    {
        return $this->path;
    }

    /**
     * Get the Str representation of the path
     *
     * This returns a Str object representing the path.
     *
     * @return Str The Str representation of the path
     */
    public function toStr(): Str
    {
        return Str::from($this->path);
    }

    /**
     * Get the parent directory path
     *
     * Returns an Option containing the parent directory path, or None if this path
     * doesn't have a parent (e.g., it's the root directory or a relative path like "file.txt").
     *
     * @return Option<Path> The parent directory path or None
     */
    public function parent(): Option
    {
        $parentPath = \dirname($this->path);

        if ($parentPath === '.' || $parentPath === $this->path) {
            return Option::none();
        }

        return Option::some(new self($parentPath));
    }

    /**
     * Get a Sequence of all ancestors of the path
     *
     * This returns a Sequence containing all parent directories, starting from the immediate
     * parent and ending with the root directory. The Sequence is ordered from the closest
     * ancestor to the furthest ancestor, with the last element always being None.
     *
     * The last element of the Sequence is always None to indicate that we've reached
     * the end of the hierarchy.
     *
     * @return Sequence<Option<Path>> Sequence of parent paths as Options
     */
    public function ancestors(): Sequence
    {
        /** @var Sequence<Option<Path>> $ancestors */
        $ancestors = Sequence::new();
        $parent = $this->parent();

        if ($parent->isNone()) {
            return $ancestors->push($parent);
        }

        while ($parent->isSome())
        {
            $ancestors = $ancestors->push($parent);
            $parent = $parent->unwrap()->parent();
        }

        return $ancestors->push(Option::none());
    }

    /**
     * Get the file name of the path
     *
     * Returns the final component of the path as a string (without any parent directories).
     * If the path ends with a directory separator, this will return None.
     *
     * @return Option<Str> The file name or None if the path doesn't have a file name
     */
    public function fileName(): Option
    {
        $fileName = \basename($this->path);

        if ($fileName === '') {
            return Option::none();
        }

        /** @var Option<Str> */
        return Option::some(Str::from($fileName));
    }

    /**
     * Strip the prefix from the path
     *
     * Removes the specified prefix from the path if it exists. Returns an error
     * if the path doesn't start with the given prefix.
     *
     * @param string $prefix The prefix to remove from the path
     * @return Result<Path, PrefixNotFound> The new path without the prefix or an error
     */
    public function stripPrefix(string $prefix): Result
    {
        if (\str_starts_with($this->path, $prefix)) {
            /** @var Result<Path, PrefixNotFound> */
            return Result::ok(new self(\substr($this->path, \strlen($prefix))));
        }

        /** @var Result<Path, PrefixNotFound> */
        return Result::err(new PrefixNotFound(\sprintf("Prefix '%s' not found in path '%s'", $prefix, $this->path)));
    }

    /**
     * Check if the path starts with a given prefix
     *
     * This method performs a simple string prefix check without modifying the path.
     *
     * @param string $prefix The prefix to check for
     * @return bool True if the path starts with the prefix, false otherwise
     */
    public function startsWith(string $prefix): bool
    {
        return \str_starts_with($this->path, $prefix);
    }

    /**
     * Check if the path ends with a given suffix
     *
     * Useful for checking file extensions or specific endings.
     *
     * @param string $suffix The suffix to check for
     * @return bool True if the path ends with the suffix, false otherwise
     */
    public function endsWith(string $suffix): bool
    {
        return \str_ends_with($this->path, $suffix);
    }

    /**
     * Get the file stem of the path
     *
     * Returns the final component of the path without its extension. For example,
     * for a path "/path/to/file.txt", this returns "file".
     *
     * @return Option<Str> The file stem or None if there is no file stem
     */
    public function fileStem(): Option
    {
        $fileName = \basename($this->path);

        if ($fileName === '') {
            return Option::none();
        }

        $fileStem = \pathinfo($fileName, \PATHINFO_FILENAME);

        if ($fileStem === '') {
            return Option::none();
        }

        return Option::some(Str::from($fileStem));
    }

    /**
     * Get the file extension of the path
     *
     * Returns the extension of the file, if any. The extension is the portion of the
     * file name after the last dot. Does not include the leading dot.
     *
     * @return Option<Str> The extension or None if there is no extension
     */
    public function extension(): Option
    {
        $extension = \pathinfo($this->path, \PATHINFO_EXTENSION);

        if ($extension === '') {
            return Option::none();
        }

        return Option::some(Str::from($extension));
    }

    /**
     * Join the provided path with the current path
     *
     * Combines this path with another path. If the provided path is relative, it's appended
     * to the current path. If the provided path is absolute, the current path is ignored
     * and the absolute path is returned directly.
     *
     * @param self $path The path to join with the current path
     * @return self The combined path
     */
    public function join(self $path): self
    {
        if ($path->isAbsolute()) {
            return new self($path->path);
        }

        return new self($this->path . \DIRECTORY_SEPARATOR . $path->path);
    }
}
