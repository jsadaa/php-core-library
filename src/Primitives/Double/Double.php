<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Primitives\Double;

use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Primitives\Double\Error\DivisionByZero;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;

/**
 * Double represents a floating-point number with immutable operations.
 *
 * @psalm-immutable
 */
final readonly class Double
{
    private float $value;

    private function __construct(float $value)
    {
        $this->value = $value;
    }

    /**
     * Creates a new Double from a value
     *
     * @param float|int|Double $value A numeric value or another Double instance
     *
     * @psalm-pure
     */
    public static function of(float | int | self $value): self
    {
        if ($value instanceof self) {
            return new self($value->value);
        }

        return new self((float) $value);
    }

    /**
     * Returns the absolute value
     *
     */
    public function abs(): self
    {
        return new self(\abs($this->value));
    }

    /**
     * Returns the absolute difference between this Double and another value
     *
     * @param float|int|Double $other The other value
     */
    public function absDiff(float | int | self $other): self
    {
        $otherValue = $other instanceof self ? $other->value : (float) $other;

        return new self(\abs($this->value - $otherValue));
    }

    /**
     * Returns this Double raised to the power of the exponent
     *
     * @param float|int|Double $exp The exponent
     */
    public function pow(float | int | self $exp): self
    {
        $expValue = $exp instanceof self ? $exp->value : (float) $exp;

        return new self(\pow($this->value, $expValue));
    }

    /**
     * Checks if this Double is positive (> 0)
     *
     */
    public function isPositive(): bool
    {
        return $this->value > 0;
    }

    /**
     * Checks if this Double is negative (< 0)
     *
     */
    public function isNegative(): bool
    {
        return $this->value < 0;
    }

    /**
     * Adds another value to this Double
     *
     * @param float|int|Double $other The value to add
     */
    public function add(float | int | self $other): self
    {
        $otherValue = $other instanceof self ? $other->value : (float) $other;

        return new self($this->value + $otherValue);
    }

    /**
     * Subtracts another value from this Double
     *
     * @param float|int|Double $other The value to subtract
     */
    public function sub(float | int | self $other): self
    {
        $otherValue = $other instanceof self ? $other->value : (float) $other;

        return new self($this->value - $otherValue);
    }

    /**
     * Multiplies this Double by another value
     *
     * @param float|int|Double $other The value to multiply by
     */
    public function mul(float | int | self $other): self
    {
        $otherValue = $other instanceof self ? $other->value : (float) $other;

        return new self($this->value * $otherValue);
    }

    /**
     * Divides this Double by another value
     *
     * @param float|int|Double $other The value to divide by
     * @return Result<Double, DivisionByZero> The division result or an error if dividing by zero
     */
    public function div(float | int | self $other): Result
    {
        $otherValue = $other instanceof self ? $other->value : (float) $other;

        if ($otherValue === 0.0) {
            /** @var Result<Double, DivisionByZero> */
            return Result::err(new DivisionByZero());
        }

        /** @var Result<Double, DivisionByZero> */
        return Result::ok(new self($this->value / $otherValue));
    }

    /**
     * Returns a new Double with the value handled by the mapping function
     *
     * @param callable(float): float $fn The mapping function
     */
    public function map(callable $fn): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self($fn($this->value));
    }

    /**
     * Returns the Double as a native float
     *
     */
    public function toFloat(): float
    {
        return $this->value;
    }

    /**
     * Returns the Double as a native int (truncated)
     *
     */
    public function toInt(): int
    {
        return (int) $this->value;
    }

    /**
     * Clamps this Double between min and max values
     *
     * @param float|int|Double $min The minimum allowed value
     * @param float|int|Double $max The maximum allowed value
     */
    public function clamp(float | int | self $min, float | int | self $max): self
    {
        $minValue = $min instanceof self ? $min->value : (float) $min;
        $maxValue = $max instanceof self ? $max->value : (float) $max;

        if ($minValue > $maxValue) {
            // Swap them to ensure valid range
            $temp = $minValue;
            $minValue = $maxValue;
            $maxValue = $temp;
        }

        if ($this->value < $minValue) {
            return new self($minValue);
        }

        if ($this->value > $maxValue) {
            return new self($maxValue);
        }

        return new self($this->value);
    }

    /**
     * Checks if this Double is equal to another value
     *
     * @param float|int|Double $other The value to compare with
     */
    public function eq(float | int | self $other): bool
    {
        $otherValue = $other instanceof self ? $other->value : (float) $other;

        return $this->value === $otherValue;
    }

    /**
     * Checks if this Double is greater than another value
     *
     * @param float|int|Double $other The value to compare with
     */
    public function gt(float | int | self $other): bool
    {
        $otherValue = $other instanceof self ? $other->value : (float) $other;

        return $this->value > $otherValue;
    }

    /**
     * Checks if this Double is greater than or equal to another value
     *
     * @param float|int|Double $other The value to compare with
     */
    public function ge(float | int | self $other): bool
    {
        $otherValue = $other instanceof self ? $other->value : (float) $other;

        return $this->value >= $otherValue;
    }

    /**
     * Checks if this Double is less than or equal to another value
     *
     * @param float|int|Double $other The value to compare with
     */
    public function le(float | int | self $other): bool
    {
        $otherValue = $other instanceof self ? $other->value : (float) $other;

        return $this->value <= $otherValue;
    }

    /**
     * Checks if this Double is less than another value
     *
     * @param float|int|Double $other The value to compare with
     */
    public function lt(float | int | self $other): bool
    {
        $otherValue = $other instanceof self ? $other->value : (float) $other;

        return $this->value < $otherValue;
    }

    /**
     * Compares this Double with another value
     * Returns -1 if this < other, 0 if this == other, 1 if this > other
     *
     * @param float|int|Double $other The value to compare with
     */
    public function cmp(float | int | self $other): Integer
    {
        $otherValue = $other instanceof self ? $other->value : (float) $other;

        if ($this->value < $otherValue) {
            return Integer::of(-1);
        }

        if ($this->value > $otherValue) {
            return Integer::of(1);
        }

        return Integer::of(0);
    }

    /**
     * Returns the smaller of two Doubles
     *
     * @param float|int|Double $other The value to compare with
     */
    public function min(float | int | self $other): self
    {
        $otherValue = $other instanceof self ? $other->value : (float) $other;

        return new self(\min($this->value, $otherValue));
    }

    /**
     * Returns the larger of two Doubles
     *
     * @param float|int|Double $other The value to compare with
     */
    public function max(float | int | self $other): self
    {
        $otherValue = $other instanceof self ? $other->value : (float) $other;

        return new self(\max($this->value, $otherValue));
    }

    /**
     * Returns the sign of this Double
     * -1 if negative, 0 if zero, 1 if positive
     *
     */
    public function signum(): Integer
    {
        if ($this->value < 0) {
            return Integer::of(-1);
        }

        if ($this->value > 0) {
            return Integer::of(1);
        }

        return Integer::of(0);
    }

    /**
     * Returns the natural logarithm (base e) of this Double
     *
     */
    public function ln(): self
    {
        if ($this->value <= 0) {
            return self::nan();
        }

        return new self(\log($this->value));
    }

    /**
     * Calculates the logarithm of the number with respect to an arbitrary base.
     *
     * @param float|Double $base The base for the logarithm
     */
    public function log(float | self $base): self
    {
        if ($base instanceof self) {
            $base = $base->value;
        }

        if ($this->value <= 0) {
            return self::nan();
        }

        if ($base <= 0 || $base === 1.0) {
            return self::nan();
        }

        return new self(\log($this->value, $base));
    }

    /**
     * Returns the logarithm of this Double with base 2
     *
     */
    public function log2(): self
    {
        if ($this->value <= 0) {
            return self::nan();
        }

        return new self(\log($this->value, 2));
    }

    /**
     * Returns the logarithm of this Double with base 10
     *
     */
    public function log10(): self
    {
        if ($this->value <= 0) {
            return self::nan();
        }

        return new self(\log10($this->value));
    }

    /**
     * Returns the square root of this Double
     *
     */
    public function sqrt(): self
    {
        if ($this->value < 0) {
            return self::nan();
        }

        return new self(\sqrt($this->value));
    }

    /**
     * Returns the cubic root (cbrt) of this Double
     *
     */
    public function cbrt(): self
    {
        return new self(($this->value < 0 ? -1.0 : 1.0) * \pow(\abs($this->value), 1.0/3.0));
    }

    /**
     * Returns e (Euler's number) raised to the power of this Double
     *
     */
    public function exp(): self
    {
        return new self(\exp($this->value));
    }

    /**
     * Returns the sine of this Double (in radians)
     *
     */
    public function sin(): self
    {
        return new self(\sin($this->value));
    }

    /**
     * Returns the cosine of this Double (in radians)
     *
     */
    public function cos(): self
    {
        return new self(\cos($this->value));
    }

    /**
     * Returns the tangent of this Double (in radians)
     *
     */
    public function tan(): self
    {
        return new self(\tan($this->value));
    }

    /**
     * Returns the arcsine of this Double
     *
     */
    public function asin(): self
    {
        if ($this->value < -1 || $this->value > 1) {
            return self::nan();
        }

        return new self(\asin($this->value));
    }

    /**
     * Returns the arccosine of this Double
     *
     */
    public function acos(): self
    {
        if ($this->value < -1 || $this->value > 1) {
            return self::nan();
        }

        return new self(\acos($this->value));
    }

    /**
     * Returns the arctangent of this Double
     *
     */
    public function atan(): self
    {
        return new self(\atan($this->value));
    }

    /**
     * Returns the arctangent of y/x using the signs of the arguments to determine the quadrant
     *
     * @param float|int|Double $x The x coordinate
     */
    public function atan2(float | int | self $x): self
    {
        $xValue = $x instanceof self ? $x->value : (float) $x;

        return new self(\atan2($this->value, $xValue));
    }

    /**
     * Hyperbolic sine of this Double
     *
     */
    public function sinh(): self
    {
        return new self(\sinh($this->value));
    }

    /**
     * Hyperbolic cosine of this Double
     *
     */
    public function cosh(): self
    {
        return new self(\cosh($this->value));
    }

    /**
     * Hyperbolic tangent of this Double
     *
     */
    public function tanh(): self
    {
        return new self(\tanh($this->value));
    }

    /**
     * Returns the value rounded to the nearest integer
     *
     */
    public function round(): Integer
    {
        return Integer::of((int)\round($this->value));
    }

    /**
     * Returns the largest integer less than or equal to this Double
     *
     */
    public function floor(): Integer
    {
        return Integer::of((int)\floor($this->value));
    }

    /**
     * Returns the smallest integer greater than or equal to this Double
     *
     */
    public function ceil(): Integer
    {
        return Integer::of((int)\ceil($this->value));
    }

    /**
     * Returns the integer part of this Double by truncating
     *
     */
    public function trunc(): Integer
    {
        return Integer::of((int) $this->value);
    }

    /**
     * Returns the fractional part of this Double
     *
     */
    public function fract(): self
    {
        return new self($this->value - \floor($this->value));
    }

    /**
     * Checks if this Double is finite (not NaN and not infinite)
     *
     */
    public function isFinite(): bool
    {
        return \is_finite($this->value);
    }

    /**
     * Checks if this Double is infinite
     *
     */
    public function isInfinite(): bool
    {
        return \is_infinite($this->value);
    }

    /**
     * Checks if this Double is NaN (not a number)
     *
     */
    public function isNan(): bool
    {
        return \is_nan($this->value);
    }

    /**
     * Checks if this Double is an integer (has no fractional part)
     *
     */
    public function isInteger(): bool
    {
        return $this->value === (float) (int) $this->value;
    }

    /**
     * Checks if this Double is approximately equal to another value within epsilon
     *
     * @param float|int|Double $other The value to compare with
     * @param float|Double $epsilon The maximum allowed difference
     */
    public function approxEq(float | int | self $other, float | self $epsilon = 1.0e-9): bool
    {
        $otherValue = $other instanceof self ? $other->value : (float) $other;
        $epsilonValue = $epsilon instanceof self ? $epsilon->value : $epsilon;

        return \abs($this->value - $otherValue) <= $epsilonValue;
    }

    /**
     * Divides two Doubles and returns the remainder
     *
     * @param float|int|Double $other The divisor
     */
    public function rem(float | int | self $other): self
    {
        $otherValue = $other instanceof self ? $other->value : (float) $other;

        if ($otherValue === 0.0) {
            return self::nan();
        }

        return new self(\fmod($this->value, $otherValue));
    }

    /**
     * Creates a new Double representing PI (π)
     *
     */
    public static function pi(): self
    {
        return new self(\M_PI);
    }

    /**
     * Creates a new Double representing E (Euler's number)
     *
     */
    public static function e(): self
    {
        return new self(\M_E);
    }

    /**
     * Creates a new Double representing positive infinity
     *
     */
    public static function infinity(): self
    {
        return new self(\INF);
    }

    /**
     * Creates a new Double representing negative infinity
     *
     */
    public static function negInfinity(): self
    {
        return new self(-\INF);
    }

    /**
     * Creates a new Double representing NaN (Not a Number)
     *
     * @psalm-pure
     */
    public static function nan(): self
    {
        return new self(\NAN);
    }

    /**
     * Converts degrees to radians
     *
     */
    public function toRadians(): self
    {
        return new self($this->value * \M_PI / 180.0);
    }

    /**
     * Converts radians to degrees
     *
     */
    public function toDegrees(): self
    {
        return new self($this->value * 180.0 / \M_PI);
    }
}
