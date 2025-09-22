<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Result;

/**
 * Represents an error result.
 *
 * @template T
 * @psalm-immutable
 */
final readonly class Err
{
    /**
     * @var T
     */
    private mixed $error;

    /**
     * @param T $error
     */
    private function __construct(mixed $error)
    {
        $this->error = $error;
    }

    /**
     * Creates a new Err instance with the given error.
     *
     * @template U
     * @param U $error
     * @return Err<U>
     * @psalm-pure
     */
    public static function of(mixed $error): self
    {
        return new self($error);
    }

    /**
     * Gets the contained error.
     *
     * @return T The error value
     */
    public function unwrap(): mixed
    {
        return $this->error;
    }
}
