<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Time;

use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\DurationCalculationInvalid;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\DurationOverflow;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\ZeroDuration;
use Jsadaa\PhpCoreLibrary\Primitives\Double\Double;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;

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

    /** @var Integer The whole seconds component of the duration */
    private Integer $seconds;

    /** @var Integer The nanoseconds component of the duration (always 0 <= nanos < 1_000_000_000) */
    private Integer $nanos;

    /**
     * Creates a new Duration instance from seconds and nanoseconds.
     *
     * @param int|Integer $seconds The number of whole seconds
     * @param int|Integer $nanos The number of nanoseconds (should be less than 1 billion)
     */
    private function __construct(int | Integer $seconds, int | Integer $nanos)
    {
        $this->seconds = $seconds instanceof Integer ? $seconds : Integer::from($seconds);
        $this->nanos = $nanos instanceof Integer ? $nanos : Integer::from($nanos);
    }

    /**
     * Creates a new Duration from the specified number of seconds and nanoseconds.
     *
     * @param int|Integer $seconds The number of whole seconds
     * @param int|Integer $nanos The number of nanoseconds (should be less than 1 billion)
     * @return self A new Duration instance
     * @psalm-pure
     */
    public static function new(int | Integer $seconds, int | Integer $nanos): self
    {
        $seconds = $seconds instanceof Integer ? $seconds : Integer::from($seconds);
        $nanos = $nanos instanceof Integer ? $nanos : Integer::from($nanos);

        return new self($seconds, $nanos);
    }

    /**
     * Computes the absolute difference between two durations.
     *
     * @param self $other The other duration to compare with
     * @return self A new Duration representing the absolute difference
     */
    public function absDiff(self $other): self
    {
        $seconds = $this->seconds->absDiff($other->seconds);
        $nanos = $this->nanos->absDiff($other->nanos);

        return self::new($seconds, $nanos);
    }

    /**
     * Returns the total number of microseconds contained by this Duration.
     *
     * @return Integer The total microseconds (may exceed u64::MAX)
     */
    public function toMicros(): Integer
    {
        return $this
            ->seconds
            ->mul(self::MICROS_PER_SECOND)
            ->add(
                $this
                    ->nanos
                    ->div(self::NANOS_PER_MICRO)
                    ->unwrap(),
            );
    }

    /**
     * Returns the total number of milliseconds contained by this Duration.
     *
     * @return Integer The total milliseconds (may exceed u64::MAX)
     */
    public function toMillis(): Integer
    {
        return $this
            ->seconds
            ->mul(self::MILLIS_PER_SECOND)
            ->add(
                $this
                    ->nanos
                    ->div(self::NANOS_PER_MILLI)
                    ->unwrap(),
            );
    }

    /**
     * Returns the total number of nanoseconds contained by this Duration.
     *
     * @return Integer The total nanoseconds (may exceed u64::MAX)
     */
    public function toNanos(): Integer
    {
        return $this
            ->seconds
            ->mul(self::NANOS_PER_SECOND)
            ->add($this->nanos);
    }

    /**
     * Returns the total number of whole seconds contained by this Duration.
     *
     * The result is rounded towards zero (truncated).
     *
     * @return Integer The total seconds
     */
    public function toSeconds(): Integer
    {
        return $this
            ->seconds
            ->add(
                $this
                    ->nanos
                    ->div(self::NANOS_PER_SECOND)
                    ->unwrap(),
            );
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

        // Protect against potential floating point issues with extreme values
        if ($this->isZero()) {
            /** @var Result<Double, ZeroDuration|DurationCalculationInvalid> */
            return Result::ok(Double::from(0.0));
        }

        // Handle cases where overflow could occur with large numbers
        if ($this->seconds->eq(Integer::maximum()) && $other->seconds->eq(Integer::from(1)) && $other->nanos->eq(Integer::from(0))) {
            /** @var Result<Double, ZeroDuration|DurationCalculationInvalid> */
            return Result::ok(Double::from((float)$this->seconds->toInt()));
        }

        // Convert to total nanoseconds as float for calculation
        $selfSecondsFloat = (float)$this->seconds->toInt();
        $selfNanosFloat = (float)$this->nanos->toInt();
        $otherSecondsFloat = (float)$other->seconds->toInt();
        $otherNanosFloat = (float)$other->nanos->toInt();

        // For extremely large values, use a different approach to avoid floating point overflow
        if ($selfSecondsFloat > \PHP_FLOAT_MAX / (float)self::NANOS_PER_SECOND ||
            $otherSecondsFloat > \PHP_FLOAT_MAX / (float)self::NANOS_PER_SECOND) {
            // Calculate seconds ratio first
            $secRatio = $this->seconds->toFloat() / $other->seconds->toFloat();
            // Adjust for nanoseconds as a small correction
            $nanoAdjustment = 0.0;

            if ($other->seconds->gt(Integer::from(0))) {
                $nanoAdjustment = ($this->nanos->toFloat() - ($secRatio * $other->nanos->toFloat()))
                                / ($other->seconds->toFloat() * (float)self::NANOS_PER_SECOND);
            }
            $quotient = $secRatio + $nanoAdjustment;
        } else {
            $selfTotal = ($selfSecondsFloat * (float)self::NANOS_PER_SECOND) + $selfNanosFloat;
            $otherTotal = ($otherSecondsFloat * (float)self::NANOS_PER_SECOND) + $otherNanosFloat;
            $quotient = $selfTotal / $otherTotal;
        }

        // Check if result is valid
        if (!\is_finite($quotient)) {
            /** @var Result<Double, ZeroDuration|DurationCalculationInvalid> */
            return Result::err(new DurationCalculationInvalid('Division resulted in an invalid value (infinity or NaN)'));
        }

        /** @var Result<Double, ZeroDuration|DurationCalculationInvalid> */
        return Result::ok(Double::from($quotient));
    }

    /**
     * Checked Duration addition. Computes `self + other`, returning an error if overflow occurred.
     *
     * @param self $other The duration to add
     * @return Result<self, DurationOverflow> The result of the addition or an error if overflow occurred
     */
    public function add(self $other): Result
    {
        // Add seconds
        $seconds = $this->seconds->overflowingAdd($other->seconds);

        if ($seconds->isErr()) {
            /** @var Result<self, DurationOverflow> */
            return Result::err(new DurationOverflow(
                'Overflow occurred while adding seconds',
            ));
        }

        // Add nanoseconds
        $nanos = $this->nanos->overflowingAdd($other->nanos);

        if ($nanos->isErr()) {
            /** @var Result<self, DurationOverflow> */
            return Result::err(new DurationOverflow(
                'Overflow occurred while adding nanoseconds',
            ));
        }

        $secondsResult = $seconds->unwrap();
        $nanosResult = $nanos->unwrap();

        // Handle overflow of nanoseconds to seconds
        if ($nanosResult->ge(self::NANOS_PER_SECOND)) {
            $nanosResult = $nanosResult->sub(self::NANOS_PER_SECOND);
            $secondsAdd = $secondsResult->overflowingAdd(1);

            if ($secondsAdd->isErr()) {
                /** @var Result<self, DurationOverflow> */
                return Result::err(new DurationOverflow(
                    'Overflow occurred while adding seconds',
                ));
            }

            $secondsResult = $secondsAdd->unwrap();
        }

        /** @var Result<self, DurationOverflow> */
        return Result::ok(self::new($secondsResult, $nanosResult));
    }

    /**
     * Saturating Duration addition. Computes `self + other`, returning `Duration::maximum()`
     * if overflow occurred.
     *
     * Unlike `add()`, this method never returns an error. Instead, it clamps the result
     * to the maximum possible duration value on overflow.
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
        if ($this->seconds->lt($other->seconds) ||
            ($this->seconds->eq($other->seconds) && $this->nanos->lt($other->nanos))) {
            /** @var Result<self, DurationOverflow> */
            return Result::err(new DurationOverflow('Subtraction would result in negative duration'));
        }

        // Subtract seconds
        $seconds = $this->seconds->overflowingSub($other->seconds);

        if ($seconds->isErr()) {
            /** @var Result<self, DurationOverflow> */
            return Result::err(new DurationOverflow('Overflow occurred while subtracting durations'));
        }

        $secondsResult = $seconds->unwrap();

        // Handle nanoseconds
        if ($this->nanos->ge($other->nanos)) {
            // Simple case: no need to borrow
            $nanosResult = $this->nanos->sub($other->nanos);
        } else {
            // Need to borrow a second
            $secondsSub = $secondsResult->overflowingSub(1);

            if ($secondsSub->isErr()) {
                /** @var Result<self, DurationOverflow> */
                return Result::err(new DurationOverflow('Overflow occurred while subtracting durations'));
            }

            $secondsResult = $secondsSub->unwrap();
            $nanosResult = $this
                ->nanos
                ->add(self::NANOS_PER_SECOND)
                ->sub($other->nanos);
        }

        /** @var Result<self, DurationOverflow> */
        return Result::ok(self::new($secondsResult, $nanosResult));
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
     * @param int|Integer $other The value to multiply by
     * @return Result<self, DurationOverflow> The result of the multiplication or an error if overflow occurred.
     */
    public function mul(int | Integer $other): Result
    {
        $other = $other instanceof Integer ? $other : Integer::from($other);

        // Convert nanoseconds to a larger number to avoid overflow
        $totalNanos = $this->nanos->mul($other);
        $extraSecs = $totalNanos->div(self::NANOS_PER_SECOND)->unwrap();
        $nanosResult = $totalNanos->sub($extraSecs->mul(self::NANOS_PER_SECOND));

        // Multiply seconds and add extra seconds from nanoseconds
        $secsResult = $this->seconds->overflowingMul($other);

        if ($secsResult->isErr()) {
            /** @var Result<self, DurationOverflow> */
            return Result::err(new DurationOverflow('Overflow occurred while multiplying seconds'));
        }

        $secondsResult = $secsResult->unwrap();
        $secondsPlusExtra = $secondsResult->overflowingAdd($extraSecs);

        if ($secondsPlusExtra->isErr()) {
            /** @var Result<self, DurationOverflow> */
            return Result::err(new DurationOverflow('Overflow occurred while adding extra seconds'));
        }

        /** @var Result<self, DurationOverflow> */
        return Result::ok(self::new($secondsPlusExtra->unwrap(), $nanosResult));
    }

    /**
     * Saturating Duration multiplication. Computes `self * other`, returning
     * `Duration::max()` if overflow occurred.
     *
     * @param int|Integer $rhs The value to multiply by
     * @return self A new Duration representing the product or max duration if overflow
     */
    public function saturatingMul(int | Integer $rhs): self
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
     * @param positive-int|Integer $rhs The divisor
     * @return Result<self, ZeroDuration> The result of the division or an error
     */
    public function div(int | Integer $rhs): Result
    {
        if ($rhs instanceof Integer) {
            $rhs = $rhs->toInt();
        }

        if ($rhs <= 0) {
            /** @var Result<self, ZeroDuration> */
            return Result::err(new ZeroDuration('Division by zero'));
        }

        $secs = $this->seconds->div($rhs)->unwrap();
        $extraSecs = $this->seconds->sub($secs->mul($rhs));

        // Convert remaining seconds to nanoseconds and add to existing nanoseconds
        $extraNanos = $extraSecs
            ->mul(self::NANOS_PER_SECOND)
            ->div($rhs)
            ->unwrap();

        $nanos = $this->nanos->div($rhs)->unwrap();
        $nanos = $nanos->add($extraNanos);

        /** @var Result<self, ZeroDuration> */
        return Result::ok(self::new($secs, $nanos));
    }

    /**
     * Compares two Durations.
     *
     * @param self $other The Duration to compare with.
     * @return Integer The result of the comparison.
     */
    public function cmp(self $other): Integer
    {
        $cmp = $this->seconds->cmp($other->seconds);

        if ($cmp->toInt() !== 0) {
            return $cmp;
        }

        return $this->nanos->cmp($other->nanos);
    }

    /**
     * Returns the maximum of two Durations.
     *
     * @param self $other The Duration to compare with.
     * @return self The maximum Duration.
     */
    public function max(self $other): self
    {
        return $this->cmp($other)->ge(0) ? new self($this->seconds, $this->nanos) : new self($other->seconds, $other->nanos);
    }

    /**
     * Returns the minimum of two Durations.
     *
     * @param self $other The Duration to compare with.
     * @return self The minimum Duration.
     */
    public function min(self $other): self
    {
        return $this->cmp($other)->le(0) ? new self($this->seconds, $this->nanos) : new self($other->seconds, $other->nanos);
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
        return $this->seconds->eq($other->seconds) && $this->nanos->eq($other->nanos);
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
        return $this->seconds->lt($other->seconds) || ($this->seconds->eq($other->seconds) && $this->nanos->lt($other->nanos));
    }

    /**
     * Checks if this Duration is less than or equal to another Duration.
     *
     * @param self $other The Duration to compare with.
     *
     * @return bool True if this Duration is less than or equal to the other Duration, false otherwise.
     */
    public function le(self $other): bool
    {
        return $this->seconds->lt($other->seconds) || ($this->seconds->eq($other->seconds) && $this->nanos->le($other->nanos));
    }

    /**
     * Checks if this Duration is greater than another Duration.
     *
     * @param self $other The Duration to compare with.
     *
     * @return bool True if this Duration is greater than the other Duration, false otherwise.
     */
    public function gt(self $other): bool
    {
        return $this->seconds->gt($other->seconds) || ($this->seconds->eq($other->seconds) && $this->nanos->gt($other->nanos));
    }

    /**
     * Checks if this Duration is greater than or equal to another Duration.
     *
     * @param self $other The Duration to compare with.
     *
     * @return bool True if this Duration is greater than or equal to the other Duration, false otherwise.
     */
    public function ge(self $other): bool
    {
        return $this->seconds->gt($other->seconds) || ($this->seconds->eq($other->seconds) && $this->nanos->ge($other->nanos));
    }

    /**
     * Returns the fractional part of this Duration, in whole microseconds.
     *
     * This method does **not** return the length of the duration when
     * represented by microseconds. The returned number always represents a
     * fractional portion of a second (i.e., it is less than one million).
     *
     * @return Integer The microseconds part (always less than 1 million)
     */
    public function subsecMicros(): Integer
    {
        return $this->nanos->div(self::NANOS_PER_MICRO)->unwrap();
    }

    /**
     * Returns the fractional part of this Duration, in whole milliseconds.
     *
     * This method does **not** return the length of the duration when
     * represented by milliseconds. The returned number always represents a
     * fractional portion of a second (i.e., it is less than one thousand).
     *
     * @return Integer The milliseconds part (always less than 1 thousand)
     */
    public function subsecMillis(): Integer
    {
        return $this->nanos->div(self::NANOS_PER_MILLI)->unwrap();
    }

    /**
     * Returns the fractional part of this Duration, in nanoseconds.
     *
     * This method does **not** return the length of the duration when
     * represented by nanoseconds. The returned number always represents a
     * fractional portion of a second (i.e., it is less than one billion).
     *
     * @return Integer The nanoseconds part (always less than 1 billion)
     */
    public function subsecNanos(): Integer
    {
        return $this->nanos;
    }

    /**
     * Creates a new Duration from the specified number of milliseconds.
     *
     * @param int|Integer $millis The number of milliseconds
     * @return self A new Duration instance
     * @psalm-pure
     */
    public static function fromMillis(int | Integer $millis): self
    {
        $millis = $millis instanceof Integer ? $millis : Integer::from($millis);
        $secs = $millis->div(self::MILLIS_PER_SECOND)->unwrap();
        $nanos = $millis
            ->sub($secs->mul(self::MILLIS_PER_SECOND))
            ->mul(self::NANOS_PER_MILLI);

        return self::new($secs, $nanos);
    }

    /**
     * Creates a new Duration from the specified number of microseconds.
     *
     * @param int|Integer $micros The number of microseconds
     * @return self A new Duration instance
     * @psalm-pure
     */
    public static function fromMicros(int | Integer $micros): self
    {
        $micros = $micros instanceof Integer ? $micros : Integer::from($micros);
        $secs = $micros->div(self::MICROS_PER_SECOND)->unwrap();
        $nanos = $micros
            ->sub($secs->mul(self::MICROS_PER_SECOND))
            ->mul(self::NANOS_PER_MICRO);

        return self::new($secs, $nanos);
    }

    /**
     * Creates a new Duration from the specified number of nanoseconds.
     *
     * @param int|Integer $nanos The number of nanoseconds
     * @return self A new Duration instance
     * @psalm-pure
     */
    public static function fromNanos(int | Integer $nanos): self
    {
        $nanos = $nanos instanceof Integer ? $nanos : Integer::from($nanos);
        $secs = $nanos->div(self::NANOS_PER_SECOND)->unwrap();
        $nanosPart = $nanos->sub($secs->mul(self::NANOS_PER_SECOND));

        return self::new($secs, $nanosPart);
    }

    /**
     * Creates a new Duration from the specified number of days.
     *
     * @param int|Integer $days The number of days
     * @return self A new Duration instance
     * @psalm-pure
     */
    public static function fromDays(int | Integer $days): self
    {
        $days = $days instanceof Integer ? $days : Integer::from($days);
        $secs = $days->mul(self::SECONDS_PER_DAY);

        return self::new($secs, 0);
    }

    /**
     * Creates a new Duration from the specified number of hours.
     *
     * @param int|Integer $hours The number of hours
     * @return self A new Duration instance
     * @psalm-pure
     */
    public static function fromHours(int | Integer $hours): self
    {
        $hours = $hours instanceof Integer ? $hours : Integer::from($hours);
        $secs = $hours->mul(self::SECONDS_PER_HOUR);

        return self::new($secs, 0);
    }

    /**
     * Creates a new Duration from the specified number of minutes.
     *
     * @param int|Integer $minutes The number of minutes
     * @return self A new Duration instance
     * @psalm-pure
     */
    public static function fromMins(int | Integer $minutes): self
    {
        $minutes = $minutes instanceof Integer ? $minutes : Integer::from($minutes);
        $secs = $minutes->mul(self::SECONDS_PER_MINUTE);

        return self::new($secs, 0);
    }

    /**
     * Creates a new Duration from the specified number of seconds.
     *
     * @param int|Integer $seconds The number of seconds
     * @return self A new Duration instance
     * @psalm-pure
     */
    public static function fromSeconds(int | Integer $seconds): self
    {
        $seconds = $seconds instanceof Integer ? $seconds : Integer::from($seconds);

        return self::new($seconds, 0);
    }

    /**
     * Creates a new Duration from the specified number of weeks.
     *
     * @param int|Integer $weeks The number of weeks
     * @return self A new Duration instance
     * @psalm-pure
     */
    public static function fromWeeks(int | Integer $weeks): self
    {
        $weeks = $weeks instanceof Integer ? $weeks : Integer::from($weeks);
        $secs = $weeks->mul(self::SECONDS_PER_WEEK);

        return self::new($secs, 0);
    }

    /**
     * Returns true if this Duration spans no time.
     *
     * @return bool True if the duration is zero
     */
    public function isZero(): bool
    {
        return $this->seconds->eq(0) && $this->nanos->eq(0);
    }

    /**
     * Creates a duration of zero time.
     *
     * @return self A Duration with value of zero
     * @psalm-pure
     */
    public static function zero(): self
    {
        return self::new(0, 0);
    }

    /**
     * Returns the maximum possible Duration value.
     *
     * @return self A Duration representing the maximum possible value
     * @psalm-pure
     */
    public static function maximum(): self
    {
        return self::new(\PHP_INT_MAX, self::MAX_NANOS);
    }

    /**
     * Creates a Duration representing one microsecond.
     *
     * @return self A Duration of one microsecond
     * @psalm-pure
     */
    public static function microsecond(): self
    {
        return self::new(0, self::NANOS_PER_MICRO);
    }

    /**
     * Creates a Duration representing one nanosecond.
     *
     * This represents the smallest possible non-zero Duration that can be created.
     *
     * @return self A Duration of one nanosecond
     * @psalm-pure
     */
    public static function nanosecond(): self
    {
        return self::new(0, 1);
    }

    /**
     * Creates a Duration representing one millisecond.
     *
     * @return self A Duration of one millisecond
     * @psalm-pure
     */
    public static function millisecond(): self
    {
        return self::new(0, self::NANOS_PER_MILLI);
    }

    /**
     * Creates a Duration representing one second.
     *
     * @return self A Duration of one second
     * @psalm-pure
     */
    public static function second(): self
    {
        return self::new(1, 0);
    }
}
