<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Collections\Map;

/**
 * @template T
 * @template S
 * @psalm-immutable
 */
final readonly class Pair {
    /**
     * @param T $key
     * @param S $value
     */
    private function __construct(private mixed $key, private mixed $value) {}

    /**
     * @template A
     * @template B
     * @param A $key
     * @param B $value
     * @psalm-pure
     */
    public static function of(mixed $key, mixed $value): self {
        return new self($key, $value);
    }

    /**
     * @return T
     */
    public function key(): mixed {
        return $this->key;
    }

    /**
     * @return S
     */
    public function value(): mixed {
        return $this->value;
    }
}
