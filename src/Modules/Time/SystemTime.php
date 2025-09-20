<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Time;

use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\DateTimeConversionFailed;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\DurationOverflow;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\TimeBeforeUnixEpoch;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\TimeOverflow;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\TimeReversal;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\TimeUnderflow;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;

/**
 * An immutable representation of a system time, representing a specific moment in time.
 *
 * SystemTime represents time as seconds since the Unix epoch (1970-01-01 00:00:00 UTC)
 * with nanosecond precision. It provides methods for time arithmetic, comparisons,
 * and conversions to/from PHP's DateTimeImmutable.
 *
 * @psalm-immutable
 */
final readonly class SystemTime {
    private Integer $seconds;
    private Integer $nanos;

    private function __construct(Integer $seconds, Integer $nanos) {
        $this->seconds = $seconds;
        $this->nanos = $nanos;
    }

    /**
     * Creates a new SystemTime instance from a Unix timestamp.
     *
     * The timestamp represents seconds since the Unix epoch (1970-01-01 00:00:00 UTC).
     * Negative values are converted to their absolute value, which may not represent
     * the intended time.
     *
     * @param int<0, max>|Integer $timestamp The Unix timestamp in seconds
     * @return self The SystemTime instance
     * @psalm-pure
     */
    public static function fromTimestamp(int | Integer $timestamp): self
    {
        $seconds = $timestamp instanceof Integer ? $timestamp : Integer::of($timestamp);
        $seconds = $seconds->abs();
        $nanos = Integer::of(0);

        return new self($seconds, $nanos);
    }

    /**
     * Creates a new SystemTime instance representing the current system time.
     *
     * Uses PHP's microtime() to get the current time with microsecond precision.
     * Note that PHP only provides microsecond precision, so the last 3 digits
     * of nanosecond precision will always be zero.
     *
     * @return self The current system time
     * @psalm-pure
     * @psalm-suppress ImpureFunctionCall
     */
    public static function now(): self {
        // Get the current timestamp with microseconds
        $microtime = \microtime(true);
        $seconds = Integer::of((int)$microtime);

        // Extract the nanoseconds (PHP only provides microseconds)
        $fractional = $microtime - (float)(int)$microtime;
        $nanos = Integer::of((int)($fractional * (float)Duration::NANOS_PER_SECOND));

        return new self($seconds, $nanos);
    }

    /**
     * Creates a new SystemTime instance representing the Unix epoch.
     *
     * The Unix epoch is 1970-01-01 00:00:00 UTC, which is timestamp 0.
     * This is useful as a reference point for time calculations.
     *
     * @return self The Unix epoch time (timestamp 0)
     * @psalm-pure
     */
    public static function unixEpoch(): self {
        return new self(Integer::of(0), Integer::of(0));
    }

    /**
     * Calculates the duration between this time and an earlier time.
     *
     * Returns an error if the provided time is actually later than this time,
     * since durations cannot be negative.
     *
     * @param SystemTime $earlier The earlier time
     * @return Result<Duration, TimeReversal|DurationOverflow> The duration between the two times, or an error if time order is invalid
     */
    public function durationSince(self $earlier): Result
    {
        // Compare seconds first
        if ($this->seconds->gt($earlier->seconds)) {
            // Simple case: seconds are greater, we can safely calculate
            $seconds = $this->seconds->sub($earlier->seconds);

            // Handle nanoseconds
            if ($this->nanos->ge($earlier->nanos)) {
                $nanos = $this->nanos->sub($earlier->nanos);
            } else {
                $seconds = $seconds->sub(Integer::of(1));
                $nanos = $this->nanos->add(Integer::of(Duration::NANOS_PER_SECOND))->sub($earlier->nanos);
            }

            /** @var Result<Duration, TimeReversal|DurationOverflow> */
            return Duration::fromSeconds($seconds)->add(Duration::fromNanos($nanos));
        }

        if ($this->seconds->eq($earlier->seconds)) {
            // Same second, compare nanoseconds
            if ($this->nanos->ge($earlier->nanos)) {
                $nanos = $this->nanos->sub($earlier->nanos);

                /** @var Result<Duration, TimeReversal|DurationOverflow> */
                return Result::ok(Duration::fromNanos($nanos));
            }
        }

        // We're earlier than the "earlier" time, which is an error
        /** @var Result<Duration, TimeReversal|DurationOverflow> */
        return Result::err(new TimeReversal('Second time is later than self'));
    }

    /**
     * Returns the duration between the current time and this time.
     *
     * This is equivalent to `SystemTime::now()->durationSince($this)`.
     * Returns an error if this time is in the future relative to the current time.
     *
     * @return Result<Duration, TimeReversal|DurationOverflow> The duration since this time, or an error if this time is in the future
     */
    public function elapsed(): Result
    {
        return self::now()->durationSince(new self($this->seconds, $this->nanos));
    }

    /**
     * Adds a duration to this SystemTime.
     *
     * Returns an error if the addition would cause an overflow in the underlying
     * integer representation.
     *
     * @param Duration $duration The duration to add
     * @return Result<self, TimeOverflow> A Result containing the new SystemTime or an error if overflow occurs
     */
    public function add(Duration $duration): Result
    {
        $seconds = $duration->toSeconds();
        $nanos = $duration->subsecNanos();

        $newNanos = $this->nanos->add($nanos);
        $additionalSeconds = Integer::of(0);

        // Handle nanosecond overflow
        if ($newNanos->ge(Integer::of(Duration::NANOS_PER_SECOND))) {
            $additionalSeconds = Integer::of(1);
            $newNanos = $newNanos->sub(Integer::of(Duration::NANOS_PER_SECOND));
        }

        // Add seconds with overflow checking
        $secondsAdd = $this->seconds->overflowingAdd($seconds);

        if ($secondsAdd->isErr()) {
            /** @var Result<SystemTime, TimeOverflow> */
            return Result::err(new TimeOverflow('Overflow occurred while adding seconds'));
        }

        // Add the additional seconds from nanosecond overflow
        $newSeconds = $secondsAdd->unwrap()->overflowingAdd($additionalSeconds);

        if ($newSeconds->isErr()) {
            /** @var Result<SystemTime, TimeOverflow> */
            return Result::err(new TimeOverflow('Overflow occurred while adding additional seconds'));
        }

        /** @var Result<SystemTime, TimeOverflow> */
        return Result::ok(new self($newSeconds->unwrap(), $newNanos));
    }

    /**
     * Subtracts a duration from this SystemTime.
     *
     * Returns an error if the subtraction would result in a time before the Unix epoch
     * or cause an underflow in the underlying integer representation.
     *
     * @param Duration $duration The duration to subtract
     * @return Result<SystemTime, TimeOverflow|TimeUnderflow> The new SystemTime or an error
     */
    public function sub(Duration $duration): Result
    {
        $seconds = $duration->toSeconds();
        $nanos = $duration->subsecNanos();

        $newNanos = $this->nanos->sub($nanos);
        $additionalSeconds = Integer::of(0);

        // Handle nanosecond underflow
        if ($newNanos->lt(Integer::of(0))) {
            $additionalSeconds = Integer::of(-1);
            $newNanos = $newNanos->add(Integer::of(Duration::NANOS_PER_SECOND));
        }

        // Subtract seconds with underflow checking
        $secondsSub = $this->seconds->overflowingSub($seconds);

        if ($secondsSub->isErr()) {
            /** @var Result<SystemTime, TimeOverflow|TimeUnderflow> */
            return Result::err(new TimeOverflow('Underflow occurred while subtracting seconds'));
        }

        // Add the additional seconds from nanosecond underflow
        $newSeconds = $secondsSub->unwrap()->overflowingAdd($additionalSeconds);

        if ($newSeconds->isErr()) {
            /** @var Result<SystemTime, TimeOverflow|TimeUnderflow> */
            return Result::err(new TimeOverflow('Underflow occurred while adjusting for nanosecond underflow'));
        }

        // Verify the result is not before the Unix epoch
        $result = new self($newSeconds->unwrap(), $newNanos);

        if ($result->lt(self::unixEpoch())) {
            /** @var Result<SystemTime, TimeOverflow|TimeUnderflow> */
            return Result::err(new TimeUnderflow('Result time would be before the Unix epoch'));
        }

        /** @var Result<SystemTime, TimeOverflow|TimeUnderflow> */
        return Result::ok($result);
    }

    /**
     * Checks if this time is equal to another time.
     *
     * Two SystemTimes are equal if both their seconds and nanoseconds components
     * are identical.
     *
     * @param SystemTime $other The other time to compare with
     * @return bool True if the times are equal, false otherwise
     */
    public function eq(self $other): bool
    {
        return $this->seconds->eq($other->seconds) && $this->nanos->eq($other->nanos);
    }

    /**
     * Checks if this time is less than (earlier than) another time.
     *
     * @param SystemTime $other The other time to compare with
     * @return bool True if this time is earlier than the other, false otherwise
     */
    public function lt(self $other): bool
    {
        return $this->seconds->lt($other->seconds) ||
            ($this->seconds->eq($other->seconds) && $this->nanos->lt($other->nanos));
    }

    /**
     * Checks if this time is less than or equal to (earlier than or same as) another time.
     *
     * @param SystemTime $other The other time to compare with
     * @return bool True if this time is earlier than or equal to the other, false otherwise
     */
    public function le(self $other): bool
    {
        return $this->lt($other) || $this->eq($other);
    }

    /**
     * Checks if this time is greater than (later than) another time.
     *
     * @param SystemTime $other The other time to compare with
     * @return bool True if this time is later than the other, false otherwise
     */
    public function gt(self $other): bool
    {
        return $this->seconds->gt($other->seconds) ||
            ($this->seconds->eq($other->seconds) && $this->nanos->gt($other->nanos));
    }

    /**
     * Checks if this time is greater than or equal to (later than or same as) another time.
     *
     * @param SystemTime $other The other time to compare with
     * @return bool True if this time is later than or equal to the other, false otherwise
     */
    public function ge(self $other): bool
    {
        return $this->gt($other) || $this->eq($other);
    }

    /**
     * Returns a SystemTime representing the maximum possible value.
     *
     * This represents the latest possible time that can be represented by this
     * implementation, using the maximum integer value for seconds and the maximum
     * nanoseconds value (999,999,999).
     *
     * @return self The maximum system time
     * @psalm-pure
     */
    public static function max(): self
    {
        return new self(
            Integer::maximum(),
            Integer::of(Duration::NANOS_PER_SECOND - 1),
        );
    }

    /**
     * Gets the seconds component of this time (seconds since Unix epoch).
     *
     * This returns only the whole seconds part, without any fractional seconds.
     *
     * @return Integer The seconds since Unix epoch
     */
    public function seconds(): Integer
    {
        return $this->seconds;
    }

    /**
     * Gets the nanoseconds component of this time (the fractional part of the second).
     *
     * This value represents the subsecond portion and is always in the range [0, 999_999_999].
     *
     * @return Integer The nanoseconds part (always less than 1 billion)
     */
    public function nanos(): Integer
    {
        return $this->nanos;
    }

    /**
     * Converts this SystemTime to a PHP DateTimeImmutable object.
     *
     * The conversion preserves microsecond precision (6 decimal places) but loses
     * nanosecond precision (the last 3 digits). The resulting DateTimeImmutable
     * will be in UTC timezone.
     *
     * @return Result<\DateTimeImmutable, DateTimeConversionFailed> The DateTimeImmutable representation of this time
     */
    public function toDateTimeImmutable(): Result
    {
        $seconds = $this->seconds->toInt();
        $microseconds = (int)($this->nanos->toInt() / 1000); // Convert nanos to micros

        $datetime = \DateTimeImmutable::createFromFormat('U.u', \sprintf('%d.%06d', $seconds, $microseconds));

        if ($datetime === false) {
            /** @var Result<\DateTimeImmutable, DateTimeConversionFailed> */
            return Result::err(new DateTimeConversionFailed('Failed to create DateTimeImmutable from SystemTime'));
        }

        /** @var Result<\DateTimeImmutable, DateTimeConversionFailed> */
        return Result::ok($datetime);
    }

    /**
     * Creates a SystemTime from a PHP DateTimeImmutable object.
     *
     * The underlying time must be at or after the Unix epoch (1970-01-01 00:00:00 UTC).
     * Times before the Unix epoch will result in an error.
     *
     * **Precision Note:** PHP's DateTimeImmutable only provides microsecond precision (10^-6 seconds),
     * while SystemTime uses nanosecond precision (10^-9 seconds). This conversion
     * will zero-fill the last 3 digits of precision. For example, 500 microseconds
     * becomes 500,000 nanoseconds, but any precision beyond microseconds is lost.
     *
     * @param \DateTimeImmutable $datetime The DateTimeImmutable to convert
     * @return Result<SystemTime, TimeBeforeUnixEpoch> The SystemTime or an error if before Unix epoch
     */
    public static function fromDateTimeImmutable(\DateTimeImmutable $datetime): Result
    {
        $timestamp = (int)$datetime->format('U');

        if ($timestamp < 0) {
            /** @var Result<SystemTime, TimeBeforeUnixEpoch> */
            return Result::err(new TimeBeforeUnixEpoch('DateTimeImmutable represents a time before Unix epoch'));
        }

        $seconds = Integer::of($timestamp);
        $microseconds = (int)$datetime->format('u');
        $nanos = Integer::of($microseconds * Duration::NANOS_PER_MICRO);

        /** @var Result<SystemTime, TimeBeforeUnixEpoch> */
        return Result::ok(new self($seconds, $nanos));
    }
}
