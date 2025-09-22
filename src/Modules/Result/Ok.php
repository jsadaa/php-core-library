<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Result;

/**
 * Represents a successful result.
 *
 * @template T
 * @psalm-immutable
 */
final readonly class Ok
{
    /**
     * @var T
     */
    private mixed $value;

    /**
     * @param T $value
     */
    private function __construct(mixed $value)
    {
        $this->value = $value;
    }

    /**
     * Creates a new Ok instance with the given value.
     *
     * @template U
     * @param U $value
     * @return self<U>
     * @psalm-pure
     */
    public static function of(mixed $value): self
    {
        return new self($value);
    }

    /**
     * Gets the contained value.
     *
     * @return T The success value
     */
    public function unwrap(): mixed
    {
        return $this->value;
    }
}
