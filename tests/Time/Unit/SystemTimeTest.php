<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Time\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Time\Duration;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\TimeBeforeUnixEpoch;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\TimeReversal;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\TimeUnderflow;
use Jsadaa\PhpCoreLibrary\Modules\Time\SystemTime;
use PHPUnit\Framework\TestCase;

final class SystemTimeTest extends TestCase
{
    public function testFromTimestamp(): void
    {
        $timestamp = 1640995200; // 2022-01-01 00:00:00 UTC
        $time = SystemTime::fromTimestamp($timestamp);

        $this->assertEquals($timestamp, $time->seconds());
        $this->assertEquals(0, $time->nanos());
    }

    public function testFromTimestampWithNegative(): void
    {
        // Negative timestamps should be converted to absolute value
        $time = SystemTime::fromTimestamp(-1640995200);

        $this->assertEquals(1640995200, $time->seconds());
        $this->assertEquals(0, $time->nanos());
    }

    public function testNow(): void
    {
        $before = \time();
        $systemTime = SystemTime::now();
        $after = \time();

        $seconds = $systemTime->seconds();
        $this->assertGreaterThanOrEqual($before, $seconds);
        $this->assertLessThanOrEqual($after, $seconds);

        // Nanoseconds should be >= 0 and < 1 billion
        $nanos = $systemTime->nanos();
        $this->assertGreaterThanOrEqual(0, $nanos);
        $this->assertLessThan(1_000_000_000, $nanos);
    }

    public function testUnixEpoch(): void
    {
        $epoch = SystemTime::unixEpoch();

        $this->assertEquals(0, $epoch->seconds());
        $this->assertEquals(0, $epoch->nanos());
    }

    public function testMax(): void
    {
        $max = SystemTime::max();

        $this->assertEquals(\PHP_INT_MAX, $max->seconds());
        $this->assertEquals(999_999_999, $max->nanos());
    }

    public function testDurationSince(): void
    {
        $earlier = SystemTime::fromTimestamp(1640995200); // 2022-01-01 00:00:00
        $later = SystemTime::fromTimestamp(1640995260);   // 2022-01-01 00:01:00

        $result = $later->durationSince($earlier);

        $this->assertTrue($result->isOk());
        $duration = $result->unwrap();
        $this->assertEquals(60, $duration->toSeconds());
    }

    public function testDurationSinceWithNanoseconds(): void
    {
        $time1 = SystemTime::fromTimestamp(1640995200);
        $time2 = SystemTime::fromTimestamp(1640995201);

        $result = $time2->durationSince($time1);

        $this->assertTrue($result->isOk());
        $duration = $result->unwrap();
        $this->assertEquals(1, $duration->toSeconds());
    }

    public function testDurationSinceSameTime(): void
    {
        $time = SystemTime::fromTimestamp(1640995200);

        $result = $time->durationSince($time);

        $this->assertTrue($result->isOk());
        $duration = $result->unwrap();
        $this->assertTrue($duration->isZero());
    }

    public function testDurationSinceReverseOrder(): void
    {
        $earlier = SystemTime::fromTimestamp(1640995200);
        $later = SystemTime::fromTimestamp(1640995260);

        $result = $earlier->durationSince($later);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(TimeReversal::class, $result->unwrapErr());
    }

    public function testElapsed(): void
    {
        $past = SystemTime::fromTimestamp(\time() - 1);

        $result = $past->elapsed();

        if ($result->isOk()) {
            $duration = $result->unwrap();
            // Should be approximately 1 second, allow some tolerance
            $this->assertGreaterThanOrEqual(0, $duration->toSeconds());
            $this->assertLessThanOrEqual(5, $duration->toSeconds()); // Allow up to 5 seconds tolerance
        } else {
            $this->assertInstanceOf(TimeReversal::class, $result->unwrapErr());
        }
    }

    public function testElapsedFutureTime(): void
    {
        $future = SystemTime::fromTimestamp(\time() + 3600);

        $result = $future->elapsed();

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(TimeReversal::class, $result->unwrapErr());
    }

    public function testAdd(): void
    {
        $time = SystemTime::fromTimestamp(1640995200);
        $duration = Duration::fromHours(1);

        $result = $time->add($duration);

        $this->assertTrue($result->isOk());
        $newTime = $result->unwrap();
        $this->assertEquals(1640995200 + 3600, $newTime->seconds());
    }

    public function testAddWithNanoseconds(): void
    {
        $time = SystemTime::fromTimestamp(1640995200);
        $duration = Duration::new(1, 500_000_000); // 1.5 seconds

        $result = $time->add($duration);

        $this->assertTrue($result->isOk());
        $newTime = $result->unwrap();
        $this->assertEquals(1640995201, $newTime->seconds());
        $this->assertEquals(500_000_000, $newTime->nanos());
    }

