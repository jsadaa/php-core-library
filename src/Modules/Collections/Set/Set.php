<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Collections\Set;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Map\Map;
use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use Jsadaa\PhpCoreLibrary\Modules\Option\Option;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;

/**
 * A collection of unique values.
 *
 * @template T
 * @psalm-immutable
 */
final readonly class Set
{
    /** @var Map<T, bool> */
    private Map $map;

    /**
     * @param Map<T, bool> $map
     */
    private function __construct(Map $map)
    {
        $this->map = $map;
    }

    /**
     * Creates a new set from the given values.
     *
     * @template U
     * @param U ...$values
     * @psalm-pure
     * @return Set<U>
     */
    public static function of(mixed ...$values): self
    {
        return new self(Map::fromKeys($values, true));
    }

    /**
     * Adds a value to the set.
     *
     * @param T $value
     * @return Set<T>
     */
    public function add(mixed $value): self
    {
        return new self($this->map->add($value, true));
    }

    /**
     * Clears the set.
     *
     * @return Set<T>
     */
    public function clear(): self
    {
        return new self(Map::new());
    }

    /**
     * Checks if the set contains a value.
     *
     * @param T $value
     */
    public function contains(mixed $value): bool
    {
        return $this->map->containsKey($value);
    }

    /**
     * Computes the difference between two sets.
     *
     * @param Set<T> $other
     * @return Set<T>
     */
    public function difference(self $other): self
    {
        return $this->filter(static fn($value) => !$other->contains($value));
    }

    /**
     * Computes the intersection of two sets.
     *
     * @param Set<T> $other
     * @return Set<T>
     */
    public function intersection(self $other): self
    {
        return $this->filter(static fn($value) => $other->contains($value));
    }

    /**
     * Checks if the set is disjoint with another set.
     *
     * @param Set<T> $other
     */
    public function isDisjoint(self $other): bool
    {
        return $this->intersection($other)->isEmpty();
    }

    /**
     * Checks if the set is empty.
     *
     */
    public function isEmpty(): bool
    {
        return $this->map->isEmpty();
    }

    /**
     * Checks if the set is equal to another set.
     *
     * @param Set<T> $other
     */
    public function eq(self $other): bool
    {
        if ($this->size()->toInt() !== $other->size()->toInt()) {
            return false;
        }

        // Check if all keys in this set exist in other set
        return $this->all(static fn($value) => $other->contains($value));
    }

    /**
     * Checks if the set is a subset of another set.
     *
     * @param Set<T> $other
     */
    public function isSubset(self $other): bool
    {
        return $this->all(static fn($value) => $other->contains($value));
    }

    /**
     * Checks if the set is a superset of another set.
     *
     * @param Set<T> $other
     */
    public function isSuperset(self $other): bool
    {
        return $other->isSubset($this);
    }

    /**
     * Returns the number of elements in the set.
     *
     */
    public function size(): Integer
    {
        return $this->map->size();
    }

    /**
     * Removes an element from the set.
     *
     * @param T $value
     * @return self<T>
     */
    public function remove(mixed $value): self
    {
        return new self($this->map->remove($value));
    }

    /**
     * Append another set to this set.
     *
     * @param Set<T> $other
     * @return self<T>
     */
    public function append(self $other): self
    {
        return new self($this->map->append($other->map));
    }

    /**
     * Map each element of the set to a new value.
     *
     * @template U
     * @param callable(T): U $fn
     * @return self<U>
     */
    public function map(callable $fn): self
    {
        /** @var list<U> $newValues */
        $newValues = [];
        $this->forEach(
            /** @param T $val */
            static fn(mixed $val) => $newValues[] = $fn($val),
        );

        return self::of(...$newValues);
    }

    /**
     * Map elements to iterables and then flatten the result into a single collection
     *
     * @template U
     * @param callable(T): iterable<U> $fn
     * @return self<U>
     */
    public function flatMap(callable $fn): self
    {
        /** @var list<U> $newValues */
        $newValues = [];
        $this->forEach(
            /**
             * @param T $val
             */
            static function (mixed $val) use (&$newValues, $fn): void {
                foreach ($fn($val) as $item) {
                    $newValues[] = $item;
                }
            },
        );

        return self::of(...$newValues);
    }

    /**
     * Filter elements based on a predicate function.
     *
     * @param callable(T): bool $fn
     * @return self<T>
     */
    public function filter(callable $fn): self
    {
        return new self($this->map->filter(
            /**
             * @param T $key
             */
            static fn(mixed $key, bool $_): bool => $fn($key),
        ));
    }

    /**
     * Check if any element in the set satisfies the given predicate.
     *
     * @param callable(T): bool $fn
     */
    public function any(callable $fn): bool
    {
        return $this->map->find(
            /**
             * @param T $key
             */
            static fn(mixed $key, bool $_): bool => $fn($key),
        )->isSome();
    }

    /**
     * Check if all elements in the set satisfy the given predicate.
     *
     * @param callable(T): bool $fn
     */
    public function all(callable $fn): bool
    {
        $foundMismatch = $this->map->find(
            /**
             * @param T $key
             */
            static fn(mixed $key, bool $_): bool => !$fn($key),
        )->isSome();

        return !$foundMismatch;
    }

    /**
     * Map each element to an optional value and filter out the None values
     *
     * @template U
     * @param callable(T): Option<U> $fn
     * @return self<U>
     */
    public function filterMap(callable $fn): self
    {
        /** @var list<U> $newValues */
        $newValues = [];
        $this->forEach(
            /**
             * @param T $val
             */
            static function (mixed $val) use (&$newValues, $fn): void {
                $opt = $fn($val);

                if ($opt->isSome()) {
                    $newValues[] = $opt->unwrap();
                }
            },
        );

        return self::of(...$newValues);
    }

    /**
     * Fold the collection to a single value using the given callback
     *
     * @template U
     * @param callable(U, T): U $fn
     * @param U $initial
     * @return U
     */
    public function fold(callable $fn, mixed $initial): mixed
    {
        return $this->map->fold(
            /**
             * @param U $acc
             * @param T $key
             */
            static fn(mixed $acc, mixed $key, bool $_): mixed => $fn($acc, $key),
            $initial,
        );
    }

    /**
     * Flattens a collection of collections into a single collection
     *
     * @return self<T>
     */
    public function flatten(): self
    {
        return $this->flatMap(
            /**
             * @param T $x
             * @return iterable<T>
             * @psalm-suppress MixedReturnTypeCoercion
             */
            static fn(mixed $x): mixed => $x,
        );
    }

    /**
     * Apply the given callback to each element of the collection
     *
     * @param callable(T): void $fn
     */
    public function forEach(callable $fn): void
    {
        $this->map->forEach(
            /**
             * @param T $key
             */
            static function (mixed $key, bool $_) use ($fn): void {
                $fn($key);
            },
        );
    }

    /**
     * Convert the collection to an array
     *
     * @return array<T>
     */
    public function toArray(): array
    {
        /** @var array<T> $result */
        $result = [];
        $this->forEach(
            /**
             * @param T $val
             */
            static function (mixed $val) use (&$result): void {
                $result[] = $val;
            },
        );

        return $result;
    }

    /**
     * Converts the collection to a Sequence.
     *
     * @return Sequence<T> A new Sequence instance with the elements of the collection
     */
    public function toSequence(): Sequence
    {
        return Sequence::ofArray($this->toArray());
    }

    /**
     * Creates a new Set instance from an array.
     *
     * @template A
     * @param array<A> $array
     * @psalm-pure
     * @return Set<A> A new Set instance with the elements of the array
     */
    public static function ofArray(array $array): self
    {
        return new self(Map::fromKeys($array, true));
    }
}
