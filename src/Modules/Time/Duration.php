<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Time;

use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\DurationCalculationInvalid;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\DurationOverflow;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\ZeroDuration;
use Jsadaa\PhpCoreLibrary\Primitives\Double\Double;

/**
 * An immutable duration type representing a span of time.
 *
 * Duration stores time as a combination of seconds and nanoseconds for high precision
 * time measurements. It provides safe arithmetic operations with overflow checks
 * and various convenience methods for converting between time units.
 *
 * @psalm-immutable
 */
final readonly class Duration
{
    /** @var int Nanoseconds in a microsecond */
    public const NANOS_PER_MICRO = 1_000;

    /** @var int Nanoseconds in a millisecond */
    public const NANOS_PER_MILLI = 1_000_000;

    /** @var int Nanoseconds in a second */
    public const NANOS_PER_SECOND = 1_000_000_000;

    /** @var int Milliseconds in a second */
    public const MILLIS_PER_SECOND = 1_000;

    /** @var int Microseconds in a second */
    public const MICROS_PER_SECOND = 1_000_000;

    /** @var int Seconds in a minute */
    public const SECONDS_PER_MINUTE = 60;

    /** @var int Seconds in an hour */
    public const SECONDS_PER_HOUR = 3_600;

    /** @var int Seconds in a day */
    public const SECONDS_PER_DAY = 86_400;

    /** @var int Seconds in a week */
    public const SECONDS_PER_WEEK = 604_800;

    /** @var int Maximum nanoseconds (less than a second) */
    public const MAX_NANOS = 999_999_999;

    /** @var int The whole seconds component of the duration */
    private int $seconds;

    /** @var int The nanoseconds component of the duration (always 0 <= nanos < 1_000_000_000) */
    private int $nanos;

    /**
     * Creates a new Duration instance from seconds and nanoseconds.
     *
     * @param int $seconds The number of whole seconds
     * @param int $nanos The number of nanoseconds (should be less than 1 billion)
     */
    private function __construct(int $seconds, int $nanos)
    {
        $this->seconds = $seconds;
        $this->nanos = $nanos;
    }

    // --- Factory methods ---

    /**
     * Creates a new Duration from the specified number of seconds and nanoseconds.
     *
     * @param int $seconds The number of whole seconds
     * @param int $nanos The number of nanoseconds (should be less than 1 billion)
     * @return self A new Duration instance
     * @psalm-pure
     */
    public static function new(int $seconds, int $nanos): self
    {
        return new self($seconds, $nanos);
    }

    /**
     * Creates a new Duration from the specified number of milliseconds.
     *
     * @param int $millis The number of milliseconds
     * @return self A new Duration instance
     * @psalm-pure
     */
    public static function fromMillis(int $millis): self
    {
        $secs = \intdiv($millis, self::MILLIS_PER_SECOND);
        $nanos = ($millis - $secs * self::MILLIS_PER_SECOND) * self::NANOS_PER_MILLI;

        return new self($secs, $nanos);
    }

    /**
     * Creates a new Duration from the specified number of microseconds.
     *
     * @param int $micros The number of microseconds
     * @return self A new Duration instance
     * @psalm-pure
     */
    public static function fromMicros(int $micros): self
    {
        $secs = \intdiv($micros, self::MICROS_PER_SECOND);
        $nanos = ($micros - $secs * self::MICROS_PER_SECOND) * self::NANOS_PER_MICRO;

        return new self($secs, $nanos);
    }

    /**
     * Creates a new Duration from the specified number of nanoseconds.
     *
     * @param int $nanos The number of nanoseconds
     * @return self A new Duration instance
     * @psalm-pure
     */
    public static function fromNanos(int $nanos): self
    {
        $secs = \intdiv($nanos, self::NANOS_PER_SECOND);
        $nanosPart = $nanos - $secs * self::NANOS_PER_SECOND;

        return new self($secs, $nanosPart);
    }

    /**
     * Creates a new Duration from the specified number of days.
     *
     * @param int $days The number of days
     * @return self A new Duration instance
     * @psalm-pure
     */
    public static function fromDays(int $days): self
    {
        return new self($days * self::SECONDS_PER_DAY, 0);
    }

    /**
     * Creates a new Duration from the specified number of hours.
     *
     * @param int $hours The number of hours
     * @return self A new Duration instance
     * @psalm-pure
     */
    public static function fromHours(int $hours): self
    {
        return new self($hours * self::SECONDS_PER_HOUR, 0);
    }

    /**
     * Creates a new Duration from the specified number of minutes.
     *
     * @param int $minutes The number of minutes
     * @return self A new Duration instance
     * @psalm-pure
     */
    public static function fromMins(int $minutes): self
    {
        return new self($minutes * self::SECONDS_PER_MINUTE, 0);
    }

    /**
     * Creates a new Duration from the specified number of seconds.
     *
     * @param int $seconds The number of seconds
     * @return self A new Duration instance
     * @psalm-pure
     */
    public static function fromSeconds(int $seconds): self
    {
        return new self($seconds, 0);
    }

    /**
     * Creates a new Duration from the specified number of weeks.
     *
     * @param int $weeks The number of weeks
     * @return self A new Duration instance
     * @psalm-pure
     */
    public static function fromWeeks(int $weeks): self
    {
        return new self($weeks * self::SECONDS_PER_WEEK, 0);
    }

    // --- Conversion ---

    /**
     * Returns the total number of whole seconds contained by this Duration.
     *
     * @return int The total seconds
     */
    public function toSeconds(): int
    {
        return $this->seconds + \intdiv($this->nanos, self::NANOS_PER_SECOND);
    }

    /**
     * Returns the total number of milliseconds contained by this Duration.
     *
     * @return int The total milliseconds
     */
    public function toMillis(): int
    {
        return $this->seconds * self::MILLIS_PER_SECOND + \intdiv($this->nanos, self::NANOS_PER_MILLI);
    }

    /**
     * Returns the total number of microseconds contained by this Duration.
     *
     * @return int The total microseconds
     */
    public function toMicros(): int
    {
        return $this->seconds * self::MICROS_PER_SECOND + \intdiv($this->nanos, self::NANOS_PER_MICRO);
    }

    /**
     * Returns the total number of nanoseconds contained by this Duration.
     *
     * @return int The total nanoseconds
     */
    public function toNanos(): int
    {
        return $this->seconds * self::NANOS_PER_SECOND + $this->nanos;
    }

    /**
     * Returns the fractional part of this Duration, in whole microseconds.
     *
     * This method does **not** return the length of the duration when
     * represented by microseconds. The returned number always represents a
     * fractional portion of a second (i.e., it is less than one million).
     *
     * @return int The microseconds part (always less than 1 million)
     */
    public function subsecMicros(): int
    {
        return \intdiv($this->nanos, self::NANOS_PER_MICRO);
    }

    /**
     * Returns the fractional part of this Duration, in whole milliseconds.
     *
     * This method does **not** return the length of the duration when
     * represented by milliseconds. The returned number always represents a
     * fractional portion of a second (i.e., it is less than one thousand).
     *
     * @return int The milliseconds part (always less than 1 thousand)
     */
    public function subsecMillis(): int
    {
        return \intdiv($this->nanos, self::NANOS_PER_MILLI);
    }

    /**
     * Returns the fractional part of this Duration, in nanoseconds.
     *
     * This method does **not** return the length of the duration when
     * represented by nanoseconds. The returned number always represents a
     * fractional portion of a second (i.e., it is less than one billion).
     *
     * @return int The nanoseconds part (always less than 1 billion)
     */
    public function subsecNanos(): int
    {
        return $this->nanos;
    }

    // --- Arithmetic ---

    /**
     * Checked Duration addition. Computes `self + other`, returning an error if overflow occurred.
     *
     * @param self $other The duration to add
     * @return Result<self, DurationOverflow> The result of the addition or an error if overflow occurred
     */
    public function add(self $other): Result
    {
        $secsResult = self::checkedAdd($this->seconds, $other->seconds);

        if ($secsResult->isErr()) {
            /** @var Result<self, DurationOverflow> */
            return Result::err(new DurationOverflow('Overflow occurred while adding seconds'));
        }

        $secs = $secsResult->unwrap();
        $nanos = $this->nanos + $other->nanos;

        // Handle overflow of nanoseconds to seconds
        if ($nanos >= self::NANOS_PER_SECOND) {
            $nanos -= self::NANOS_PER_SECOND;
            $secsResult = self::checkedAdd($secs, 1);

            if ($secsResult->isErr()) {
                /** @var Result<self, DurationOverflow> */
                return Result::err(new DurationOverflow('Overflow occurred while adding seconds'));
            }

            $secs = $secsResult->unwrap();
        }

        /** @var Result<self, DurationOverflow> */
        return Result::ok(new self($secs, $nanos));
    }

    /**
     * Saturating Duration addition. Computes `self + other`, returning `Duration::maximum()`
     * if overflow occurred.
     *
     * @param self $other The duration to add
     * @return self A new Duration representing the sum or maximum duration if overflow
     */
    public function saturatingAdd(self $other): self
    {
        $result = $this->add($other);

        if ($result->isErr()) {
            return self::maximum();
        }

        return $result->unwrap();
    }

    /**
     * Checked Duration subtraction. Computes `self - other`, returning an error if the result would be negative or if overflow occurred.
     *
     * @param self $other The duration to subtract
     * @return Result<self, DurationOverflow> The result of the subtraction or an error
     */
    public function sub(self $other): Result
    {
        if ($this->seconds < $other->seconds ||
            ($this->seconds === $other->seconds && $this->nanos < $other->nanos)) {
            /** @var Result<self, DurationOverflow> */
            return Result::err(new DurationOverflow('Subtraction would result in negative duration'));
        }

        $secsResult = self::checkedSub($this->seconds, $other->seconds);

        if ($secsResult->isErr()) {
            /** @var Result<self, DurationOverflow> */
            return Result::err(new DurationOverflow('Overflow occurred while subtracting durations'));
        }

        $secs = $secsResult->unwrap();

        if ($this->nanos >= $other->nanos) {
            $nanos = $this->nanos - $other->nanos;
        } else {
            // Need to borrow a second
            $secsResult = self::checkedSub($secs, 1);

            if ($secsResult->isErr()) {
                /** @var Result<self, DurationOverflow> */
                return Result::err(new DurationOverflow('Overflow occurred while subtracting durations'));
            }

            $secs = $secsResult->unwrap();
            $nanos = $this->nanos + self::NANOS_PER_SECOND - $other->nanos;
        }

        /** @var Result<self, DurationOverflow> */
        return Result::ok(new self($secs, $nanos));
    }

    /**
     * Saturating Duration subtraction. Computes `self - other`, returning `Duration::zero()`
     * if the result would be negative or if overflow occurred.
     *
     * @param self $other The duration to subtract
     * @return self A new Duration representing the difference or zero if result would be negative
     */
    public function saturatingSub(self $other): self
    {
        $result = $this->sub($other);

        if ($result->isErr()) {
            return self::zero();
        }

        return $result->unwrap();
    }

    /**
     * Checked Duration multiplication. Computes `self * other`, returning an error if overflow occurred.
     *
     * @param int $other The value to multiply by
     * @return Result<self, DurationOverflow> The result of the multiplication or an error if overflow occurred.
     */
    public function mul(int $other): Result
    {
        // Convert nanoseconds to a larger number to avoid overflow
        $totalNanos = $this->nanos * $other;
        $extraSecs = \intdiv($totalNanos, self::NANOS_PER_SECOND);
        $nanosResult = $totalNanos - $extraSecs * self::NANOS_PER_SECOND;

        // Multiply seconds and add extra seconds from nanoseconds
        $secsResult = self::checkedMul($this->seconds, $other);

        if ($secsResult->isErr()) {
            /** @var Result<self, DurationOverflow> */
            return Result::err(new DurationOverflow('Overflow occurred while multiplying seconds'));
        }

        $secondsPlusExtra = self::checkedAdd($secsResult->unwrap(), $extraSecs);

        if ($secondsPlusExtra->isErr()) {
            /** @var Result<self, DurationOverflow> */
            return Result::err(new DurationOverflow('Overflow occurred while adding extra seconds'));
        }

        /** @var Result<self, DurationOverflow> */
        return Result::ok(new self($secondsPlusExtra->unwrap(), $nanosResult));
    }

    /**
     * Saturating Duration multiplication. Computes `self * other`, returning
     * `Duration::max()` if overflow occurred.
     *
     * @param int $rhs The value to multiply by
     * @return self A new Duration representing the product or max duration if overflow
     */
    public function saturatingMul(int $rhs): self
    {
        $result = $this->mul($rhs);

        if ($result->isErr()) {
            return self::maximum();
        }

        return $result->unwrap();
    }

    /**
     * Checked Duration division. Computes `self / rhs`, returning an error
     * if `rhs == 0` or if the operation would overflow.
     *
     * @param positive-int $rhs The divisor
     * @return Result<self, ZeroDuration> The result of the division or an error
     */
    public function div(int $rhs): Result
    {
        /** @psalm-suppress DocblockTypeContradiction Runtime guard for non-positive values */
        if ($rhs <= 0) {
            /** @var Result<self, ZeroDuration> */
            return Result::err(new ZeroDuration('Division by zero'));
        }

        $secs = \intdiv($this->seconds, $rhs);
        $extraSecs = $this->seconds - $secs * $rhs;

        // Convert remaining seconds to nanoseconds and add to existing nanoseconds
        $extraNanos = \intdiv($extraSecs * self::NANOS_PER_SECOND, $rhs);
        $nanos = \intdiv($this->nanos, $rhs) + $extraNanos;

        /** @var Result<self, ZeroDuration> */
        return Result::ok(new self($secs, $nanos));
    }

    /**
     * Divides this duration by another duration, returning a scalar.
     *
     * This represents how many times the `other` duration fits within the current duration.
     * The result is a floating-point number that can represent fractional relationships.
     *
     * @param self $other The duration to divide by
     * @return Result<Double, ZeroDuration|DurationCalculationInvalid> A scalar representing the quotient
     */
    public function divDuration(self $other): Result
    {
        if ($other->isZero()) {
            /** @var Result<Double, ZeroDuration|DurationCalculationInvalid> */
            return Result::err(new ZeroDuration('Cannot divide by a zero duration'));
        }

        if ($this->isZero()) {
            /** @var Result<Double, ZeroDuration|DurationCalculationInvalid> */
            return Result::ok(Double::of(0.0));
        }

        // Handle cases where overflow could occur with large numbers
        if ($this->seconds === \PHP_INT_MAX && $other->seconds === 1 && $other->nanos === 0) {
            /** @var Result<Double, ZeroDuration|DurationCalculationInvalid> */
            return Result::ok(Double::of((float)$this->seconds));
        }

        // Convert to total nanoseconds as float for calculation
        $selfSecondsFloat = (float)$this->seconds;
        $selfNanosFloat = (float)$this->nanos;
        $otherSecondsFloat = (float)$other->seconds;
        $otherNanosFloat = (float)$other->nanos;

        // For extremely large values, use a different approach to avoid floating point overflow
        if ($selfSecondsFloat > \PHP_FLOAT_MAX / (float)self::NANOS_PER_SECOND ||
            $otherSecondsFloat > \PHP_FLOAT_MAX / (float)self::NANOS_PER_SECOND) {
            $secRatio = (float)$this->seconds / (float)$other->seconds;
            $nanoAdjustment = 0.0;

            if ($other->seconds > 0) {
                $nanoAdjustment = ((float)$this->nanos - ($secRatio * (float)$other->nanos))
                                / ((float)$other->seconds * (float)self::NANOS_PER_SECOND);
            }
            $quotient = $secRatio + $nanoAdjustment;
        } else {
            $selfTotal = ($selfSecondsFloat * (float)self::NANOS_PER_SECOND) + $selfNanosFloat;
            $otherTotal = ($otherSecondsFloat * (float)self::NANOS_PER_SECOND) + $otherNanosFloat;
            $quotient = $selfTotal / $otherTotal;
        }

        if (!\is_finite($quotient)) {
            /** @var Result<Double, ZeroDuration|DurationCalculationInvalid> */
            return Result::err(new DurationCalculationInvalid('Division resulted in an invalid value (infinity or NaN)'));
        }

        /** @var Result<Double, ZeroDuration|DurationCalculationInvalid> */
        return Result::ok(Double::of($quotient));
    }

    /**
     * Computes the absolute difference between two durations.
     *
     * @param self $other The other duration to compare with
     * @return self A new Duration representing the absolute difference
     */
    public function absDiff(self $other): self
    {
        $seconds = \abs($this->seconds - $other->seconds);
        $nanos = \abs($this->nanos - $other->nanos);

        return new self($seconds, $nanos);
    }

    // --- Comparisons ---

    /**
     * Compares two Durations.
     *
     * @param self $other The Duration to compare with.
     * @return int -1, 0, or 1
     */
    public function cmp(self $other): int
    {
        return $this->seconds <=> $other->seconds ?: $this->nanos <=> $other->nanos;
    }

    /**
     * Returns the maximum of two Durations.
     *
     * @param self $other The Duration to compare with.
     * @return self The maximum Duration.
     */
    public function max(self $other): self
    {
        return $this->cmp($other) >= 0 ? new self($this->seconds, $this->nanos) : new self($other->seconds, $other->nanos);
    }

    /**
     * Returns the minimum of two Durations.
     *
     * @param self $other The Duration to compare with.
     * @return self The minimum Duration.
     */
    public function min(self $other): self
    {
        return $this->cmp($other) <= 0 ? new self($this->seconds, $this->nanos) : new self($other->seconds, $other->nanos);
    }

    /**
     * Clamps this Duration between a minimum and a maximum.
     *
     * @param self $min The minimum Duration.
     * @param self $max The maximum Duration.
     * @return self The clamped Duration.
     */
    public function clamp(self $min, self $max): self
    {
        return $this->max($min)->min($max);
    }

    /**
     * Checks if this Duration is equal to another Duration.
     *
     * @param self $other The Duration to compare with.
     * @return bool True if the Durations are equal, false otherwise.
     */
    public function eq(self $other): bool
    {
        return $this->seconds === $other->seconds && $this->nanos === $other->nanos;
    }

    /**
     * Checks if this Duration is not equal to another Duration.
     *
     * @param self $other The Duration to compare with.
     * @return bool True if the Durations are not equal, false otherwise.
     */
    public function ne(self $other): bool
    {
        return !$this->eq($other);
    }

    /**
     * Checks if this Duration is less than another Duration.
     *
     * @param self $other The Duration to compare with.
     * @return bool True if this Duration is less than the other Duration, false otherwise.
     */
    public function lt(self $other): bool
    {
        return $this->seconds < $other->seconds || ($this->seconds === $other->seconds && $this->nanos < $other->nanos);
    }

    /**
     * Checks if this Duration is less than or equal to another Duration.
     *
     * @param self $other The Duration to compare with.
     * @return bool True if this Duration is less than or equal to the other Duration, false otherwise.
     */
    public function le(self $other): bool
    {
        return $this->seconds < $other->seconds || ($this->seconds === $other->seconds && $this->nanos <= $other->nanos);
    }

    /**
     * Checks if this Duration is greater than another Duration.
     *
     * @param self $other The Duration to compare with.
     * @return bool True if this Duration is greater than the other Duration, false otherwise.
     */
    public function gt(self $other): bool
    {
        return $this->seconds > $other->seconds || ($this->seconds === $other->seconds && $this->nanos > $other->nanos);
    }

    /**
     * Checks if this Duration is greater than or equal to another Duration.
     *
     * @param self $other The Duration to compare with.
     * @return bool True if this Duration is greater than or equal to the other Duration, false otherwise.
     */
    public function ge(self $other): bool
    {
        return $this->seconds > $other->seconds || ($this->seconds === $other->seconds && $this->nanos >= $other->nanos);
    }

    // --- Constants & predicates ---

    /**
     * Returns true if this Duration spans no time.
     *
     * @return bool True if the duration is zero
     */
    public function isZero(): bool
    {
        return $this->seconds === 0 && $this->nanos === 0;
    }

    /**
     * Creates a duration of zero time.
     *
     * @return self A Duration with value of zero
     * @psalm-pure
     */
    public static function zero(): self
    {
        return new self(0, 0);
    }

    /**
     * Returns the maximum possible Duration value.
     *
     * @return self A Duration representing the maximum possible value
     * @psalm-pure
     */
    public static function maximum(): self
    {
        return new self(\PHP_INT_MAX, self::MAX_NANOS);
    }

    /**
     * Creates a Duration representing one microsecond.
     *
     * @return self A Duration of one microsecond
     * @psalm-pure
     */
    public static function microsecond(): self
    {
        return new self(0, self::NANOS_PER_MICRO);
    }

    /**
     * Creates a Duration representing one nanosecond.
     *
     * @return self A Duration of one nanosecond
     * @psalm-pure
     */
    public static function nanosecond(): self
    {
        return new self(0, 1);
    }

    /**
     * Creates a Duration representing one millisecond.
     *
     * @return self A Duration of one millisecond
     * @psalm-pure
     */
    public static function millisecond(): self
    {
        return new self(0, self::NANOS_PER_MILLI);
    }

    /**
     * Creates a Duration representing one second.
     *
     * @return self A Duration of one second
     * @psalm-pure
     */
    public static function second(): self
    {
        return new self(1, 0);
    }

    // --- Overflow helpers ---

    /**
     * @return Result<int, DurationOverflow>
     * @psalm-pure
     * @psalm-suppress ImpureMethodCall
     */
    private static function checkedAdd(int $a, int $b): Result
    {
        if (($b > 0 && $a > \PHP_INT_MAX - $b) || ($b < 0 && $a < \PHP_INT_MIN - $b)) {
            /** @var Result<int, DurationOverflow> */
            return Result::err(new DurationOverflow('Overflow on addition'));
        }

        /** @var Result<int, DurationOverflow> */
        return Result::ok($a + $b);
    }

    /**
     * @return Result<int, DurationOverflow>
     * @psalm-pure
     * @psalm-suppress ImpureMethodCall
     */
    private static function checkedSub(int $a, int $b): Result
    {
        if (($b > 0 && $a < \PHP_INT_MIN + $b) || ($b < 0 && $a > \PHP_INT_MAX + $b)) {
            /** @var Result<int, DurationOverflow> */
            return Result::err(new DurationOverflow('Overflow on subtraction'));
        }

        /** @var Result<int, DurationOverflow> */
        return Result::ok($a - $b);
    }

    /**
     * @return Result<int, DurationOverflow>
     * @psalm-pure
     * @psalm-suppress ImpureMethodCall
     */
    private static function checkedMul(int $a, int $b): Result
    {
        $result = $a * $b;

        /** @psalm-suppress TypeDoesNotContainType PHP promotes int*int to float on overflow */
        if (\is_float($result)) {
            /** @var Result<int, DurationOverflow> */
            return Result::err(new DurationOverflow('Overflow on multiplication'));
        }

        /** @var Result<int, DurationOverflow> */
        return Result::ok($result);
    }
}