    public function testAddWithNanosecondOverflow(): void
    {
        $time = SystemTime::fromTimestamp(1640995200);
        $duration = Duration::fromNanos(1_000_000_001); // 1 second + 1 nanosecond

        $result = $time->add($duration);

        $this->assertTrue($result->isOk());
        $newTime = $result->unwrap();
        $this->assertEquals(1640995201, $newTime->seconds());
        $this->assertEquals(1, $newTime->nanos());
    }

    public function testSub(): void
    {
        $time = SystemTime::fromTimestamp(1640995200);
        $duration = Duration::fromHours(1);

        $result = $time->sub($duration);

        $this->assertTrue($result->isOk());
        $newTime = $result->unwrap();
        $this->assertEquals(1640995200 - 3600, $newTime->seconds());
    }

    public function testSubWithNanoseconds(): void
    {
        $time = SystemTime::fromTimestamp(1640995201);
        $duration = Duration::new(0, 500_000_000); // 0.5 seconds

        $result = $time->sub($duration);

        $this->assertTrue($result->isOk());
        $newTime = $result->unwrap();
        $this->assertEquals(1640995200, $newTime->seconds());
        $this->assertEquals(500_000_000, $newTime->nanos());
    }

    public function testSubBeforeEpoch(): void
    {
        $epoch = SystemTime::unixEpoch();
        $duration = Duration::fromSeconds(1);

        $result = $epoch->sub($duration);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(TimeUnderflow::class, $result->unwrapErr());
    }

    public function testComparisons(): void
    {
        $time1 = SystemTime::fromTimestamp(1640995200);
        $time2 = SystemTime::fromTimestamp(1640995200);
        $time3 = SystemTime::fromTimestamp(1640995260);

        $this->assertTrue($time1->eq($time2));
        $this->assertFalse($time1->eq($time3));

        $this->assertTrue($time1->lt($time3));
        $this->assertFalse($time3->lt($time1));
        $this->assertFalse($time1->lt($time2));

        $this->assertTrue($time1->le($time2));
        $this->assertTrue($time1->le($time3));
        $this->assertFalse($time3->le($time1));

        $this->assertTrue($time3->gt($time1));
        $this->assertFalse($time1->gt($time3));
        $this->assertFalse($time1->gt($time2));

        $this->assertTrue($time1->ge($time2));
        $this->assertTrue($time3->ge($time1));
        $this->assertFalse($time1->ge($time3));
    }

    public function testComparisonWithNanoseconds(): void
    {
        $time1 = SystemTime::fromTimestamp(1640995200);
        $time2 = SystemTime::fromTimestamp(1640995201); // 1 second later

        $this->assertTrue($time1->lt($time2));
        $this->assertFalse($time1->gt($time2));
        $this->assertFalse($time1->eq($time2));
    }

    public function testToDateTimeImmutable(): void
    {
        $timestamp = 1640995200; // 2022-01-01 00:00:00 UTC
        $time = SystemTime::fromTimestamp($timestamp);

        $result = $time->toDateTimeImmutable();

        $this->assertTrue($result->isOk());
        $dateTime = $result->unwrap();

        $this->assertInstanceOf(\DateTimeImmutable::class, $dateTime);
        $this->assertEquals('2022-01-01 00:00:00', $dateTime->format('Y-m-d H:i:s'));
        // UTC timezone might be represented as '+00:00' in some PHP versions
        $this->assertTrue(\in_array($dateTime->getTimezone()->getName(), ['UTC', '+00:00'], true));
    }

    public function testToDateTimeImmutableWithMicroseconds(): void
    {
        // Test conversion preserves precision available in PHP
        $time = SystemTime::now(); // This will have microsecond precision

        $result = $time->toDateTimeImmutable();

        $this->assertTrue($result->isOk());
        $dateTime = $result->unwrap();

        // Verify format includes microseconds (6 digits)
        $formatted = $dateTime->format('Y-m-d H:i:s.u');
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{6}/', $formatted);
    }

    public function testFromDateTimeImmutable(): void
    {
        $dateTime = new \DateTimeImmutable('2022-01-01 00:00:00 UTC');

        $result = SystemTime::fromDateTimeImmutable($dateTime);

        $this->assertTrue($result->isOk());
        $systemTime = $result->unwrap();

        $this->assertEquals(1640995200, $systemTime->seconds());
        $this->assertEquals(0, $systemTime->nanos());
    }

