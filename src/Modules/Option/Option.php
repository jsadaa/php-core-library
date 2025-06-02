<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Option;

use Jsadaa\PhpCoreLibrary\Modules\Result\Result;

/**
 * Represents an optional value that can be either Some(value) or None.
 *
 * @template T The type of the contained value
 * @psalm-immutable
 */
final readonly class Option
{
    /**
     * @var Some<T>|None
     */
    private Some | None $value;

    /**
     * @param Some<T>|None $value
     */
    private function __construct(Some | None $value)
    {
        $this->value = $value;
    }

    /**
     * Returns a string representation of the Option.
     *
     */
    public function __toString(): string
    {
        if ($this->isSome()) {
            /** @var Some<T> $value */
            $value = $this->value;

            return 'Some<' . \get_debug_type($value->get()) . '>';
        }

        return 'None';
    }

    /**
     * Applies functions depending on whether the Option is Some or None.
     *
     * @template U
     * @param callable(T): U $some Function to apply if Option is Some
     * @param callable(): U $none Function to apply if Option is None
     * @return U
     */
    public function match(callable $some, callable $none): mixed
    {
        if ($this->isSome()) {
            /** @var Some<T> $value */
            $value = $this->value;

            /** @psalm-suppress ImpureFunctionCall */
            return $some($value->get());
        }

        /** @psalm-suppress ImpureFunctionCall */
        return $none();
    }

    /**
     * Checks if this Option is Some.
     *
     */
    public function isSome(): bool
    {
        return $this->value instanceof Some;
    }

    /**
     * Checks if this Option is Some and satisfies the given predicate.
     *
     * @param callable(T): bool $predicate Predicate to check
     * @return bool True if Option is Some and predicate is satisfied, false otherwise
     */
    public function isSomeAnd(callable $predicate): bool
    {
        if ($this->isSome()) {
            /** @var Some<T> $value */
            $value = $this->value;

            /** @psalm-suppress ImpureFunctionCall */
            return $predicate($value->get());
        }

        return false;
    }

    /**
     * Returns the contained value or throws an exception if None.
     *
     * @throws \RuntimeException If the Option is None
     * @return T The contained value
     */
    public function unwrap(): mixed
    {
        if ($this->isNone()) {
            throw new \RuntimeException('Cannot unwrap None');
        }

        /** @var Some<T> $value */
        $value = $this->value;

        return $value->get();
    }

    /**
     * Checks if this Option is None.
     *
     */
    public function isNone(): bool
    {
        return $this->value instanceof None;
    }

    /**
     * Checks if this Option is None or the provided predicate returns true.
     *
     * @param callable(T): bool $predicate Predicate to apply if Option is Some
     * @return bool True if Option is None or the predicate returns true
     */
    public function isNoneOr(callable $predicate): bool
    {
        if ($this->isNone()) {
            return true;
        }

        /** @var Some<T> $value */
        $value = $this->value;

        /** @psalm-suppress ImpureFunctionCall */
        return $predicate($value->get());
    }

    /**
     * Returns the contained value or returns a default value if None.
     *
     * @param T $default Default value to return if Option is None
     * @return T The contained value or the default
     */
    public function unwrapOr(mixed $default): mixed
    {
        if ($this->isNone()) {
            return $default;
        }

        /** @var Some<T> $value */
        $value = $this->value;

        return $value->get();
    }

    /**
     * Returns the contained value or call the default function if None.
     *
     * @param callable(): T $default Function to apply if Option is None
     * @return T The result of the default function or the contained value
     */
    public function unwrapOrElse(callable $default): mixed
    {
        if ($this->isNone()) {
            /** @psalm-suppress ImpureFunctionCall */
            return $default();
        }

        /** @var Some<T> $value */
        $value = $this->value;

        return $value->get();
    }

    /**
     * Maps the contained value using a mapper function.
     *
     * @template U
     * @param callable(T): U $mapper Mapper function to apply if Option is Some
     * @return Option<U> A new Option containing the mapped value or None
     */
    public function map(callable $mapper): self
    {
        if ($this->isNone()) {
            return self::none();
        }

        /** @var Some<T> $value */
        $value = $this->value;

        /** @psalm-suppress ImpureFunctionCall */
        return self::some($mapper($value->get()));
    }

    /**
     * Maps the contained value using a mapper function or returns a default value.
     *
     * @template U
     * @param callable(T): U $mapper Mapper function to apply if Option is Some
     * @param U $default Default value to return if Option is None
     * @return U The mapped value or the default value
     */
    public function mapOr(callable $mapper, mixed $default): mixed
    {
        if ($this->isNone()) {
            return $default;
        }

        /** @var Some<T> $value */
        $value = $this->value;

        /** @psalm-suppress ImpureFunctionCall */
        return $mapper($value->get());
    }

    /**
     * Maps the contained value using a mapper function or returns a default value.
     *
     * @template U
     * @param callable(T): U $mapper Mapper function to apply if Option is Some
     * @param callable(): U $default Default value to return if Option is None
     * @return U The mapped value or the default value
     */
    public function mapOrElse(callable $mapper, callable $default): mixed
    {
        if ($this->isNone()) {
            /** @psalm-suppress ImpureFunctionCall */
            return $default();
        }

        /** @var Some<T> $value */
        $value = $this->value;

        /** @psalm-suppress ImpureFunctionCall */
        return $mapper($value->get());
    }

    /**
     * Creates a new Option with no value.
     *
     * @template U
     * @return self<U>
     * @psalm-pure
     */
    public static function none(): self
    {
        return new self(None::new());
    }

    /**
     * Creates a new Option with a value.
     *
     * @template U
     * @param U $value
     * @return self<U>
     * @psalm-pure
     */
    public static function some(mixed $value): self
    {
        return new self(Some::of($value));
    }

    /**
     * Filters the contained value using a predicate function.
     *
     * @param callable(T): bool $predicate Predicate function to apply if Option is Some
     * @return Option<T> A new Option containing the original value if predicate is true, otherwise None
     */
    public function filter(callable $predicate): self
    {
        if ($this->isNone()) {
            return self::none();
        }

        /** @var Some<T> $value */
        $value = $this->value;
        $unwrapped = $value->get();

        /** @psalm-suppress ImpureFunctionCall */
        return $predicate($unwrapped) ? self::some($unwrapped) : self::none();
    }

    /**
     * Flattens the Option by unwrapping the contained value if it is an Option itself.
     *
     * Note : this only flattens one level of nesting.
     *
     * @return Option<T> The flattened Option
     */
    public function flatten(): self
    {
        if ($this->isNone()) {
            return self::none();
        }

        /** @var Some<T> $value */
        $value = $this->value;
        $unwrapped = $value->get();

        return $unwrapped instanceof self ? $unwrapped : self::some($unwrapped);
    }

    /**
     * Converts the Option to a Result, returning an error if the Option is None.
     *
     * @template E
     *
     * @param E $err Error value to use if Option is None
     * @return Result<T, E> A new Result containing the unwrapped value if Option is Some, otherwise an error
     */
    public function okOr(mixed $err): Result
    {
        if ($this->isNone()) {
            return Result::err($err);
        }

        /** @var Some<T> $value */
        $value = $this->value;
        $unwrapped = $value->get();

        return Result::ok($unwrapped);
    }

    /**
     * Converts the Option to a Result, returning an error if the Option is None.
     *
     * @template E
     *
     * @param callable(): E $err Error value to use if Option is None
     * @return Result<T, E> A new Result containing the unwrapped value if Option is Some, otherwise an error
     */
    public function okOrElse(callable $err): Result
    {
        if ($this->isNone()) {
            /** @psalm-suppress ImpureFunctionCall */
            return Result::err($err());
        }

        /** @var Some<T> $value */
        $value = $this->value;
        $unwrapped = $value->get();

        return Result::ok($unwrapped);
    }

    /**
     * Returns the Option if it is Some, otherwise returns the provided Option.
     *
     * @param Option<T> $other Option to return if this Option is None
     * @return Option<T> A new Option containing the unwrapped value if Option is Some, otherwise the provided Option
     */
    public function or(self $other): self
    {
        if ($this->isNone()) {
            return $other;
        }

        /** @var Some<T> $value */
        $value = $this->value;
        $unwrapped = $value->get();

        return self::some($unwrapped);
    }

    /**
     * Returns the Option if it is Some, otherwise returns the result of the provided callable.
     *
     * @param callable(): Option<T> $other Callable to return if this Option is None
     * @return Option<T> A new Option containing the unwrapped value if Option is Some, otherwise the result of the provided callable
     */
    public function orElse(callable $other): self
    {
        if ($this->isNone()) {
            /** @psalm-suppress ImpureFunctionCall */
            return $other();
        }

        /** @var Some<T> $value */
        $value = $this->value;
        $unwrapped = $value->get();

        return self::some($unwrapped);
    }

    /**
     * Calls the callback if the Option is Some, otherwise returns None.
     *
     * @template U
     * @param callable(T): Option<U> $callback Mapper function to apply if Option is Some
     * @return Option<U> A new Option containing the mapped value or None
     */
    public function andThen(callable $callback): self
    {
        if ($this->isNone()) {
            return self::none();
        }

        /** @var Some<T> $value */
        $value = $this->value;
        $unwrapped = $value->get();

        /** @psalm-suppress ImpureFunctionCall */
        return $callback($unwrapped);
    }

    /**
     * Execute a side effect on the Option's value if it is Some.
     *
     * @param callable(T): void $callback Callback to execute if this Option is Some
     * @return Option<T> A new Option containing the unwrapped value if Option is Some, otherwise None
     */
    public function inspect(callable $callback): self
    {
        if ($this->isSome()) {
            /** @var Some<T> $value */
            $value = $this->value;
            $unwrapped = $value->get();

            /** @psalm-suppress ImpureFunctionCall */
            $callback($unwrapped);

            return self::some($unwrapped);
        }

        return self::none();
    }
}
