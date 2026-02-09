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
    private int $seconds;
    private int $nanos;

    private function __construct(int $seconds, int $nanos) {
        $this->seconds = $seconds;
        $this->nanos = $nanos;
    }

    // --- Factories ---

    /**
     * Creates a new SystemTime instance from a Unix timestamp.
     *
     * The timestamp represents seconds since the Unix epoch (1970-01-01 00:00:00 UTC).
     * Negative values are converted to their absolute value, which may not represent
     * the intended time.
     *
     * @param int<0, max> $timestamp The Unix timestamp in seconds
     * @return self The SystemTime instance
     * @psalm-pure
     */
    public static function fromTimestamp(int $timestamp): self
    {
        return new self(\abs($timestamp), 0);
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
        $microtime = \microtime(true);
        $seconds = (int)$microtime;
        $fractional = $microtime - (float)$seconds;
        $nanos = (int)($fractional * (float)Duration::NANOS_PER_SECOND);

        return new self($seconds, $nanos);
    }

    /**
     * Creates a new SystemTime instance representing the Unix epoch.
     *
     * The Unix epoch is 1970-01-01 00:00:00 UTC, which is timestamp 0.
     *
     * @return self The Unix epoch time (timestamp 0)
     * @psalm-pure
     */
    public static function unixEpoch(): self {
        return new self(0, 0);
    }

    /**
     * Returns a SystemTime representing the maximum possible value.
     *
     * @return self The maximum system time
     * @psalm-pure
     */
    public static function max(): self
    {
        return new self(\PHP_INT_MAX, Duration::NANOS_PER_SECOND - 1);
    }

    /**
     * Creates a SystemTime from a PHP DateTimeImmutable object.
     *
     * The underlying time must be at or after the Unix epoch (1970-01-01 00:00:00 UTC).
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

        $microseconds = (int)$datetime->format('u');
        $nanos = $microseconds * Duration::NANOS_PER_MICRO;

        /** @var Result<SystemTime, TimeBeforeUnixEpoch> */
        return Result::ok(new self($timestamp, $nanos));
    }

    // --- Accessors ---

    /**
     * Gets the seconds component of this time (seconds since Unix epoch).
     *
     * @return int The seconds since Unix epoch
     */
    public function seconds(): int
    {
        return $this->seconds;
    }

    /**
     * Gets the nanoseconds component of this time (the fractional part of the second).
     *
     * @return int The nanoseconds part (always less than 1 billion)
     */
    public function nanos(): int
    {
        return $this->nanos;
    }

    // --- Arithmetic ---

    /**
     * Calculates the duration between this time and an earlier time.
     *
     * @param SystemTime $earlier The earlier time
     * @return Result<Duration, TimeReversal|DurationOverflow> The duration between the two times
     */
    public function durationSince(self $earlier): Result
    {
        if ($this->seconds > $earlier->seconds) {
            $seconds = $this->seconds - $earlier->seconds;

            if ($this->nanos >= $earlier->nanos) {
                $nanos = $this->nanos - $earlier->nanos;
            } else {
                $seconds -= 1;
                $nanos = $this->nanos + Duration::NANOS_PER_SECOND - $earlier->nanos;
            }

            /** @var Result<Duration, TimeReversal|DurationOverflow> */
            return Duration::fromSeconds($seconds)->add(Duration::fromNanos($nanos));
        }

        if ($this->seconds === $earlier->seconds) {
            if ($this->nanos >= $earlier->nanos) {
                $nanos = $this->nanos - $earlier->nanos;

                /** @var Result<Duration, TimeReversal|DurationOverflow> */
                return Result::ok(Duration::fromNanos($nanos));
            }
        }

        /** @var Result<Duration, TimeReversal|DurationOverflow> */
        return Result::err(new TimeReversal('Second time is later than self'));
    }

    /**
     * Returns the duration between the current time and this time.
     *
     * @return Result<Duration, TimeReversal|DurationOverflow> The duration since this time
     */
    public function elapsed(): Result
    {
        return self::now()->durationSince(new self($this->seconds, $this->nanos));
    }

    /**
     * Adds a duration to this SystemTime.
     *
     * @param Duration $duration The duration to add
     * @return Result<self, TimeOverflow> A Result containing the new SystemTime or an error
     */
    public function add(Duration $duration): Result
    {
        $durationSeconds = $duration->toSeconds();
        $durationNanos = $duration->subsecNanos();

        $newNanos = $this->nanos + $durationNanos;
        $additionalSeconds = 0;

        if ($newNanos >= Duration::NANOS_PER_SECOND) {
            $additionalSeconds = 1;
            $newNanos -= Duration::NANOS_PER_SECOND;
        }

        $secondsAdd = self::checkedAdd($this->seconds, $durationSeconds);

        if ($secondsAdd->isErr()) {
            /** @var Result<SystemTime, TimeOverflow> */
            return Result::err(new TimeOverflow('Overflow occurred while adding seconds'));
        }

        $newSeconds = self::checkedAdd($secondsAdd->unwrap(), $additionalSeconds);

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
     * @param Duration $duration The duration to subtract
     * @return Result<SystemTime, TimeOverflow|TimeUnderflow> The new SystemTime or an error
     */
    public function sub(Duration $duration): Result
    {
        $durationSeconds = $duration->toSeconds();
        $durationNanos = $duration->subsecNanos();

        $newNanos = $this->nanos - $durationNanos;
        $additionalSeconds = 0;

        if ($newNanos < 0) {
            $additionalSeconds = -1;
            $newNanos += Duration::NANOS_PER_SECOND;
        }

        $secondsSub = self::checkedSub($this->seconds, $durationSeconds);

        if ($secondsSub->isErr()) {
            /** @var Result<SystemTime, TimeOverflow|TimeUnderflow> */
            return Result::err(new TimeOverflow('Underflow occurred while subtracting seconds'));
        }

        $newSeconds = self::checkedAdd($secondsSub->unwrap(), $additionalSeconds);

        if ($newSeconds->isErr()) {
            /** @var Result<SystemTime, TimeOverflow|TimeUnderflow> */
            return Result::err(new TimeOverflow('Underflow occurred while adjusting for nanosecond underflow'));
        }

        $result = new self($newSeconds->unwrap(), $newNanos);

        if ($result->lt(self::unixEpoch())) {
            /** @var Result<SystemTime, TimeOverflow|TimeUnderflow> */
            return Result::err(new TimeUnderflow('Result time would be before the Unix epoch'));
        }

        /** @var Result<SystemTime, TimeOverflow|TimeUnderflow> */
        return Result::ok($result);
    }

    // --- Comparisons ---

    /**
     * Checks if this time is equal to another time.
     *
     * @param SystemTime $other The other time to compare with
     * @return bool True if the times are equal
     */
    public function eq(self $other): bool
    {
        return $this->seconds === $other->seconds && $this->nanos === $other->nanos;
    }

    /**
     * Checks if this time is less than (earlier than) another time.
     *
     * @param SystemTime $other The other time to compare with
     * @return bool True if this time is earlier
     */
    public function lt(self $other): bool
    {
        return $this->seconds < $other->seconds ||
            ($this->seconds === $other->seconds && $this->nanos < $other->nanos);
    }

    /**
     * Checks if this time is less than or equal to another time.
     *
     * @param SystemTime $other The other time to compare with
     * @return bool True if this time is earlier or equal
     */
    public function le(self $other): bool
    {
        return $this->lt($other) || $this->eq($other);
    }

    /**
     * Checks if this time is greater than (later than) another time.
     *
     * @param SystemTime $other The other time to compare with
     * @return bool True if this time is later
     */
    public function gt(self $other): bool
    {
        return $this->seconds > $other->seconds ||
            ($this->seconds === $other->seconds && $this->nanos > $other->nanos);
    }

    /**
     * Checks if this time is greater than or equal to another time.
     *
     * @param SystemTime $other The other time to compare with
     * @return bool True if this time is later or equal
     */
    public function ge(self $other): bool
    {
        return $this->gt($other) || $this->eq($other);
    }

    // --- Conversion ---

    /**
     * Converts this SystemTime to a PHP DateTimeImmutable object.
     *
     * @return Result<\DateTimeImmutable, DateTimeConversionFailed> The DateTimeImmutable representation
     */
    public function toDateTimeImmutable(): Result
    {
        $microseconds = \intdiv($this->nanos, 1000);

        $datetime = \DateTimeImmutable::createFromFormat('U.u', \sprintf('%d.%06d', $this->seconds, $microseconds));

        if ($datetime === false) {
            /** @var Result<\DateTimeImmutable, DateTimeConversionFailed> */
            return Result::err(new DateTimeConversionFailed('Failed to create DateTimeImmutable from SystemTime'));
        }

        /** @var Result<\DateTimeImmutable, DateTimeConversionFailed> */
        return Result::ok($datetime);
    }

    // --- Overflow helpers ---

    /**
     * @return Result<int, TimeOverflow>
     * @psalm-pure
     * @psalm-suppress ImpureMethodCall
     */
    private static function checkedAdd(int $a, int $b): Result
    {
        if (($b > 0 && $a > \PHP_INT_MAX - $b) || ($b < 0 && $a < \PHP_INT_MIN - $b)) {
            /** @var Result<int, TimeOverflow> */
            return Result::err(new TimeOverflow('Overflow on addition'));
        }

        /** @var Result<int, TimeOverflow> */
        return Result::ok($a + $b);
    }

    /**
     * @return Result<int, TimeOverflow>
     * @psalm-pure
     * @psalm-suppress ImpureMethodCall
     */
    private static function checkedSub(int $a, int $b): Result
    {
        if (($b > 0 && $a < \PHP_INT_MIN + $b) || ($b < 0 && $a > \PHP_INT_MAX + $b)) {
            /** @var Result<int, TimeOverflow> */
            return Result::err(new TimeOverflow('Overflow on subtraction'));
        }

        /** @var Result<int, TimeOverflow> */
        return Result::ok($a - $b);
    }
}
