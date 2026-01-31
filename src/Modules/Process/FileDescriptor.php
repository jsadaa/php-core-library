<?php

declare(strict_types=1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process;

use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;

/**
 * Represents a file descriptor in a process.
 *
 * @psalm-immutable
 */
final readonly class FileDescriptor
{
    private Integer $number;

    private function __construct(Integer $number)
    {
        $this->number = $number;
    }

    /**
     * @psalm-pure
     */
    public static function stdin(): self
    {
        return new self(Integer::of(0));
    }

    /**
     * @psalm-pure
     */
    public static function stdout(): self
    {
        return new self(Integer::of(1));
    }

    /**
     * @psalm-pure
     */
    public static function stderr(): self
    {
        return new self(Integer::of(2));
    }

    /**
     * @psalm-pure
     */
    public static function custom(int|Integer $number): self
    {
        return new self(
            is_int($number) ? Integer::of($number) : $number
        );
    }

    public function number(): Integer
    {
        return $this->number;
    }

    public function toInt(): int
    {
        return $this->number->toInt();
    }

    public function isStdin(): bool
    {
        return $this->number->eq(0);
    }

    public function isStdout(): bool
    {
        return $this->number->eq(1);
    }

    public function isStderr(): bool
    {
        return $this->number->eq(2);
    }

    public function eq(self $other): bool
    {
        return $this->number->eq($other->number);
    }
}
