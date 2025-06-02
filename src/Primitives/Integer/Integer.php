<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Primitives\Integer;

use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Error\DivisionByZero;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Error\IntegerOverflow;

/**
 * Represents an immutable wrapper around a PHP integer with safe operations.
 *
 * @psalm-immutable
 */
final readonly class Integer {
    private int $value;

    private function __construct(int $value)
    {
        $this->value = $value;
    }

    /**
     * Creates a new Integer instance from a native PHP integer.
     *
     * @param int $value The integer value to wrap
     * @return self A new Integer instance
     * @psalm-pure
     */
    public static function from(int $value): self
    {
        return new self($value);
    }

    /**
     * Returns the absolute value of this integer.
     *
     * @return self A new Integer with the absolute value
     */
    public function abs(): self
    {
        $newValue = \abs($this->value);

        if ($newValue >= \PHP_INT_MAX || $newValue <= \PHP_INT_MIN) {
            return new self(\PHP_INT_MAX);
        }

        return new self($newValue);
    }

    /**
     * Returns the absolute difference between this integer and another value.
     *
     * @param int|self $other The value to compute the absolute difference with
     * @return self A new Integer with the absolute difference
     */
    public function absDiff(int | self $other): self
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        $newValue = \abs($this->value - $other);

        if ($newValue >= \PHP_INT_MAX || $newValue <= \PHP_INT_MIN) {
            return new self(\PHP_INT_MAX);
        }

        return new self($newValue);
    }

    /**
     * Raises this integer to the power of the given exponent.
     *
     * @param int|self $exponent The exponent to raise this integer to
     * @return self A new Integer with the result of the power operation
     */
    public function pow(int | self $exponent): self
    {
        if ($exponent instanceof self) {
            $exponent = $exponent->value;
        }

        if ($exponent < 0) {
            return new self(0);
        }

        if ($exponent === 0) {
            return new self(1);
        }

        return new self($this->value ** $exponent);
    }

    /**
     * Checks if this integer is positive (greater than 0).
     *
     * @return bool True if the integer is positive
     */
    public function isPositive(): bool
    {
        return $this->value > 0;
    }

    /**
     * Checks if this integer is negative (less than 0).
     *
     * @return bool True if the integer is negative
     */
    public function isNegative(): bool
    {
        return $this->value < 0;
    }

    /**
     * Adds another integer to this one and returns the result.
     *
     * @param int|self $other The value to add
     * @return self A new Integer with the sum
     */
    public function add(int | self $other): self
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        return new self((int) ($this->value + $other));
    }

    /**
     * Subtracts another integer from this one and returns the result.
     *
     * @param int|self $other The value to subtract
     * @return self A new Integer with the difference
     */
    public function sub(int | self $other): self
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        return new self((int) ($this->value - $other));
    }

    /**
     * Multiplies this integer by another one and returns the result.
     *
     * @param int|self $other The value to multiply by
     * @return self A new Integer with the product
     */
    public function mul(int | self $other): self
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        return new self((int) ($this->value * $other));
    }

    /**
     * Divides this integer by another one and returns the result.
     * Returns an error if the divisor is zero.
     *
     * @param positive-int|self $other The divisor
     * @return Result<self, DivisionByZero> A Result containing either the quotient or an error
     */
    public function div(int | self $other): Result
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        if ($other === 0) {
            /** @var Result<self, DivisionByZero> */
            return Result::err(new DivisionByZero());
        }

        /** @var Result<self, DivisionByZero> */
        return Result::ok(new self((int) ($this->value / $other)));
    }

    /**
     * Divides this integer by another one and returns the floor of the result.
     * Returns an error if the divisor is zero.
     *
     * @param positive-int|self $other The divisor
     * @return Result<self, DivisionByZero> A Result containing either the floor quotient or an error
     */
    public function divFloor(int | self $other): Result
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        if ($other === 0) {
            /** @var Result<self, DivisionByZero> */
            return Result::err(new DivisionByZero());
        }

        /** @var Result<self,DivisionByZero> */
        return Result::ok(new self((int) (\floor($this->value / $other))));
    }

    /**
     * Divides this integer by another one and returns the ceiling of the result.
     * Returns an error if the divisor is zero.
     *
     * @param positive-int|self $other The divisor
     * @return Result<self, DivisionByZero> A Result containing either the ceiling quotient or an error
     */
    public function divCeil(int | self $other): Result
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        if ($other === 0) {
            /** @var Result<self, DivisionByZero> */
            return Result::err(new DivisionByZero());
        }

        /** @var Result<self, DivisionByZero> */
        return Result::ok(new self((int) (\ceil($this->value / $other))));
    }

    /**
     * Adds another integer, checking for overflow.
     * Returns an error on overflow.
     *
     * @param int|self $other The value to add
     * @return Result<self, IntegerOverflow> A Result containing either the sum or an overflow error
     */
    public function overflowingAdd(int | self $other): Result
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        $sum = $this->value + $other;

        if (\is_float($sum)) {
            /** @var Result<self, IntegerOverflow> */
            return Result::err(new IntegerOverflow());
        }

        /** @var Result<self, IntegerOverflow> */
        return Result::ok(new self($sum));
    }

    /**
     * Subtracts another integer, checking for overflow.
     * Returns an error on overflow.
     *
     * @param int|self $other The value to subtract
     * @return Result<self, IntegerOverflow> A Result containing either the difference or an overflow error
     */
    public function overflowingSub(int | self $other): Result
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        $diff = $this->value - $other;

        if (\is_float($diff)) {
            /** @var Result<self, IntegerOverflow> */
            return Result::err(new IntegerOverflow());
        }

        /** @var Result<self, IntegerOverflow> */
        return Result::ok(new self($diff));
    }

    /**
     * Multiplies by another integer, checking for overflow.
     * Returns an error on overflow.
     *
     * @param int|self $other The value to multiply by
     * @return Result<self, IntegerOverflow> A Result containing either the product or an overflow error
     */
    public function overflowingMul(int | self $other): Result
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        $product = $this->value * $other;

        if (\is_float($product)) {
            /** @var Result<self, IntegerOverflow> */
            return Result::err(new IntegerOverflow());
        }

        /** @var Result<self, IntegerOverflow> */
        return Result::ok(new self($product));
    }

    /**
     * Divides by another integer, checking for overflow.
     * Returns an error on overflow or if the divisor is zero.
     *
     * @param positive-int|self $other The divisor
     * @return Result<self, IntegerOverflow|DivisionByZero> A Result containing either the quotient or an error
     */
    public function overflowingDiv(int | self $other): Result
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        if ($other === 0) {
            /** @var Result<self, IntegerOverflow|DivisionByZero> */
            return Result::err(new DivisionByZero());
        }

        $quotient = $this->value / $other;

        if ($quotient > \PHP_INT_MAX || $quotient < \PHP_INT_MIN) {
            /** @var Result<self, IntegerOverflow|DivisionByZero> */
            return Result::err(new IntegerOverflow());
        }

        /** @var Result<self, IntegerOverflow|DivisionByZero> */
        return Result::ok(new self((int) $quotient));
    }

    /**
     * Adds another integer, saturating at the integer bounds.
     *
     * @param int|self $other The value to add
     * @return self A new Integer with the saturated sum
     */
    public function saturatingAdd(int | self $other): self
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        $sum = $this->value + $other;

        if ($sum <= \PHP_INT_MIN) {
            return new self(\PHP_INT_MIN);
        }

        if ($sum >= \PHP_INT_MAX) {
            return new self(\PHP_INT_MAX);
        }

        return new self($sum);
    }

    /**
     * Subtracts another integer, saturating at the integer bounds.
     *
     * @param int|self $other The value to subtract
     * @return self A new Integer with the saturated difference
     */
    public function saturatingSub(int | self $other): self
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        $diff = $this->value - $other;

        if ($diff <= \PHP_INT_MIN) {
            return new self(\PHP_INT_MIN);
        }

        if ($diff >= \PHP_INT_MAX) {
            return new self(\PHP_INT_MAX);
        }

        return new self($diff);
    }

    /**
     * Multiplies by another integer, saturating at the integer bounds.
     *
     * @param int|self $other The value to multiply by
     * @return self A new Integer with the saturated product
     */
    public function saturatingMul(int | self $other): self
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        $product = $this->value * $other;

        if ($product <= \PHP_INT_MIN) {
            return new self(\PHP_INT_MIN);
        }

        if ($product >= \PHP_INT_MAX) {
            return new self(\PHP_INT_MAX);
        }

        return new self($product);
    }

    /**
     * Divides by another integer, saturating at the integer bounds.
     * Returns an error if the divisor is zero.
     *
     * @param positive-int|self $other The divisor
     * @return Result<self, DivisionByZero> A Result containing either the saturated quotient or an error
     */
    public function saturatingDiv(int | self $other): Result
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        if ($other === 0) {
            /** @var Result<self, DivisionByZero> */
            return Result::err(new DivisionByZero());
        }

        $quotient = $this->value / $other;

        if ($quotient <= \PHP_INT_MIN) {
            /** @var Result<self, DivisionByZero> */
            return Result::ok(new self(\PHP_INT_MIN));
        }

        if ($quotient >= \PHP_INT_MAX) {
            /** @var Result<self, DivisionByZero> */
            return Result::ok(new self(\PHP_INT_MAX));
        }

        /** @var Result<self, DivisionByZero> */
        return Result::ok(new self((int) $quotient));
    }

    /**
     * Returns the logarithm of the integer with respect to an arbitrary base (floor value).
     * For values <= 0 or when base <= 0 or base = 1, returns PHP_INT_MIN to indicate an error.
     *
     * @param float $base The base for the logarithm (default: \M_E for natural logarithm)
     * @return self A new Integer with the logarithm result or PHP_INT_MIN for invalid inputs
     */
    public function log(float $base = \M_E): self
    {
        if ($this->value <= 0 || $base <= 0 || \abs($base - 1.0) < 1e-9) {
            return new self(\PHP_INT_MIN);
        }

        return new self((int) (\floor(\log((float) ($this->value), $base))));
    }

    /**
     * Returns the base-2 logarithm of the integer as an integer (floor value).
     * For values <= 0, returns PHP_INT_MIN to indicate an error.
     *
     * @return self A new Integer with the base-2 logarithm result or PHP_INT_MIN for invalid inputs
     */
    public function log2(): self
    {
        if ($this->value <= 0) {
            return new self(\PHP_INT_MIN);
        }

        return new self((int) (\floor(\log((float) ($this->value), 2))));
    }

    /**
     * Returns the base-10 logarithm of the integer as an integer (floor value).
     * For values <= 0, returns PHP_INT_MIN to indicate an error.
     *
     * @return self A new Integer with the base-10 logarithm result or PHP_INT_MIN for invalid inputs
     */
    public function log10(): self
    {
        if ($this->value <= 0) {
            return new self(\PHP_INT_MIN);
        }

        return new self((int) (\floor(\log((float) ($this->value), 10))));
    }

    /**
     * Returns the integer square root (floor value).
     * For negative numbers, returns PHP_INT_MIN to indicate an error.
     *
     * @return self A new Integer with the square root result or PHP_INT_MIN for negative inputs
     */
    public function sqrt(): self
    {
        if ($this->value < 0) {
            return new self(\PHP_INT_MIN);
        }

        return new self((int) (\floor(\sqrt((float) ($this->value)))));
    }

    /**
     * Returns the sign of the integer as -1, 0, or 1.
     *
     * @return self A new Integer with the sign (-1, 0, or 1)
     */
    public function signum(): self
    {
        if ($this->value > 0) {
            return new self(1);
        }

        if ($this->value < 0) {
            return new self(-1);
        }

        return new self(0);
    }

    /**
     * Checks if this integer equals another value.
     *
     * @param int|self $other The value to compare with
     * @return bool True if the integers are equal
     */
    public function eq(int | self $other): bool
    {
        if ($other instanceof self) {
            return $this->value === $other->value;
        }

        return $this->value === $other;
    }

    /**
     * Checks if this integer is greater than another value.
     *
     * @param int|self $other The value to compare with
     * @return bool True if this integer is greater than the other
     */
    public function gt(int | self $other): bool
    {
        if ($other instanceof self) {
            return $this->value > $other->value;
        }

        return $this->value > $other;
    }

    /**
     * Checks if this integer is greater than or equal to another value.
     *
     * @param int|self $other The value to compare with
     * @return bool True if this integer is greater than or equal to the other
     */
    public function ge(int | self $other): bool
    {
        if ($other instanceof self) {
            return $this->value >= $other->value;
        }

        return $this->value >= $other;
    }

    /**
     * Checks if this integer is less than or equal to another value.
     *
     * @param int|self $other The value to compare with
     * @return bool True if this integer is less than or equal to the other
     */
    public function le(int | self $other): bool
    {
        if ($other instanceof self) {
            return $this->value <= $other->value;
        }

        return $this->value <= $other;
    }

    /**
     * Checks if this integer is less than another value.
     *
     * @param int|self $other The value to compare with
     * @return bool True if this integer is less than the other
     */
    public function lt(int | self $other): bool
    {
        if ($other instanceof self) {
            return $this->value < $other->value;
        }

        return $this->value < $other;
    }

    /**
     * Applies a function to the integer value and returns a new Integer.
     *
     * @param callable(int): int $mapper The function to apply
     * @return self A new Integer with the function applied
     */
    public function map(callable $mapper): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self($mapper($this->value));
    }

    /**
     * Converts the Integer to a native PHP integer.
     *
     * @return int The wrapped integer value
     */
    public function toInt(): int
    {
        return $this->value;
    }

    /**
     * Returns the Integer as a native float
     */
    public function toFloat(): float
    {
        return (float) $this->value;
    }

    /**
     * Clamps this integer between min and max values (inclusive).
     *
     * @param int|self $min The minimum value (inclusive)
     * @param int|self $max The maximum value (inclusive)
     * @return self A new Integer clamped between min and max
     */
    public function clamp(int | self $min, int | self $max): self
    {
        if ($min instanceof self) {
            $min = $min->value;
        }

        if ($max instanceof self) {
            $max = $max->value;
        }

        if ($this->value < $min) {
            return new self($min);
        }

        if ($this->value > $max) {
            return new self($max);
        }

        return new self($this->value);
    }

    /**
     * Checks if this integer is a multiple of another integer.
     *
     * @param int|self $other The divisor to check
     * @return bool True if this integer is a multiple of the divisor
     */
    public function isMultipleOf(int | self $other): bool
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        return $this->value % $other === 0;
    }

    /**
     * Checks if this integer is even.
     *
     * @return bool True if the integer is even
     */
    public function isEven(): bool
    {
        return $this->value % 2 === 0;
    }

    /**
     * Checks if this integer is odd.
     *
     * @return bool True if the integer is odd
     */
    public function isOdd(): bool
    {
        return $this->value % 2 !== 0;
    }

    /**
     * Returns the minimum of this integer and another value.
     *
     * @param int|self $other The value to compare with
     * @return self A new Integer with the minimum value
     */
    public function min(int | self $other): self
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        return $this->value < $other ? new self($this->value) : new self($other);
    }

    /**
     * Returns the maximum of this integer and another value.
     *
     * @param int|self $other The value to compare with
     * @return self A new Integer with the maximum value
     */
    public function max(int | self $other): self
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        return $this->value > $other ? new self($this->value) : new self($other);
    }

    /**
     * Compares this integer with another value.
     * Returns -1 if this < other, 0 if this == other, 1 if this > other.
     *
     * @param int|self $other The value to compare with
     * @return self A new Integer containing -1, 0, or 1 as this integer is less than, equal to, or greater than the other
     */
    public function cmp(int | self $other): self
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        return new self($this->value <=> $other);
    }

    /**
     * Performs a bitwise AND operation with another integer.
     *
     * @param int|self $other The value to AND with
     * @return self A new Integer with the result of the AND operation
     */
    public function and(int | self $other): self
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        return new self($this->value & $other);
    }

    /**
     * Performs a bitwise OR operation with another integer.
     *
     * @param int|self $other The value to OR with
     * @return self A new Integer with the result of the OR operation
     */
    public function or(int | self $other): self
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        return new self($this->value | $other);
    }

    /**
     * Performs a bitwise XOR operation with another integer.
     *
     * @param int|self $other The value to XOR with
     * @return self A new Integer with the result of the XOR operation
     */
    public function xor(int | self $other): self
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        return new self($this->value ^ $other);
    }

    /**
     * Performs a bitwise NOT operation on this integer.
     *
     * @return self A new Integer with the result of the NOT operation
     */
    public function not(): self
    {
        return new self(~$this->value);
    }

    /**
     * Performs a left shift operation on this integer.
     *
     * @param int|self $other The number of bits to shift left
     * @return self A new Integer with the result of the left shift operation
     */
    public function leftShift(int | self $other): self
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        return new self($this->value << $other);
    }

    /**
     * Performs a right shift operation on this integer.
     *
     * @param int|self $other The number of bits to shift right
     * @return self A new Integer with the result of the right shift operation
     */
    public function rightShift(int | self $other): self
    {
        if ($other instanceof self) {
            $other = $other->value;
        }

        return new self($this->value >> $other);
    }

    /**
     * Returns the maximum value that can be represented by this integer type.
     *
     * @return self A new Integer with the maximum value
     *
     * @psalm-pure
     */
    public static function maximum(): self
    {
        return self::from(\PHP_INT_MAX);
    }

    /**
     * Returns the minimum value that can be represented by this integer type.
     *
     * @return self A new Integer with the minimum value
     */
    public static function minimum(): self
    {
        return self::from(\PHP_INT_MIN);
    }
}
