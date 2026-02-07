<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\FileSystem;

use Jsadaa\PhpCoreLibrary\Modules\Path\Path;

/**
 * Represents the type of a filesystem entry.
 *
 * If a path is a symbolic link, it will be reported as Symlink regardless
 * of what the link points to. This is different from following the symlink
 * to determine the target type.
 *
 * @psalm-immutable
 */
enum FileType
{
    case RegularFile;
    case Directory;
    case Symlink;

    /**
     * Determine the file type of a path.
     *
     * Uses lstat-level checks to correctly identify symlinks
     * before checking for regular files or directories.
     *
     * @psalm-pure
     * @psalm-suppress ImpureFunctionCall
     */
    public static function of(string | Path $path): self
    {
        $pathStr = $path instanceof Path ? $path->toString() : $path;

        if (\is_link($pathStr)) {
            return self::Symlink;
        }

        if (\is_dir($pathStr)) {
            return self::Directory;
        }

        return self::RegularFile;
    }

    /**
     * Determine the file type from a stat mode value.
     *
     * Symlinks cannot be reliably detected from stat() mode alone;
     * use lstat() or is_link() for symlink detection.
     *
     * @param int $mode The mode value from stat()
     * @psalm-pure
     */
    public static function fromMode(int $mode): self
    {
        if (($mode & 0xF000) === 0x4000) {
            return self::Directory;
        }

        if (($mode & 0xF000) === 0xA000) {
            return self::Symlink;
        }

        return self::RegularFile;
    }

    public function isFile(): bool
    {
        return $this === self::RegularFile;
    }

    public function isDir(): bool
    {
        return $this === self::Directory;
    }

    public function isSymLink(): bool
    {
        return $this === self::Symlink;
    }
}
