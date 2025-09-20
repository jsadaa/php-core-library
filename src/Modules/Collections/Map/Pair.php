<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Collections\Map;

/**
 * @template K
 * @template V
 * @psalm-immutable
 */
final readonly class Pair
{
    /**
     * @param K $key
     * @param V $value
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
     * @return K
     */
    public function key(): mixed {
        return $this->key;
    }

    /**
     * @return V
     */
    public function value(): mixed {
        return $this->value;
    }
}
