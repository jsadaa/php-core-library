<?php

declare(strict_types = 1);

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
    /** @var Sequence<T> */
    private Sequence $values;

    /**
     * @param Sequence<T> $values
     */
    private function __construct(Sequence $values)
    {
        $this->values = $values;
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
        /** @var self<U> */
        return new self(Sequence::of($values)->unique());
    }

    /**
     * Adds a value to the set.
     *
     * @param T $value
     * @return Set<T>
     */
    public function add(mixed $value): self
    {
        return new self($this->values->add($value)->unique());
    }

    /**
     * Clears the set.
     *
     * @return Set<T>
     */
    public function clear(): self
    {
        return new self(Sequence::new());
    }

    /**
     * Checks if the set contains a value.
     *
     * @param T $value
     */
    public function contains(mixed $value): bool
    {
        return $this->values->contains($value);
    }

    /**
     * Computes the difference between two sets.
     *
     * @param Set<T> $other
     * @return Set<T>
     */
    public function difference(self $other): self
    {
        return new self($this->values->filter(static fn($value) => !$other->contains($value)));
    }

    /**
     * Computes the intersection of two sets.
     *
     * @param Set<T> $other
     * @return Set<T>
     */
    public function intersection(self $other): self
    {
        return new self($this->values->filter(static fn($value) => $other->contains($value)));
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
        return $this->values->isEmpty();
    }

    /**
     * Checks if the set is equal to another set.
     *
     * @param Set<T> $other
     */
    public function eq(self $other): bool
    {
        return $this->values->eq($other->values);
    }

    /**
     * Checks if the set is a subset of another set.
     *
     * @param Set<T> $other
     */
    public function isSubset(self $other): bool
    {
        return $this->intersection($other)->eq($this);
    }

    /**
     * Checks if the set is a superset of another set.
     *
     * @param Set<T> $other
     */
    public function isSuperset(self $other): bool
    {
        return $this->intersection($other)->eq($other);
    }

    /**
     * Returns the number of elements in the set.
     *
     */
    public function size(): Integer
    {
        return $this->values->size();
    }

    /**
     * Removes an element from the set.
     *
     * @param T $value
     * @return self<T>
     */
    public function remove(mixed $value): self
    {
        return new self($this->values->filter(static fn($v) => $v !== $value));
    }

    /**
     * Append another set to this set.
     *
     * @param Set<T> $other
     * @return self<T>
     */
    public function append(self $other): self
    {
        return new self($this->values->append($other->values));
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
        return new self($this->values->map($fn)->unique());
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
        return new self($this->values->flatMap($fn)->unique());
    }

    /**
     * Filter elements based on a predicate function.
     *
     * @param callable(T): bool $fn
     * @return self<T>
     */
    public function filter(callable $fn): self
    {
        return new self($this->values->filter($fn));
    }

    /**
     * Check if any element in the set satisfies the given predicate.
     *
     * @param callable(T): bool $fn
     */
    public function any(callable $fn): bool
    {
        return $this->values->any($fn);
    }

    /**
     * Check if all elements in the set satisfy the given predicate.
     *
     * @param callable(T): bool $fn
     */
    public function all(callable $fn): bool
    {
        return $this->values->all($fn);
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
        return new self($this->values->filterMap($fn)->unique());
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
        return $this->values->fold($fn, $initial);
    }

    /**
     * Flattens a collection of collections into a single collection
     *
     * @return self<T>
     */
    public function flatten(): self
    {
        return new self($this->values->flatten());
    }

    /**
     * Apply the given callback to each element of the collection
     *
     * @param callable(T): void $fn
     */
    public function forEach(callable $fn): void
    {
        $this->values->forEach($fn);
    }

    /**
     * Convert the collection to an array
     *
     * @return array<T>
     */
    public function toArray(): array
    {
        return $this->values->toArray();
    }

    /**
     * Converts the collection to a Sequence.
     *
     * @return Sequence<T> A new Sequence instance with the elements of the collection
     */
    public function toSequence(): Sequence
    {
        return $this->values;
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
        return new self(Sequence::ofArray($array)->unique());
    }
}
