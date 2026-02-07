<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Collections;

/**
 * An immutable pair of two values.
 *
 * @template A
 * @template B
 * @psalm-immutable
 */
final readonly class Pair
{
    /**
     * @param A $first
     * @param B $second
     */
    private function __construct(private mixed $first, private mixed $second) {}

    /**
     * @template C
     * @template D
     * @param C $first
     * @param D $second
     * @return self<C, D>
     * @psalm-pure
     */
    public static function of(mixed $first, mixed $second): self
    {
        return new self($first, $second);
    }

    /**
     * @return A
     */
    public function first(): mixed
    {
        return $this->first;
    }

    /**
     * @return B
     */
    public function second(): mixed
    {
        return $this->second;
    }
}
