<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Pair;
use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Error\IndexOutOfBounds;
use Jsadaa\PhpCoreLibrary\Modules\Collections\Set\Set;
use Jsadaa\PhpCoreLibrary\Modules\Option\Option;
use Jsadaa\PhpCoreLibrary\Modules\Result\Result;

/**
 * An ordered collection of elements of the same type.
 * Type safety is enforced via static analysis only - no runtime type checking.
 *
 * @psalm-immutable
 * @template T
 */
final readonly class Sequence
{
    /**
     * @var array<int, T>
     */
    private array $collection;

    /**
     * @param array<array-key, T> $collection
     */
    private function __construct(array $collection = [])
    {
        $this->collection = \array_values($collection);
    }

    /**
     * Return a string representation of the collection
     *
     * @return string The string representation of the collection
     */
    public function __toString(): string
    {
        if ($this->isEmpty()) {
            return 'Sequence<>';
        }

        $type = \is_object($this->collection[0])
            ? $this->collection[0]::class
            : \get_debug_type($this->collection[0]);

        return 'Sequence<' . $type . '>';
    }

    /**
     * Check if all elements satisfy the given predicate
     *
     * @param callable(T): bool $callback The predicate to check each element against
     * @return bool True if all elements satisfy the predicate, false otherwise
     */
    public function all(callable $callback): bool
    {
        foreach ($this->collection as $value) {
            /** @psalm-suppress ImpureFunctionCall */
            if (!$callback($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if any element satisfies the given predicate
     *
     * @param callable(T): bool $callback The predicate to check each element against
     * @return bool True if any element satisfies the predicate, false otherwise
     */
    public function any(callable $callback): bool
    {
        foreach ($this->collection as $value) {
            /** @psalm-suppress ImpureFunctionCall */
            if ($callback($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Map each element to an optional value and filter out the None values
     *
     * @template U
     * @param callable(T): Option<U> $callback The function to map each element to an optional value
     * @return self<U> The new collection with the mapped elements
     */
    public function filterMap(callable $callback): self
    {
        $result = [];

        foreach ($this->collection as $value) {
            /** @psalm-suppress ImpureFunctionCall */
            $mapped = $callback($value);

            if ($mapped->isSome()) {
                $result[] = $mapped->unwrap();
            }
        }

        return new self($result);
    }

    /**
     * Map each element to an optional value and return the first Some value
     *
     * @template U
     * @param callable(T): Option<U> $callback The function to map each element to an optional value
     * @return Option<U> The first Some value or None if no element matches
     */
    public function findMap(callable $callback): Option
    {
        foreach ($this->collection as $value) {
            /** @psalm-suppress ImpureFunctionCall */
            $mapped = $callback($value);

            if ($mapped->isSome()) {
                return $mapped;
            }
        }

        return Option::none();
    }

    /**
     * Check if two Sequences are equal
     *
     * @param Sequence<T> $other The Sequence to compare with
     * @return bool True if the Sequences are equal, false otherwise
     */
    public function eq(self $other): bool
    {
        if ($this->size() !== $other->size()) {
            return false;
        }

        foreach ($this->collection as $key => $value) {
            if ($value !== $other->collection[$key]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the length of the collection
     *
     * @return int The length of the collection
     */
    public function size(): int
    {
        return \count($this->collection);
    }

    /**
     * Zip two Sequences together
     *
     * If the Sequences are of different lengths, the resulting Sequence will be as long as the shorter one.
     *
     * @template U
     * @param self<U> $other The Sequence to zip with
     * @return self<Pair<T, U>> A Sequence containing Pair elements
     */
    public function zip(self $other): self
    {
        $zipped = [];
        $length = \min($this->size(), $other->size());

        for ($i = 0; $i < $length; $i++) {
            $zipped[] = Pair::of($this->collection[$i], $other->collection[$i]);
        }

        return new self($zipped);
    }

    /**
     * Create a new collection from the given elements
     *
     * @template U
     * @param U ...$elements The elements to create the collection from
     * @return self<U> The new collection
     * @psalm-pure
     */
    public static function of(mixed ...$elements): self
    {
        /** @var self<U> */
        return new self($elements);
    }

    /**
     * Create a new collection from the given array
     *
     * This is a helper constructor to avoid performances issues with variadic arguments in the Sequence::of method when you work with large arrays.
     *
     * @template U
     * @param array<U> $elements The array to create the collection from
     * @return self<U> The new collection
     * @psalm-pure
     */
    public static function ofArray(array $elements): self
    {
        /** @var self<U> */
        return new self($elements);
    }

    /**
     * Append another collection to the current one
     *
     * @param self<T> $other The collection to append
     * @return self<T> The new collection with the elements appended
     */
    public function append(self $other): self
    {
        return new self(\array_merge($this->collection, $other->collection));
    }

    /**
     * Clear the collection
     *
     * @template U
     * @return self<U> The cleared collection
     */
    public function clear(): self
    {
        return self::new();
    }

    /**
     * Create a new empty collection
     *
     * @template U
     * @return self<U> The new empty collection
     * @psalm-pure
     */
    public static function new(): self
    {
        return new self();
    }

    /**
     * Check if the collection is empty
     *
     * @return bool True if the collection is empty, false otherwise
     */
    public function isEmpty(): bool
    {
        return \count($this->collection) === 0;
    }

    /**
     * Get an element from the collection at the given index
     *
     * @param int $index The index of the element to get
     * @return Option<T> The element at the given index, or None if the index is out of bounds
     */
    public function get(int $index): Option
    {
        return isset($this->collection[$index])
            ? Option::some($this->collection[$index])
            : Option::none();
    }

    /**
     * Find the first element in the collection that satisfies the given predicate
     *
     * @param callable(T): bool $predicate The predicate to apply to each element
     * @return Option<T> The first element that satisfies the predicate, or None if no such element exists
     */
    public function find(callable $predicate): Option
    {
        foreach ($this->collection as $item) {
            /** @psalm-suppress ImpureFunctionCall */
            if ($predicate($item)) {
                return Option::some($item);
            }
        }

        return Option::none();
    }

    /**
     * Get the index of the first occurrence of the given element
     *
     * @param mixed $element The element to search for
     * @return Option<int> The index of the first occurrence of the element, or None if not found
     */
    public function indexOf(mixed $element): Option
    {
        $index = \array_search($element, $this->collection, true);

        return $index !== false ? Option::some($index) : Option::none();
    }

    /**
     * Check if the collection contains the given element
     *
     * @param T $element The element to check for
     * @return bool True if the collection contains the element, false otherwise
     */
    public function contains(mixed $element): bool
    {
        return \in_array($element, $this->collection, true);
    }

    /**
     * Filter the elements of the collection using the given callback
     *
     * Array keys are not preserved
     *
     * @param callable(T): bool $callback The callback to apply to each element
     * @return self<T> A new collection with the filtered elements
     */
    public function filter(callable $callback): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self(\array_values(\array_filter($this->collection, $callback)));
    }

    /**
     * fold the collection to a single value using the given callback
     *
     * @template U
     * @param callable(U, T): U $callback The callback to apply to each element
     * @param U $initialValue The initial value for the reduction
     * @return U The result of the reduction
     */
    public function fold(callable $callback, mixed $initialValue): mixed
    {
        /** @psalm-suppress ImpureFunctionCall */
        return \array_reduce($this->collection, $callback, $initialValue);
    }

    /**
     * Generate windows of the given size from the collection
     *
     * Creates sliding windows of a specified size over the elements in the collection.
     * For example, a Sequence with [1, 2, 3, 4] and a window size of 2 will produce
     * [[1, 2], [2, 3], [3, 4]].
     *
     * If the size provided is a negative, it will be treated as zero and will return an empty Sequence.
     *
     * @param positive-int $size The size of the windows (must be positive)
     * @return self<Sequence<T>> A new collection containing the windows
     */
    public function windows(int $size): self
    {
        /** @psalm-suppress DocblockTypeContradiction -- defensive guard for runtime safety */
        if ($size <= 0) {
            return self::new();
        }

        $windows = [];
        $count = $this->size();

        // If the collection is smaller than the window size, return an empty collection
        if ($count < $size) {
            return self::new();
        }

        for ($i = 0; $i <= $count - $size; $i++) {
            $window = \array_slice($this->collection, $i, $size);
            $windows[] = self::of(...$window);
        }

        return self::of(...$windows);
    }

    /**
     * Map elements to iterables and then flatten the result into a single collection
     *
     * This method applies a transformation to each element in the collection, where each
     * transformation produces an iterable (such as an array or another Sequence). It then
     * flattens all these iterables into a single Sequence.
     *
     * This is equivalent to calling map() followed by flatten().
     *
     * @template U
     * @param callable(T): iterable<U> $callback The callback to apply to each element
     * @return self<U> A new collection with the mapped and flattened elements
     */
    public function flatMap(callable $callback): self
    {
        return $this->map($callback)->flatten();
    }

    /**
     * Flattens a collection of collections into a single collection
     *
     * If the collection contains Sequence instances or other iterables (like arrays),
     * this method extracts all elements from those nested collections and combines
     * them into a single Sequence. Only one level of nesting is flattened.
     *
     * @template U
     * @return self<U> A new collection with the elements flattened
     */
    public function flatten(): self
    {
        /** @var array<int, U> $flattened */
        $flattened = [];

        foreach ($this->collection as $item) {
            // Handle Sequence objects
            if ($item instanceof self) {
                /** @var array<int, U> $array */
                $array = $item->toArray();

                foreach ($array as $subItem) {
                    $flattened[] = $subItem;
                }
            } // Handle other iterables (arrays, etc.)
            elseif (\is_iterable($item)) {
                /** @var iterable<U> $item */
                foreach ($item as $subItem) {
                    $flattened[] = $subItem;
                }
            } // Handle scalar or non-iterable objects
            else {
                /** @var U $item */
                $flattened[] = $item;
            }
        }

        return self::of(...$flattened);
    }

    /**
     * Convert the collection to an array
     *
     * @return array<int, T> The array representation of the collection
     */
    public function toArray(): array
    {
        return $this->collection;
    }

    /**
     * Map the elements of the collection using the given callback
     *
     * @template U
     * @param callable(T): U $callback The callback to apply to each element
     * @return self<U> A new collection with the mapped elements
     */
    public function map(callable $callback): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self(\array_map($callback, $this->collection));
    }

    /**
     * Apply the given callback to each element of the collection
     *
     * This should be used when you want to perform an action that has a side effect, such as printing, logging, or updating a database.
     *
     * @param callable(T): void $callback The callback to apply to each element
     */
    public function forEach(callable $callback): void
    {
        foreach ($this->collection as $item) {
            /** @psalm-suppress ImpureFunctionCall */
            $callback($item);
        }
    }

    /**
     * Insert an element at the given index
     *
     * @param int $index The index at which to insert the element
     * @param T $item The element to insert
     * @return self<T> A new collection with the element inserted
     */
    public function insertAt(int $index, mixed $item): self
    {
        $newCollection = $this->collection;
        \array_splice($newCollection, $index, 0, [$item]);

        return new self($newCollection);
    }

    /**
     * Add an element to the end of the collection
     *
     * @param T $item The element to append
     * @return self<T> A new collection with the element appended
     */
    public function add(mixed $item): self
    {
        $newCollection = $this->collection;
        $newCollection[] = $item;

        return new self($newCollection);
    }

    /**
     * Remove an element at the specified index
     *
     * @param int<0, max> $index The index of the element to remove
     * @return self<T> A new collection with the element removed
     */
    public function removeAt(int $index): self
    {
        if ($index < 0 || $index >= $this->size() || $this->isEmpty()) {
            return new self($this->collection);
        }

        $newCollection = $this->collection;
        \array_splice($newCollection, $index, 1);

        return new self($newCollection);
    }

    /**
     * Return the first element of the collection
     *
     * @return Option<T> The first element of the collection or None if the collection is empty
     */
    public function first(): Option
    {
        if ($this->isEmpty()) {
            return Option::none();
        }

        return Option::some($this->collection[0]);
    }

    /**
     * Return the last element of the collection
     *
     * @return Option<T> The last element of the collection or None if the collection is empty
     */
    public function last(): Option
    {
        if ($this->isEmpty()) {
            return Option::none();
        }

        return Option::some($this->collection[\count($this->collection) - 1]);
    }

    /**
     * Return a new Sequence with duplicate elements removed
     *
     * For scalar types (integer, float, string, boolean), this uses a hash map approach.
     * For objects, objects are compared by reference, so only identical object instances are considered duplicates.
     *
     * @return self<T> A new Sequence with duplicate elements removed
     */
    public function unique(): self
    {
        if ($this->isEmpty()) {
            return new self();
        }

        // Determine type checking strategy based on first element
        $isObject = \is_object($this->collection[0]);
        $isArray = \is_array($this->collection[0]);

        $uniqueElements = [];
        $seen = [];

        if ($isObject) {
            foreach ($this->collection as $item) {
                if (!\is_object($item)) {
                    $uniqueElements[] = $item;

                    continue;
                }
                $hash = \spl_object_hash($item);

                if (!isset($seen[$hash])) {
                    $uniqueElements[] = $item;
                    $seen[$hash] = true;
                }
            }
        } elseif ($isArray) {
            foreach ($this->collection as $item) {
                if (!\is_array($item)) {
                    $uniqueElements[] = $item;

                    continue;
                }
                /** @psalm-suppress ImpureFunctionCall */
                $key = \serialize($item);

                if (!isset($seen[$key])) {
                    $uniqueElements[] = $item;
                    $seen[$key] = true;
                }
            }

        } else {
            foreach ($this->collection as $item) {
                $key = (string) $item;

                if (!isset($seen[$key])) {
                    $uniqueElements[] = $item;
                    $seen[$key] = true;
                }
            }
        }

        return new self($uniqueElements);
    }

    /**
     * Resize the collection to a given size, filling with a specified value if necessary.
     *
     * If the provided size is negative, it will be treated as zero.
     *
     * @param positive-int $size The size to resize to.
     * @param T $value The value to fill with if the collection is growing.
     * @return self<T> The resized collection.
     */
    public function resize(int $size, mixed $value): self
    {
        $size = \max($size, 0);

        if ($this->isEmpty()) {
            return new self(\array_fill(0, $size, $value));
        }

        if ($size < $this->size()) {
            return new self(\array_slice($this->collection, 0, $size));
        }

        $newCollection = \array_merge(
            $this->collection,
            \array_fill(0, $size - $this->size(), $value),
        );

        return new self($newCollection);
    }

    /**
     * Truncates the Sequence to the specified size.
     *
     * @param int<0, max> $size The size to truncate to.
     * @return self<T> The new Sequence with the specified size.
     */
    public function truncate(int $size): self
    {
        if ($size <= 0) {
            return new self([]);
        }

        return new self(\array_slice($this->collection, 0, $size));
    }

    /**
     * Reverses the order of elements in the Sequence.
     *
     * @return self<T> The new Sequence with elements in reverse order.
     */
    public function reverse(): self
    {
        return new self(\array_reverse($this->collection));
    }

    /**
     * Takes elements from the beginning of the Sequence while the predicate is true and returns a new Sequence.
     *
     * @param callable(T): bool $predicate The predicate to apply to each element.
     * @return self<T> The new Sequence containing the elements that satisfy the predicate.
     */
    public function takeWhile(callable $predicate): self
    {
        $index = 0;

        foreach ($this->collection as $item) {
            /** @psalm-suppress ImpureFunctionCall */
            if (!$predicate($item)) {
                break;
            }
            $index++;
        }

        return new self(\array_slice($this->collection, 0, $index));
    }

    /**
     * Creates a new Sequence by skipping elements from the beginning of the Sequence while the predicate is true.
     *
     * @param callable(T): bool $predicate The predicate to apply to each element.
     * @return self<T> The new Sequence containing the elements that do not satisfy the predicate.
     */
    public function skipWhile(callable $predicate): self
    {
        $index = 0;

        foreach ($this->collection as $item) {
            /** @psalm-suppress ImpureFunctionCall */
            if (!$predicate($item)) {
                break;
            }
            $index++;
        }

        return new self(\array_slice($this->collection, $index));
    }

    /**
     * Takes elements from the beginning of the Sequence up to the specified count and returns a new Sequence.
     *
     * If the provided count is negative, it will be treated as zero.
     *
     * @param int<0, max> $count The number of elements to take.
     * @return self<T> The new Sequence containing the taken elements.
     */
    public function take(int $count): self
    {
        $count = \max($count, 0);

        return new self(\array_slice($this->collection, 0, $count));
    }

    /**
     * Skips elements from the beginning of the Sequence up to the specified count and returns a new Sequence.
     *
     * If the provided count is negative, it will be treated as zero.
     *
     * @param int<0, max> $count The number of elements to skip.
     * @return self<T> The new Sequence containing the elements after skipping.
     */
    public function skip(int $count): self
    {
        $count = \max($count, 0);

        return new self(\array_slice($this->collection, $count));
    }

    /**
     * Swaps two elements in the Sequence and returns a new Sequence.
     *
     * @param int $index1 The index of the first element to swap.
     * @param int $index2 The index of the second element to swap.
     * @return Result<self<T>, IndexOutOfBounds> The result of the swap operation.
     */
    public function swap(int $index1, int $index2): Result
    {
        if ($index1 < 0 || $index1 >= $this->size()) {
            /** @var Result<self<T>, IndexOutOfBounds> */
            return Result::err(new IndexOutOfBounds($index1, $this->size()));
        }

        if ($index2 < 0 || $index2 >= $this->size()) {
            /** @var Result<self<T>, IndexOutOfBounds> */
            return Result::err(new IndexOutOfBounds($index2, $this->size()));
        }

        $collection = $this->collection;
        $temp = $collection[$index1];
        $collection[$index1] = $collection[$index2];
        $collection[$index2] = $temp;

        /** @var Result<self<T>, IndexOutOfBounds> */
        return Result::ok(new self($collection));
    }

    /**
     * Returns an iterable for the collection.
     *
     * @return iterable<T> An iterable for the collection
     */
    public function iter(): iterable
    {
        foreach ($this->collection as $item) {
            yield $item;
        }
    }

    /**
     * Sorts the collection using a custom comparator.
     *
     * @param callable(T, T): int $comparator The comparison function must return an integer less than, equal to, or greater than zero if the first argument is considered to be respectively less than, equal to, or greater than the second.
     * @return self<T> A new Sequence instance with the elements sorted
     */
    public function sortBy(callable $comparator): self
    {
        $sorted = $this->collection;

        /** @psalm-suppress ImpureFunctionCall */
        \usort($sorted, $comparator);

        return new self($sorted);
    }

    /**
     * Sorts the collection using the default sorting algorithm.
     *
     * @return self<T> A new Sequence instance with the elements sorted
     */
    public function sort(): self
    {
        if ($this->isEmpty()) {
            return new self([]);
        }

        $sorted = $this->collection;
        $sortType = match (\get_debug_type($sorted[0])) {
            'integer', 'float' => \SORT_NUMERIC,
            'string' => \SORT_STRING,
            default => \SORT_REGULAR,
        };

        \sort($sorted, $sortType);

        return new self($sorted);
    }

    /**
     * Converts the collection to a Set.
     *
     * @return Set<T> A new Set instance with the deduplicated elements of the collection
     */
    public function toSet(): Set
    {
        return Set::ofArray($this->collection);
    }
}
