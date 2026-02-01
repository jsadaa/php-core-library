<?php

declare(strict_types=1);

namespace Jsadaa\PhpCoreLibrary\Modules\Collections\Map;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use Jsadaa\PhpCoreLibrary\Modules\Collections\Set\Set;
use Jsadaa\PhpCoreLibrary\Modules\Option\Option;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;

/**
 * A collection of key-value pairs where the keys are unique and the values can be of any type.
 * Type safety is enforced via static analysis only - no runtime type checking.
 *
 * This type is not a real Hash Map, but a Sequence of Pairs for now, so expect performance issues.
 *
 * @template K
 * @template V
 * @psalm-immutable
 */
final readonly class Map
{
    /** @var array<string, array{K, V}> */
    private array $scalars;

    /** @var \SplObjectStorage<object, V> */
    private \SplObjectStorage $objects;

    /**
     * @param array<string, array{K, V}> $scalars
     * @param \SplObjectStorage<object, V> $objects
     */
    private function __construct(array $scalars, \SplObjectStorage $objects)
    {
        $this->scalars = $scalars;
        $this->objects = $objects;
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
        return self::new()->add($key, $value);
    }

    /**
     * Create a new map from a list of keys, assigning the same value to each.
     * Efficient O(N) construction.
     *
     * @template A
     * @template B
     * @param iterable<A> $keys
     * @param B $value
     * @return self<A, B>
     */
    public static function fromKeys(iterable $keys, mixed $value): self
    {
        $scalars = [];
        $objects = new \SplObjectStorage();
        $dummy = new self([], $objects); // Helper to access encodeKey if needed? No, purely internal or object context?
        // Static context, can't call private encodeKey if it's instance method.
        // Make encodeKey static or duplicate logic.
        // Prefer static helper.

        foreach ($keys as $key) {
            if (\is_object($key)) {
                $objects->attach($key, $value);
            } else {
                $scalars[self::staticEncodeKey($key)] = [$key, $value];
            }
        }

        return new self($scalars, $objects);
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
        return new self([], new \SplObjectStorage());
    }

    /**
     * Clear the collection
     *
     * @return self<K, V>
     */
    public function clear(): self
    {
        return self::new();
    }

    /**
     * Check if the collection contains a key
     *
     * @param K $key The key to check
     */
    public function containsKey(mixed $key): bool
    {
        if (\is_object($key)) {
            return $this->objects->contains($key);
        }

        return \array_key_exists($this->encodeKey($key), $this->scalars);
    }

    /**
     * Check if the collection contains a value
     *
     * @param V $value The value to check
     */
    public function containsValue(mixed $value): bool
    {
        foreach ($this->scalars as $pair) {
            if ($pair[1] === $value) {
                return true;
            }
        }

        foreach ($this->objects as $object) {
            if ($this->objects[$object] === $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter the collection based on a predicate function
     *
     * @param callable(K, V): bool $predicate The predicate function
     * @return self<K, V>
     */
    public function filter(callable $predicate): self
    {
        /** @var array<string, array{K, V}> $newScalars */
        $newScalars = [];
        $newObjects = new \SplObjectStorage();

        foreach ($this->scalars as $hash => $pair) {
            if ($predicate($pair[0], $pair[1])) {
                $newScalars[$hash] = $pair;
            }
        }

        foreach ($this->objects as $object) {
            $value = $this->objects[$object];
            /** @var K $object */
            if ($predicate($object, $value)) {
                $newObjects->attach($object, $value);
            }
        }

        return new self($newScalars, $newObjects);
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
        /** @var array<string, array{K, U}> $newScalars */
        $newScalars = [];
        $newObjects = new \SplObjectStorage();

        foreach ($this->scalars as $hash => $pair) {
            $newScalars[$hash] = [$pair[0], $mapper($pair[0], $pair[1])];
        }

        foreach ($this->objects as $object) {
            /** @var K $object */
            $newObjects->attach($object, $mapper($object, $this->objects[$object]));
        }

        return new self($newScalars, $newObjects);
    }

    /**
     * FlatMap the collection based on a mapper function
     *
     * @template U
     * @param callable(K, V): self<K, U> $mapper The mapper function that returns a Map
     * @return self<K, U>
     */
    public function flatMap(callable $mapper): self
    {
        return $this->fold(
            /**
             * @param self<K, U> $accumulated
             * @param K $key
             * @param V $value
             * @return self<K, U>
             */
            static fn(self $accumulated, $key, $value) => $accumulated->append($mapper($key, $value)),
            self::new(),
        );
    }

    /**
     * Iterate over the collection
     *
     * @param callable(K, V): void $callback The callback function
     */
    public function forEach(callable $callback): void
    {
        foreach ($this->scalars as $pair) {
            $callback($pair[0], $pair[1]);
        }

        foreach ($this->objects as $object) {
            /** @var K $object */
            $callback($object, $this->objects[$object]);
        }
    }

    /**
     * Fold the collection
     *
     * @template U
     * @template T
     * @param callable(U|T, K, V): T $callback The callback function
     * @param U $initial The initial value
     * @return U|T
     */
    public function fold(callable $callback, mixed $initial): mixed
    {
        $carry = $initial;

        foreach ($this->scalars as $pair) {
            $carry = $callback($carry, $pair[0], $pair[1]);
        }

        foreach ($this->objects as $object) {
            /** @var K $object */
            $carry = $callback($carry, $object, $this->objects[$object]);
        }

        return $carry;
    }

    /**
     * Get the value associated with a key
     *
     * @param K $key The key to retrieve the value for
     * @return Option<V>
     */
    public function get(mixed $key): Option
    {
        if (\is_object($key)) {
            if ($this->objects->contains($key)) {
                return Option::some($this->objects[$key]);
            }
            return Option::none();
        }

        $hash = $this->encodeKey($key);
        if (isset($this->scalars[$hash])) {
            return Option::some($this->scalars[$hash][1]);
        }

        return Option::none();
    }

    /**
     * Add a key-value pair into the map
     *
     * @param K $key The key to insert
     * @param V $value The value to insert
     * @return self<K, V>
     */
    public function add(mixed $key, mixed $value): self
    {
        if (\is_object($key)) {
            $newObjects = clone $this->objects;
            $newObjects->attach($key, $value);
            return new self($this->scalars, $newObjects);
        }

        $newScalars = $this->scalars;
        $newScalars[$this->encodeKey($key)] = [$key, $value];
        return new self($newScalars, $this->objects);
    }

    /**
     * Remove a key-value pair from the map
     *
     * @param K $key The key of the pair to remove
     * @return self<K, V>
     */
    public function remove(mixed $key): self
    {
        if (\is_object($key)) {
            if (!$this->objects->contains($key)) {
                return $this;
            }
            $newObjects = clone $this->objects;
            $newObjects->detach($key);
            return new self($this->scalars, $newObjects);
        }

        $hash = $this->encodeKey($key);
        if (!isset($this->scalars[$hash])) {
            return $this;
        }

        $newScalars = $this->scalars;
        unset($newScalars[$hash]);
        return new self($newScalars, $this->objects);
    }

    /**
     * Check if the collection is empty
     *
     */
    public function isEmpty(): bool
    {
        return empty($this->scalars) && $this->objects->count() === 0;
    }

    /**
     * Return a set of all keys in the map
     *
     * @return Set<K>
     */
    public function keys(): Set
    {
        // For efficiency, we should probably construct Set directly from keys,
        // but Set logic relies on Sequence. For now, delegate to Set::of logic.
        // Or refactor Set later.
        $keys = [];
        foreach ($this->scalars as $pair) {
            $keys[] = $pair[0];
        }
        foreach ($this->objects as $object) {
            $keys[] = $object;
        }

        return Set::ofArray($keys);
    }

    /**
     * Return a sequence of all values in the map
     *
     * @return Sequence<V>
     */
    public function values(): Sequence
    {
        $values = [];
        foreach ($this->scalars as $pair) {
            $values[] = $pair[1];
        }
        foreach ($this->objects as $object) {
            $values[] = $this->objects[$object];
        }

        return Sequence::ofArray($values);
    }

    /**
     * Check if the map is equal to another map
     *
     * @param self<K, V> $other The map to compare with
     */
    public function eq(self $other): bool
    {
        if ($this->size()->toInt() !== $other->size()->toInt()) {
            return false;
        }

        // Check if all scalar keys exist and match values strict
        foreach ($this->scalars as $hash => $pair) {
            if (!$other->containsKey($pair[0])) {
                return false;
            }
            // Strict value check
            if ($other->get($pair[0])->unwrap() !== $pair[1]) {
                return false;
            }
        }

        // Check objects
        foreach ($this->objects as $object) {
            if (!$other->containsKey($object)) {
                return false;
            }
            // Strict value check
            if ($other->get($object)->unwrap() !== $this->objects[$object]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Find a key-value pair in the map that satisfies the given predicate
     *
     * @param callable(K, V): bool $predicate The predicate to apply to each key-value pair
     * @return Option<Pair<K, V>>
     */
    public function find(callable $predicate): Option
    {
        foreach ($this->scalars as $pair) {
            if ($predicate($pair[0], $pair[1])) {
                return Option::some(Pair::of($pair[0], $pair[1]));
            }
        }

        foreach ($this->objects as $object) {
            $value = $this->objects[$object];
            /** @var K $object */
            if ($predicate($object, $value)) {
                return Option::some(Pair::of($object, $value));
            }
        }

        return Option::none();
    }

    /**
     * Append all key-value pairs from another map to this map
     *
     * @param self<K, V> $other The map to append
     * @return self<K, V>
     */
    public function append(self $other): self
    {
        $newScalars = $this->scalars;
        foreach ($other->scalars as $hash => $pair) {
            $newScalars[$hash] = $pair;
        }

        if ($this->objects->count() === 0 && $other->objects->count() > 0) {
            $newObjects = clone $other->objects;
        } elseif ($other->objects->count() === 0) {
            $newObjects = clone $this->objects;
        } else {
            $newObjects = clone $this->objects;
            $newObjects->addAll($other->objects);
        }

        return new self($newScalars, $newObjects);
    }

    /**
     * Return the length of the map
     *
     */
    public function size(): Integer
    {
        return Integer::of(\count($this->scalars) + $this->objects->count());
    }

    /**
     * Convert the map to an array
     *
     * @return array<array{K, V}>
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->scalars as $pair) {
            $result[] = [$pair[0], $pair[1]];
        }
        foreach ($this->objects as $object) {
            $result[] = [$object, $this->objects[$object]];
        }
        return $result;
    }

    private function encodeKey(mixed $key): string
    {
        return self::staticEncodeKey($key);
    }

    private static function staticEncodeKey(mixed $key): string
    {
        return match (true) {
            \is_string($key) => 's:' . $key,
            \is_int($key) => 'i:' . $key,
            \is_null($key) => 'n:',
            \is_bool($key) => 'b:' . ($key ? '1' : '0'),
            \is_float($key) => 'f:' . $key,
            \is_array($key) => 'a:' . \md5(\serialize($key)), // Robust array hashing
            \is_resource($key) => 'r:' . \get_resource_id($key),
            default => 'u:' . \serialize($key),
        };
    }
}
