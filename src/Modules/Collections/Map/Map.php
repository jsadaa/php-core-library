<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Collections\Map;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use Jsadaa\PhpCoreLibrary\Modules\Option\Option;

/**
 * A collection of key-value pairs
 * Type safety is enforced via static analysis only - no runtime type checking.
 *
 * @template K
 * @template V
 * @psalm-immutable
 */
final readonly class Map
{
    /** @var Sequence<Pair<K, V>> */
    private Sequence $values;

    /**
     * @param Sequence<Pair<K, V>> $values
     */
    private function __construct(Sequence $values)
    {
        $this->values = $values;
    }

    /**
     * Create a new collection with a single key-value pair
     *
     * @template A
     * @template B
     * @param A $key The key
     * @param B $value The value
     * @psalm-pure
     * @return self<A, B>
     */
    public static function of(mixed $key, mixed $value): self
    {
        return new self(Sequence::of(Pair::of($key, $value)));
    }

    /**
     * Create a new empty collection
     *
     * @template A
     * @template B
     * @return self<A, B> The new empty collection
     * @psalm-pure
     */
    public static function new(): self
    {
        return new self(Sequence::new());
    }

    /**
     * Clear the collection
     *
     * @return self<K, V>
     */
    public function clear(): self
    {
        return new self($this->values->clear());
    }

    /**
     * Check if the collection contains a key
     *
     * @param K $key The key to check
     */
    public function containsKey(mixed $key): bool
    {
        return $this->values->any(static fn(Pair $pair) => $pair->key() === $key);
    }

    /**
     * Check if the collection contains a value
     *
     * @param V $value The value to check
     */
    public function containsValue(mixed $value): bool
    {
        return $this->values->any(static fn(Pair $pair) => $pair->value() === $value);
    }

    /**
     * Filter the collection based on a predicate function
     *
     * @param callable(K, V): bool $predicate The predicate function
     * @return self<K, V>
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
     * Map the collection based on a mapper function
     *
     * @template U
     * @param callable(K, V): U $mapper The mapper function
     * @return self<K, U>
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
     * Get the value associated with a key
     *
     * @param K $key The key to retrieve the value for
     * @return Option<V>
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
     * Insert a key-value pair into the map
     *
     * @param K $key The key to insert
     * @param V $value The value to insert
     * @return self<K, V>
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
     * Remove a key-value pair from the map
     *
     * @param K $key The key of the pair to remove
     * @return self<K, V>
     */
    public function remove(mixed $key): self
    {
        return new self(
            $this
                ->values
                ->filter(static fn(Pair $pair) => $pair->key() !== $key),
        );
    }

    /**
     * Check if the collection is empty
     *
     */
    public function isEmpty(): bool
    {
        return $this->values->isEmpty();
    }

    /**
     * Return a sequence of all keys in the map
     *
     * @return Sequence<K>
     */
    public function keys(): Sequence
    {
        return $this->values->map(static fn(Pair $pair) => $pair->key());
    }

    /**
     * Return a sequence of all values in the map
     *
     * @return Sequence<V>
     */
    public function values(): Sequence
    {
        return $this->values->map(static fn(Pair $pair) => $pair->value());
    }

    /**
     * Check if the map is equal to another map
     *
     * @param self<K, V> $other The map to compare with
     */
    public function eq(self $other): bool
    {
        if (!$this->keys()->eq($other->keys())) {
            return false;
        }

        return $this->values()->eq($other->values());
    }

    /**
     * Find a key-value pair in the map that satisfies the given predicate
     *
     * @param callable(K, V): bool $predicate The predicate to apply to each key-value pair
     * @return Option<Pair<K, V>>
     */
    public function find(callable $predicate): Option
    {
        return $this->values->find(static fn(Pair $pair) => $predicate($pair->key(), $pair->value()));
    }

    /**
     * Append all key-value pairs from another map to this map
     *
     * @param self<K, V> $other The map to append
     * @return self<K, V>
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
