<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Option;

/**
 * Represents a value that is present.
 *
 * @template T
 * @psalm-immutable
 */
final readonly class Some
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
     * Creates a new Some instance with the given value.
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
     * @return T
     */
    public function unwrap(): mixed
    {
        return $this->value;
    }
}
