<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Collections\Set;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use Jsadaa\PhpCoreLibrary\Modules\Option\Option;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;

/**
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
     * @template U
     * @param U ...$values
     * @psalm-pure
     * @return Set<U>
     */
    public static function of(mixed ...$values): self
    {
        /** @var self<U> */
        return new self(Sequence::of($values)->dedup());
    }

    /**
     * @param T $value
     * @return Set<T>
     */
    public function add(mixed $value): self
    {
        return new self($this->values->add($value)->dedup());
    }

    /**
     * @return Set<T>
     */
    public function clear(): self
    {
        return new self(Sequence::new());
    }

    /**
     * @param T $value
     */
    public function contains(mixed $value): bool
    {
        return $this->values->contains($value);
    }

    /**
     * @param Set<T> $other
     * @return Set<T>
     */
    public function difference(self $other): self
    {
        return new self($this->values->filter(static fn($value) => !$other->contains($value)));
    }

    /**
     * @param Set<T> $other
     * @return Set<T>
     */
    public function intersection(self $other): self
    {
        return new self($this->values->filter(static fn($value) => $other->contains($value)));
    }

    /**
     * @param Set<T> $other
     */
    public function isDisjoint(self $other): bool
    {
        return $this->intersection($other)->isEmpty();
    }

    public function isEmpty(): bool
    {
        return $this->values->isEmpty();
    }

    /**
     * @param Set<T> $other
     */
    public function eq(self $other): bool
    {
        return $this->values->eq($other->values);
    }

    /**
     * @param Set<T> $other
     */
    public function isSubset(self $other): bool
    {
        return $this->intersection($other)->eq($this);
    }

    /**
     * @param Set<T> $other
     */
    public function isSuperset(self $other): bool
    {
        return $this->intersection($other)->eq($other);
    }

    public function len(): Integer
    {
        return $this->values->len();
    }

    /**
     * @param T $value
     * @return self<T>
     */
    public function remove(mixed $value): self
    {
        return new self($this->values->filter(static fn($v) => $v !== $value));
    }

    /**
     * @param Set<T> $other
     * @return self<T>
     */
    public function append(self $other): self
    {
        return new self($this->values->append($other->values));
    }

    /**
     * @template U
     * @param callable(T): U $fn
     * @return self<U>
     */
    public function map(callable $fn): self
    {
        return new self($this->values->map($fn)->dedup());
    }

    /**
     * @template U
     * @param callable(T): iterable<U> $fn
     * @return self<U>
     */
    public function flatMap(callable $fn): self
    {
        return new self($this->values->flatMap($fn)->dedup());
    }

    /**
     * @param callable(T): bool $fn
     * @return self<T>
     */
    public function filter(callable $fn): self
    {
        return new self($this->values->filter($fn));
    }

    /**
     * @param callable(T): bool $fn
     */
    public function any(callable $fn): bool
    {
        return $this->values->any($fn);
    }

    /**
     * @param callable(T): bool $fn
     */
    public function all(callable $fn): bool
    {
        return $this->values->all($fn);
    }

    /**
     * @template U
     * @param callable(T): Option<U> $fn
     * @return self<U>
     */
    public function filterMap(callable $fn): self
    {
        return new self($this->values->filterMap($fn)->dedup());
    }

    /**
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
     * @return self<T>
     */
    public function flatten(): self
    {
        return new self($this->values->flatten());
    }

    /**
     * @param callable(T): void $fn
     */
    public function forEach(callable $fn): void
    {
        $this->values->forEach($fn);
    }

    /**
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
        return Sequence::ofArray($this->values->toArray());
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
        return new self(Sequence::ofArray($array)->dedup());
    }
}
