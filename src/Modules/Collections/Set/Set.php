<?php

declare(strict_types=1);

namespace Jsadaa\PhpCoreLibrary\Modules\Collections\Set;

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
    /** @var \Jsadaa\PhpCoreLibrary\Modules\Collections\Map\Map<T, bool> */
    private \Jsadaa\PhpCoreLibrary\Modules\Collections\Map\Map $map;

    /**
     * @param \Jsadaa\PhpCoreLibrary\Modules\Collections\Map\Map<T, bool> $map
     */
    private function __construct(\Jsadaa\PhpCoreLibrary\Modules\Collections\Map\Map $map)
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
        return new self(\Jsadaa\PhpCoreLibrary\Modules\Collections\Map\Map::fromKeys($values, true));
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
        return new self(\Jsadaa\PhpCoreLibrary\Modules\Collections\Map\Map::new());
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
        // Map merge of keys
        // We can expose underlying map via accessor if internal? 
        // No, use efficient construction via keys.
        // Actually, strictly speaking append is Union.
        // Just merge maps.
        // Accessing private map of other? private members are accessible by same class instances.
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
        // Extract values, map them, create new set
        $newValues = [];
        $this->forEach(static fn($val) => $newValues[] = $fn($val));
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
        $newValues = [];
        $this->forEach(function ($val) use (&$newValues, $fn) {
            foreach ($fn($val) as $item) {
                $newValues[] = $item;
            }
        });
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
        return new self($this->map->filter(static fn($key, $_) => $fn($key)));
    }

    /**
     * Check if any element in the set satisfies the given predicate.
     *
     * @param callable(T): bool $fn
     */
    public function any(callable $fn): bool
    {
        // Map::find/any takes (key, value)
        return $this->map->find(static fn($key, $_) => $fn($key))->isSome();
    }

    /**
     * Check if all elements in the set satisfy the given predicate.
     *
     * @param callable(T): bool $fn
     */
    public function all(callable $fn): bool
    {
        // If find ANY that does NOT match, return false.
        $foundMismatch = $this->map->find(static fn($key, $_) => !$fn($key))->isSome();
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
        $newValues = [];
        $this->forEach(function ($val) use (&$newValues, $fn) {
            $opt = $fn($val);
            if ($opt->isSome()) {
                $newValues[] = $opt->unwrap();
            }
        });
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
            static fn($acc, $key, $_) => $fn($acc, $key),
            $initial
        );
    }

    /**
     * Flattens a collection of collections into a single collection
     *
     * @return self<T>
     */
    public function flatten(): self
    {
        // Assuming T is iterable?
        return $this->flatMap(static fn($x) => $x);
    }

    /**
     * Apply the given callback to each element of the collection
     *
     * @param callable(T): void $fn
     */
    public function forEach(callable $fn): void
    {
        $this->map->forEach(static fn($key, $_) => $fn($key));
    }

    /**
     * Convert the collection to an array
     *
     * @return array<T>
     */
    public function toArray(): array
    {
        $result = [];
        $this->forEach(function ($val) use (&$result) {
            $result[] = $val;
        });
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
        return new self(\Jsadaa\PhpCoreLibrary\Modules\Collections\Map\Map::fromKeys($array, true));
    }
}
