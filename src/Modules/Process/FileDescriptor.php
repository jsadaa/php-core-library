<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process;

/**
 * Represents a file descriptor in a process.
 *
 * @psalm-immutable
 */
final readonly class FileDescriptor
{
    private int $number;

    private function __construct(int $number)
    {
        $this->number = $number;
    }

    /**
     * @psalm-pure
     */
    public static function stdin(): self
    {
        return new self(0);
    }

    /**
     * @psalm-pure
     */
    public static function stdout(): self
    {
        return new self(1);
    }

    /**
     * @psalm-pure
     */
    public static function stderr(): self
    {
        return new self(2);
    }

    /**
     * @psalm-pure
     */
    public static function custom(int $number): self
    {
        return new self($number);
    }

    public function number(): int
    {
        return $this->number;
    }

    public function toInt(): int
    {
        return $this->number;
    }

    public function isStdin(): bool
    {
        return $this->number === 0;
    }

    public function isStdout(): bool
    {
        return $this->number === 1;
    }

    public function isStderr(): bool
    {
        return $this->number === 2;
    }

    public function eq(self $other): bool
    {
        return $this->number === $other->number;
    }
}
