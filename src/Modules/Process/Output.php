<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process;

use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;

/**
 * @psalm-immutable
 */
final readonly class Output
{
    private function __construct(
        private Str $stdout,
        private Str $stderr,
        private Status $status,
    ) {}

    /**
     * @psalm-pure
     */
    public static function of(
        Str $stdout,
        Str $stderr,
        Status $status,
    ): self {
        return new self($stdout, $stderr, $status);
    }

    public function stdout(): Str
    {
        return $this->stdout;
    }

    public function stderr(): Str
    {
        return $this->stderr;
    }

    public function exitCode(): Integer
    {
        return $this->status->exitCode();
    }

    public function isSuccess(): bool
    {
        return $this->status->isSuccess();
    }

    public function isFailure(): bool
    {
        return $this->status->isFailure();
    }

    public function toString(): string
    {
        return $this->isSuccess() ? $this->stdout->toString() : $this->stderr->toString();
    }
}
