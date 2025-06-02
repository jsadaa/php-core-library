<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Result;

use Jsadaa\PhpCoreLibrary\Modules\Option\Option;

/**
 * Represents a result that can either be successful or an error.
 *
 * @template T The type of the success value
 * @template E The type of the error value
 * @psalm-immutable
 *
 */
final readonly class Result
{
    /**
     * @var Ok<T>|Err<E>
     */
    private Ok | Err $value;

    /**
     * @param Ok<T>|Err<E> $value
     */
    private function __construct(Ok | Err $value)
    {
        $this->value = $value;
    }

    /**
     * Returns a string representation of the Result.
     *
     */
    public function __toString(): string
    {
        if ($this->isOk()) {
            return 'Result<' . \get_debug_type($this->value->get()) . '>';
        }

        return 'Err<' . \get_debug_type($this->value->get()) . '>';
    }

    /**
     * Checks if the result is an error.
     *
     */
    public function isErr(): bool
    {
        return $this->value instanceof Err;
    }

    /**
     * Checks if the result is an error and satisfies the given predicate.
     *
     * @param callable(E): bool $predicate The predicate to check
     * @return bool True if the result is an error and satisfies the predicate, false otherwise
     */
    public function isErrAnd(callable $predicate): bool
    {
        if ($this->isErr()) {
            /** @var E */
            $value = $this->value->get();

            /** @psalm-suppress ImpureFunctionCall */
            return $predicate($value);
        }

        return false;
    }

    /**
     * Unwraps the result, throwing an exception if it is an error.
     *
     * @throws \RuntimeException If the result is an error
     * @return T The success value
     */
    public function unwrap(): mixed
    {
        if ($this->value instanceof Ok) {
            /** @var T */
            $value = $this->value->get();

            return $value;
        }

        throw new \RuntimeException('Cannot unwrap Err');
    }

    /**
     * Unwraps the result, throwing an exception if it is successful.
     *
     * @throws \RuntimeException If the result is successful
     * @return E The error value
     */
    public function unwrapErr(): mixed
    {
        if ($this->value instanceof Err) {
            /** @var E */
            $value = $this->value->get();

            return $value;
        }

        throw new \RuntimeException('Cannot unwrap Ok');
    }

    /**
     * Returns the contained value or a default value if Err.
     *
     * @param T $default Default value to return if the result is an error
     * @return T The success value or the default
     */
    public function unwrapOr(mixed $default): mixed
    {
        if ($this->value instanceof Ok) {
            /** @var T */
            $value = $this->value->get();

            return $value;
        }

        return $default;
    }

    /**
     * Returns the contained value or call the default function if Err.
     *
     * @param callable(): T $default Function to apply if Err
     * @return T The success value or the result of the default function
     */
    public function unwrapOrElse(callable $default): mixed
    {
        if ($this->value instanceof Err) {
            /** @psalm-suppress ImpureFunctionCall */
            return $default();
        }

        return $this->value->get();
    }

    /**
     * Maps the contained value using a mapper function.
     * @template U
     * @param callable(T): U $mapper Mapper function to apply if the result is successful
     * @return Result<U, E> A new Result with the mapped value or the original error
     */
    public function map(callable $mapper): self
    {
        if ($this->value instanceof Err) {
            return self::err($this->value->get());
        }

        /** @psalm-suppress ImpureFunctionCall */
        return self::ok($mapper($this->value->get()));
    }

    /**
     * Maps the contained error using a mapper function.
     * @template F
     * @param callable(E): F $mapper Mapper function to apply if the result is an error
     * @return Result<T, F> A new Result with the mapped error or the original success value
     */
    public function mapErr(callable $mapper): self
    {
        if ($this->value instanceof Err) {
            /** @var E $unwrapped */
            $unwrapped = $this->value->get();

            /** @psalm-suppress ImpureFunctionCall */
            return self::err($mapper($unwrapped));
        }

        return self::ok($this->value->get());
    }

    /**
     * returns the provided default value if Error, or the original success value
     * @template U
     * @param callable(T): U $mapper Mapper function to apply if the result is a success
     * @param U $default Default value to return if the result is an error
     * @return U The mapped value or the default value
     */
    public function mapOr(callable $mapper, mixed $default): mixed
    {
        if ($this->value instanceof Err) {
            return $default;
        }

        /** @psalm-suppress ImpureFunctionCall */
        return $mapper($this->value->get());
    }

    /**
     * Maps the success value of the result using the provided mapper function,
     * or the error value to the provided default function
     *
     * @template U
     * @param callable(T): U $mapper Mapper function to apply if the result is a success
     * @param callable(E): U $default Mapper function to apply if the result is an error
     * @return U The mapped value or the default value
     */
    public function mapOrElse(callable $mapper, callable $default): mixed
    {
        if ($this->value instanceof Err) {
            /** @var E $unwrapped */
            $unwrapped = $this->value->get();

            /** @psalm-suppress ImpureFunctionCall */
            return $default($unwrapped);
        }

        /** @psalm-suppress ImpureFunctionCall */
        return $mapper($this->value->get());
    }

    /**
     * Creates an error result.
     *
     * @template F
     * @param F $error The error value
     * @return Result<T, F> A new Result with an error
     * @psalm-pure
     */
    public static function err(mixed $error): self
    {
        return new self(Err::of($error));
    }

    /**
     * Creates a successful result.
     *
     * @template U
     * @param U $value The success value
     * @return Result<U, E> A new Result with a success value
     * @psalm-pure
     */
    public static function ok(mixed $value): self
    {
        return new self(Ok::of($value));
    }

    /**
     * Checks if the result is successful.
     *
     */
    public function isOk(): bool
    {
        return $this->value instanceof Ok;
    }

    /**
     * Checks if the result is successful and satisfies a predicate.
     *
     * @param callable(T): bool $predicate The predicate to check
     * @return bool True if the result is successful and satisfies the predicate, false otherwise
     */
    public function isOkAnd(callable $predicate): bool
    {
        if ($this->isOk()) {
            /** @var Ok<T> $value */
            $value = $this->value;
            $unwrapped = $value->get();

            /** @psalm-suppress ImpureFunctionCall */
            return $predicate($unwrapped);
        }

        return false;
    }

    /**
     * Applies functions depending on whether the Result is successful or not.
     *
     * @template U
     * @template V
     * @param callable(T): U $ok Function to apply if the Result is successful
     * @param callable(E): V $err Function to apply if the Result is an error
     * @return U|V The result of the applied function
     */
    public function match(callable $ok, callable $err): mixed
    {
        if ($this->isOk()) {
            /** @var Ok<T> $value */
            $value = $this->value;
            $unwrapped = $value->get();

            /** @psalm-suppress ImpureFunctionCall */
            return $ok($unwrapped);
        }

        /** @var Err<E> $value */
        $value = $this->value;
        $unwrapped = $value->get();

        /** @psalm-suppress ImpureFunctionCall */
        return $err($unwrapped);
    }

    /**
     * Flattens a nested Result by unwrapping the contained value if it is a Result itself.
     *
     * Note : this only flattens one level of nesting.
     *
     * @return Result<T, E> The flattened Result
     */
    public function flatten(): self
    {
        if ($this->isOk()) {
            /** @var Ok<T> $value */
            $value = $this->value;
            $unwrapped = $value->get();

            return $unwrapped instanceof self ? $unwrapped : self::ok($unwrapped);
        }

        /** @var Err<E> $value */
        $value = $this->value;
        $unwrapped = $value->get();

        return $unwrapped instanceof self ? $unwrapped : self::err($unwrapped);
    }

    /**
     * Executes a side effect on the contained value if it is Ok.
     *
     * @param callable(T): void $callback The callback to execute
     * @return Result<T, E> The original Result
     */
    public function inspect(callable $callback): self
    {
        if ($this->isOk()) {
            /** @var Ok<T> $value */
            $value = $this->value;
            $unwrapped = $value->get();

            /** @psalm-suppress ImpureFunctionCall */
            $callback($unwrapped);

            return self::ok($unwrapped);
        }

        /** @var Err<E> $value */
        $value = $this->value;
        $unwrapped = $value->get();

        return self::err($unwrapped);
    }

    /**
     * Executes a side effect on the contained value if it is Err.
     *
     * @param callable(E): void $callback The callback to execute
     * @return Result<T, E> The original Result
     */
    public function inspectErr(callable $callback): self
    {
        if ($this->isErr()) {
            /** @var Err<E> $value */
            $value = $this->value;
            $unwrapped = $value->get();

            /** @psalm-suppress ImpureFunctionCall */
            $callback($unwrapped);

            return self::err($unwrapped);
        }

        /** @var Ok<T> $value */
        $value = $this->value;
        $unwrapped = $value->get();

        return self::ok($unwrapped);
    }

    /**
     * Returns an Option containing the contained value if it is Ok, or None if it is Err.
     *
     * @return Option<T> The Option containing the contained value if it is Ok, or None if it is Err
     */
    public function option(): Option
    {
        if ($this->isOk()) {
            /** @var Ok<T> $value */
            $value = $this->value;
            $unwrapped = $value->get();

            return Option::some($unwrapped);
        }

        return Option::none();
    }

    /**
     * Returns the contained value if it is Ok, or the value from the provided Result if it is Err.
     *
     * @param Result<T, E> $other The Result to use if the current Result is Err
     * @return Result<T, E> The contained value if it is Ok, or the value from the provided Result if it is Err
     */
    public function or(self $other): self
    {
        if ($this->isOk()) {
            /** @var T $unwrapped */
            $unwrapped = $this->value->get();

            return self::ok($unwrapped);
        }

        return $other;
    }

    /**
     * Returns the Ok value if it is Ok, or the value from the provided Result if it is Err.
     *
     * @template F

     * @param callable(E): Result<T, F> $callback The callback to use if the current Result is Err
     * @return Result<T, F> The contained value if it is Ok, or the value from the provided Result if it is Err
     */
    public function orElse(callable $callback): self
    {
        if ($this->isOk()) {
            /** @var T $unwrapped */
            $unwrapped = $this->value->get();

            return self::ok($unwrapped);
        }

        /** @var E $unwrapped */
        $unwrapped = $this->value->get();

        /** @psalm-suppress ImpureFunctionCall */
        return $callback($unwrapped);
    }

    /**
     * Applies a callback to the contained value if it is Ok, and returns the result.
     *
     * @template U
     * @param callable(T): Result<U, E> $callback The callback to apply if the Result is successful
     * @return Result<U, E> A new Result with the mapped value or the original error
     */
    public function andThen(callable $callback): self
    {
        if ($this->isOk()) {
            /** @var T $unwrapped */
            $unwrapped = $this->value->get();

            /** @psalm-suppress ImpureFunctionCall */
            return $callback($unwrapped);
        }

        /** @var E $unwrapped */
        $unwrapped = $this->value->get();

        return self::err($unwrapped);
    }
}
