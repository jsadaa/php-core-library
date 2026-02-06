<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\FileSystem;

use Jsadaa\PhpCoreLibrary\Modules\Path\Path;

/**
 * @psalm-immutable
 */
final readonly class FileType
{
    private function __construct(
        private bool $isFile,
        private bool $isDir,
        private bool $isSymLink,
    ) {
    }

    /**
     * @psalm-pure
     * @psalm-suppress ImpureFunctionCall
     */
    public static function of(string | Path $path): self
    {
        $pathStr = $path instanceof Path ? $path->toString() : $path;

        // Use lstat to correctly identify symlinks
        $isSymLink = \is_link($pathStr);

        // If it's a symlink, we consider it distinct from file/dir for this abstraction,
        // matching the test expectation that isFile() is false for a symlink
        $isFile = !$isSymLink && \is_file($pathStr);
        $isDir = !$isSymLink && \is_dir($pathStr);

        return new self($isFile, $isDir, $isSymLink);
    }

    public function isFile(): bool
    {
        return $this->isFile;
    }

    public function isDir(): bool
    {
        return $this->isDir;
    }

    public function isSymLink(): bool
    {
        return $this->isSymLink;
    }
}
