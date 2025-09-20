<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Collections\Map;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use Jsadaa\PhpCoreLibrary\Modules\Option\Option;

/**
 * @template T
 * @template S
 * @psalm-immutable
 */
final readonly class Map {
    /** @var Sequence<Pair<T, S>> */
    private Sequence $values;

    /**
     * @param Sequence<Pair<T, S>> $values
     */
    private function __construct(Sequence $values)
    {
        $this->values = $values;
    }

    /**
     * @template A
     * @template B
     * @param A $key
     * @param B $value
     * @psalm-pure
     * @return self<A, B>
     */
    public static function of(mixed $key, mixed $value): self
    {
        return new self(Sequence::of(Pair::of($key, $value)));
    }

    /**
     * @return self<T, S>
     */
    public function clear(): self
    {
        return new self($this->values->clear());
    }

    /**
     * @param T $key
     */
    public function containsKey(mixed $key): bool
    {
        return $this->values->any(static fn(Pair $pair) => $pair->key() === $key);
    }

    /**
     * @param S $value
     */
    public function containsValue(mixed $value): bool
    {
        return $this->values->any(static fn(Pair $pair) => $pair->value() === $value);
    }

    /**
     * @param callable(T, S): bool $predicate
     * @return self<T, S>
     */
    public function filter(callable $predicate): self
    {
        return new self(
            $this
                ->values
                ->filter(static fn(Pair $pair) => $predicate($pair->key(), $pair->value())),
        );
    }

    /**
     * @template U
     * @param callable(T, S): U $mapper
     * @return self<T, U>
     */
    public function map(callable $mapper): self
    {
        return new self(
            $this
                ->values
                ->map(static fn(Pair $pair) => Pair::of($pair->key(), $mapper($pair->key(), $pair->value()))),
        );
    }

    /**
     * @param T $key
     * @return Option<S>
     */
    public function get(mixed $key): Option
    {
        return $this
            ->values
            ->find(static fn(Pair $pair) => $pair->key() === $key)
            ->match(
                static fn(Pair $pair) => Option::some($pair->value()),
                static fn() => Option::none(),
            );
    }

    /**
     * @param T $key
     * @param S $value
     * @return self<T, S>
     */
    public function insert(mixed $key, mixed $value): self
    {
        return new self(
            $this
                ->values
                ->push(Pair::of($key, $value)),
        );
    }

    /**
     * @param T $key
     * @return self<T, S>
     */
    public function remove(mixed $key): self
    {
        return new self(
            $this
                ->values
                ->filter(static fn(Pair $pair) => $pair->key() !== $key),
        );
    }

    public function isEmpty(): bool
    {
        return $this->values->isEmpty();
    }

    /**
     * @return Sequence<T>
     */
    public function keys(): Sequence
    {
        return $this->values->map(static fn(Pair $pair) => $pair->key());
    }

    /**
     * @return Sequence<S>
     */
    public function values(): Sequence
    {
        return $this->values->map(static fn(Pair $pair) => $pair->value());
    }

    /**
     * @param self<T, S> $other
     */
    public function eq(self $other): bool
    {
        if (!$this->keys()->eq($other->keys())) {
            return false;
        }

        return $this->values()->eq($other->values());
    }

    /**
     * @param callable(T, S): bool $predicate
     * @return Option<Pair<T, S>>
     */
    public function find(callable $predicate): Option
    {
        return $this->values->find(static fn(Pair $pair) => $predicate($pair->key(), $pair->value()));
    }

    /**
     * @param self<T, S> $other
     * @return self<T, S>
     */
    public function append(self $other): self
    {
        return new self(
            $this
                ->values
                ->append($other->values),
        );
    }
}
