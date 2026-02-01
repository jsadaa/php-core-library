<?php

declare(strict_types=1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process;

use Jsadaa\PhpCoreLibrary\Modules\Process\StreamType;
use Jsadaa\PhpCoreLibrary\Modules\Option\Option;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;

/**
 * Describes how a file descriptor should be set up for a process.
 *
 * @psalm-immutable
 */
final class StreamDescriptor
{
    private StreamType $type;
    /** @var Option<Str> */
    private Option $mode;
    /** @var Option<Path> */
    private Option $path;
    /** @var ?resource */
    private $resource;

    /**
     * @param Option<Str> $mode
     * @param Option<Path> $path
     * @param ?resource $resource
     */
    private function __construct(
        StreamType $type,
        Option $mode,
        Option $path,
        $resource = null
    ) {
        $this->type = $type;
        $this->mode = $mode;
        $this->path = $path;
        $this->resource = $resource;
    }

    /**
     * Creates a pipe descriptor for inter-process communication.
     *
     * @psalm-pure
     */
    public static function pipe(string|Str $mode = 'r'): self
    {
        return new self(
            StreamType::PIPE,
            is_string($mode) ? Option::some(Str::of($mode)) : Option::some($mode),
            Option::none()
        );
    }

    /**
     * Creates a file descriptor that reads from or writes to a file.
     *
     * @psalm-pure
     */
    public static function file(string|Path $path, string|Str $mode = 'r'): self
    {
        return new self(
            StreamType::FILE,
            is_string($mode) ? Option::some(Str::of($mode)) : Option::some($mode),
            is_string($path) ? Option::some(Path::of($path)) : Option::some($path)
        );
    }

    /**
     * Creates a descriptor from an existing resource.
     *
     * @psalm-pure
     * @param resource $resource
     */
    public static function resource($resource): self
    {
        return new self(
            StreamType::RESOURCE,
            Option::none(),
            Option::none(),
            $resource
        );
    }

    /**
     * Inherits the file descriptor from the parent process.
     *
     * @psalm-pure
     */
    public static function inherit(): self
    {
        return new self(
            StreamType::INHERIT,
            Option::none(),
            Option::none()
        );
    }

    /**
     * Redirects to /dev/null (or equivalent).
     *
     * @psalm-pure
     */
    public static function null(): self
    {
        return new self(
            StreamType::NULL,
            Option::none(),
            Option::none()
        );
    }

    /**
     * Converts to PHP's proc_open descriptor format.
     *
     * @return array{string, string}|array{string, string, string}|resource|null
     */
    public function toDescriptor()
    {
        return match ($this->type) {
            StreamType::PIPE => ['pipe', $this->mode->isSome() ? $this->mode->unwrap()->toString() : 'r'],
            StreamType::FILE => [
                'file',
                $this->path->isSome()
                ? $this->path->unwrap()->toString()
                : '',
                $this->mode->isSome()
                ? $this->mode->unwrap()->toString()
                : 'r',
            ],
            StreamType::RESOURCE => $this->resource,
            StreamType::INHERIT => match (PHP_OS_FAMILY) {
                    'Windows' => ['pipe', 'r'],
                    default => STDIN,  // Will be overridden for stdout/stderr
                },
            StreamType::NULL => match (PHP_OS_FAMILY) {
                    'Windows' => ['file', 'NUL', 'r'],
                    default => ['file', '/dev/null', 'r'],
                },
        };
    }

    public function isPipe(): bool
    {
        return $this->type === StreamType::PIPE;
    }

    public function isFile(): bool
    {
        return $this->type === StreamType::FILE;
    }

    public function isResource(): bool
    {
        return $this->type === StreamType::RESOURCE;
    }

    public function isInherit(): bool
    {
        return $this->type === StreamType::INHERIT;
    }

    public function isNull(): bool
    {
        return $this->type === StreamType::NULL;
    }
}