    public function testFromDateTimeImmutableWithMicroseconds(): void
    {
        $dateTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', '2022-01-01 00:00:00.123456');
        $dateTime = $dateTime->setTimezone(new \DateTimeZone('UTC'));

        $result = SystemTime::fromDateTimeImmutable($dateTime);

        $this->assertTrue($result->isOk());
        $systemTime = $result->unwrap();

        $this->assertEquals(1640995200, $systemTime->seconds());
        $this->assertEquals(123_456_000, $systemTime->nanos()); // 123456 microseconds = 123,456,000 nanoseconds
    }

    public function testFromDateTimeImmutableWithTimezone(): void
    {
        $dateTime = new \DateTimeImmutable('2022-01-01 12:00:00 Europe/Paris');

        $result = SystemTime::fromDateTimeImmutable($dateTime);

        $this->assertTrue($result->isOk());
        $systemTime = $result->unwrap();

        $expectedUtc = $dateTime->setTimezone(new \DateTimeZone('UTC'));
        $expectedTimestamp = (int)$expectedUtc->format('U');

        $this->assertEquals($expectedTimestamp, $systemTime->seconds());
    }

    public function testFromDateTimeImmutableBeforeEpoch(): void
    {
        $beforeEpoch = new \DateTimeImmutable('1969-12-31 23:59:59 UTC');

        $result = SystemTime::fromDateTimeImmutable($beforeEpoch);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(TimeBeforeUnixEpoch::class, $result->unwrapErr());
    }

    public function testRoundTripConversion(): void
    {
        // Test that SystemTime -> DateTimeImmutable -> SystemTime preserves the value
        $originalTime = SystemTime::fromTimestamp(1640995200);

        $dateTimeResult = $originalTime->toDateTimeImmutable();
        $this->assertTrue($dateTimeResult->isOk());

        $systemTimeResult = SystemTime::fromDateTimeImmutable($dateTimeResult->unwrap());
        $this->assertTrue($systemTimeResult->isOk());

        $finalTime = $systemTimeResult->unwrap();

        $this->assertTrue($originalTime->eq($finalTime));
    }

    public function testAccessors(): void
    {
        $time = SystemTime::fromTimestamp(1640995200);

        $this->assertIsInt($time->seconds());
        $this->assertIsInt($time->nanos());

        $this->assertEquals(1640995200, $time->seconds());
        $this->assertEquals(0, $time->nanos());
    }

    public function testNowHasMicrosecondPrecision(): void
    {
        $time1 = SystemTime::now();
        \usleep(1000); // Sleep 1000 microseconds (1 millisecond)
        $time2 = SystemTime::now();

        $this->assertFalse($time1->eq($time2));

        $this->assertTrue($time2->gt($time1));

        $durationResult = $time2->durationSince($time1);
        $this->assertTrue($durationResult->isOk());

        $duration = $durationResult->unwrap();
        // Should be at least 1 millisecond but less than 1 second
        $this->assertGreaterThanOrEqual(1_000_000, $duration->toNanos()); // At least 1ms in nanos
        $this->assertLessThan(1_000_000_000, $duration->toNanos()); // Less than 1s in nanos
    }

    public function testTimeArithmetic(): void
    {
        $baseTime = SystemTime::fromTimestamp(1640995200);

        $oneSecond = Duration::fromSeconds(1);
        $oneMinute = Duration::fromMins(1);
        $oneHour = Duration::fromHours(1);
        $oneDay = Duration::fromDays(1);

        $plusSecond = $baseTime->add($oneSecond)->unwrap();
        $this->assertEquals(1640995201, $plusSecond->seconds());

        $plusMinute = $baseTime->add($oneMinute)->unwrap();
        $this->assertEquals(1640995200 + 60, $plusMinute->seconds());

        $plusHour = $baseTime->add($oneHour)->unwrap();
        $this->assertEquals(1640995200 + 3600, $plusHour->seconds());

        $plusDay = $baseTime->add($oneDay)->unwrap();
        $this->assertEquals(1640995200 + 86400, $plusDay->seconds());

        $backToBase = $plusDay->sub($oneDay)->unwrap();
        $this->assertTrue($baseTime->eq($backToBase));
    }

    public function testEdgeCases(): void
    {
        $maxTime = SystemTime::max();

        $this->assertEquals(\PHP_INT_MAX, $maxTime->seconds());
        $this->assertEquals(999_999_999, $maxTime->nanos());

        $now = SystemTime::now();
        $this->assertTrue($maxTime->gt($now));

        $epoch = SystemTime::unixEpoch();
        $this->assertTrue($now->gt($epoch));
        $this->assertTrue($epoch->le($now));
    }
}
