<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\FileSystem;

use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\PermissionDenied;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;

/**
 * Represents the permissions of a file or directory.
 *
 * This class provides a type-safe way to create, manipulate, and apply file permissions
 * in a filesystem. It supports checking for readable, writable, and executable permissions,
 * as well as applying these permissions to files and directories.
 *
 * The class uses Unix-style octal permission modes (e.g., 0644, 0755).
 *
 * This class only manage theoric permissions (mode bits information)
 * not contextual permissions (user, mountpoint, etc.).
 *
 * See Metadata class for contextual permissions checks.
 *
 * @psalm-immutable
 */
final readonly class Permissions {
    private function __construct(
        private Integer $mode,
    ) {}

    /**
     * Create a new Permissions instance
     *
     * This factory method creates a Permissions object with specified mode.
     * The mode is an octal number representing Unix-style permissions.
     *
     * @param int|Integer $mode The file mode (e.g., 0644)
     * @return self A new Permissions instance
     * @psalm-pure
     */
    public static function create(int | Integer $mode = 0644): self {
        return new self($mode instanceof Integer ? $mode : Integer::of($mode));
    }

    /**
     * Create a new Permissions instance from a path
     *
     * Retrieves the current permissions of the file or directory pointed to by the path.
     * This is useful when you want to start with the existing permissions of a file
     * and potentially modify them.
     *
     * @param string|Path $path The path to retrieve permissions from
     * @return self A new Permissions instance with the permissions of the path
     * @psalm-pure
     */
    public static function of(string | Path $path): self {
        $path = $path instanceof Path ? $path->toString() : $path;
        $mode = @\fileperms($path);

        if ($mode === false) {
            return new self(Integer::of(0));
        }

        return new self(Integer::of($mode));
    }

    /**
     * Get the file mode
     *
     * Returns the file mode as an Integer object (octal representation).
     * This is the underlying Unix-style permission mode.
     *
     * @return Integer The file mode
     */
    public function mode(): Integer
    {
        return $this->mode;
    }

    /**
     * Check if the file is writable
     *
     * Determines if the permissions indicate the file is writable.
     * This is based on the write bits in the file mode.
     *
     * @return bool True if the permissions indicate writability (readonly is false), false otherwise
     */
    public function isWritable(): bool  // Maintenant cohérent avec les autres
    {
        return $this->mode->and(0222)->eq(0) === false;
    }

    /**
     * Check if the file is readable
     *
     * Determines if the permissions indicate the file is readable.
     * This is based on the read bits in the file mode.
     *
     * @return bool True if the permissions indicate readability, false otherwise
     */
    public function isReadable(): bool
    {
        return $this->mode->and(0444)->eq(0) === false;
    }

    /**
     * Check if the file is executable
     *
     * Determines if the permissions indicate the file is executable.
     * This is based on the execute bits in the file mode.
     *
     * @return bool True if the permissions indicate executability, false otherwise
     */
    public function isExecutable(): bool
    {
        return $this->mode->and(0111)->eq(0) === false;
    }

    /**
     * Set the file mode
     *
     * Returns a new Permissions instance with the specified file mode,
     *
     * @param int|Integer $mode The new file mode
     * @return self A new Permissions instance with the modified file mode
     */
    public function setMode(int | Integer $mode): self
    {
        return new self($mode instanceof Integer ? $mode : Integer::of($mode));
    }

    /**
     * Apply these permissions to a path on the filesystem
     *
     * Attempts to set the permissions of the specified path according to the
     * mode of this Permissions instance.
     *
     * @param string|Path $path The path to apply the permissions to
     * @return Result<Path, PermissionDenied> A Result containing the path if successful,
     */
    public function apply(string | Path $path): Result
    {
        $path = $path instanceof Path ? $path->toString() : $path;

        // Set file permissions based on the mode
        /** @psalm-suppress ImpureFunctionCall */
        if (!@\chmod($path, $this->mode->toInt())) {
            /** @var Result<Path, PermissionDenied> */
            return Result::err(
                new PermissionDenied(\sprintf(
                    'Failed to set permissions %d on %s',
                    $this->mode->toInt(),
                    $path,
                )),
            );
        }

        /** @var Result<Path, PermissionDenied> */
        return Result::ok(Path::of($path));
    }
}
