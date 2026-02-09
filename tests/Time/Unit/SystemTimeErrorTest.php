<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Time\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Time\Duration;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\DateTimeConversionFailed;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\TimeBeforeUnixEpoch;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\TimeOverflow;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\TimeReversal;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\TimeUnderflow;
use Jsadaa\PhpCoreLibrary\Modules\Time\SystemTime;
use PHPUnit\Framework\TestCase;

final class SystemTimeErrorTest extends TestCase
{
    public function testDurationSinceTimeReversal(): void
    {
        $laterTime = SystemTime::fromTimestamp(1640995260);
        $earlierTime = SystemTime::fromTimestamp(1640995200);

        $result = $earlierTime->durationSince($laterTime);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(TimeReversal::class, $result->unwrapErr());
        $this->assertEquals('Second time is later than self', $result->unwrapErr()->getMessage());
    }

    public function testDurationSinceWithNanosecondsReversal(): void
    {
        $time1 = SystemTime::fromTimestamp(1640995200);
        $time2 = SystemTime::fromTimestamp(1640995201); // 1 second later

        // time1 is earlier than time2, so time1->durationSince(time2) should fail
        $result = $time1->durationSince($time2);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(TimeReversal::class, $result->unwrapErr());
    }

    public function testAddOverflow(): void
    {
        $maxTime = SystemTime::max();
        $largeDuration = Duration::fromSeconds(1);

        $result = $maxTime->add($largeDuration);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(TimeOverflow::class, $result->unwrapErr());
    }

    public function testAddNanosecondOverflow(): void
    {
        $maxTime = SystemTime::max();
        $smallDuration = Duration::fromNanos(1); // Even 1 nanosecond should cause overflow

        $result = $maxTime->add($smallDuration);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(TimeOverflow::class, $result->unwrapErr());
    }

    public function testSubUnderflow(): void
    {
        $epoch = SystemTime::unixEpoch();
        $duration = Duration::fromSeconds(1);

        $result = $epoch->sub($duration);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(TimeUnderflow::class, $result->unwrapErr());
        $this->assertEquals('Result time would be before the Unix epoch', $result->unwrapErr()->getMessage());
    }

    public function testSubNanosecondUnderflow(): void
    {
        $nearEpoch = SystemTime::fromTimestamp(0); // Unix epoch
        $largeDuration = Duration::fromSeconds(1);

        $result = $nearEpoch->sub($largeDuration);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(TimeUnderflow::class, $result->unwrapErr());
    }

    public function testFromDateTimeImmutableBeforeEpoch(): void
    {
        $beforeEpoch = new \DateTimeImmutable('1969-12-31 23:59:59 UTC');

        $result = SystemTime::fromDateTimeImmutable($beforeEpoch);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(TimeBeforeUnixEpoch::class, $result->unwrapErr());
        $this->assertEquals('DateTimeImmutable represents a time before Unix epoch', $result->unwrapErr()->getMessage());
    }

    public function testFromDateTimeImmutableWayBeforeEpoch(): void
    {
        $wayBeforeEpoch = new \DateTimeImmutable('1900-01-01 00:00:00 UTC');

        $result = SystemTime::fromDateTimeImmutable($wayBeforeEpoch);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(TimeBeforeUnixEpoch::class, $result->unwrapErr());
    }

    public function testToDateTimeImmutableFailure(): void
    {
        $maxTime = SystemTime::max();

        $result = $maxTime->toDateTimeImmutable();

        // This might succeed or fail depending on the PHP version and system
        if ($result->isErr()) {
            $this->assertInstanceOf(DateTimeConversionFailed::class, $result->unwrapErr());
        } else {
            $this->assertInstanceOf(\DateTimeImmutable::class, $result->unwrap());
        }
    }

    public function testElapsedWithFutureTime(): void
    {
        $futureTime = SystemTime::fromTimestamp(\time() + 3600);

        $result = $futureTime->elapsed();

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(TimeReversal::class, $result->unwrapErr());
    }

    public function testComplexErrorScenarios(): void
    {
        $maxTime = SystemTime::max();
        $epochTime = SystemTime::unixEpoch();

        $largeDuration = Duration::fromSeconds(1000);

        $overflowResult = $maxTime->add($largeDuration);
        $this->assertTrue($overflowResult->isErr());
        $this->assertInstanceOf(TimeOverflow::class, $overflowResult->unwrapErr());

        $underflowResult = $epochTime->sub($largeDuration);
        $this->assertTrue($underflowResult->isErr());
        $this->assertInstanceOf(TimeUnderflow::class, $underflowResult->unwrapErr());
    }

    public function testTimeReversalWithMixedUnits(): void
    {
        $baseTime = SystemTime::fromTimestamp(1640995200);
        $futureTime = $baseTime->add(Duration::fromMins(30))->unwrap();

        $reversalTests = [
            $baseTime->durationSince($futureTime),
            $baseTime->durationSince($baseTime->add(Duration::fromSeconds(1))->unwrap()),
            $baseTime->durationSince($baseTime->add(Duration::fromNanos(1))->unwrap()),
        ];

        foreach ($reversalTests as $result) {
            $this->assertTrue($result->isErr());
            $this->assertInstanceOf(TimeReversal::class, $result->unwrapErr());
        }
    }

    public function testCascadingTimeOperations(): void
    {
        $maxTime = SystemTime::max();

        $operations = [
            static fn() => $maxTime->add(Duration::fromSeconds(1)),
            static fn() => $maxTime->add(Duration::fromNanos(1)),
            static fn() => $maxTime->add(Duration::fromMillis(1)),
        ];

        foreach ($operations as $operation) {
            $result = $operation();
            $this->assertTrue($result->isErr());
            $this->assertInstanceOf(TimeOverflow::class, $result->unwrapErr());
        }
    }

    public function testBoundaryConditions(): void
    {
        $exactMax = SystemTime::max();
        $exactEpoch = SystemTime::unixEpoch();

        $oneNano = Duration::fromNanos(1);
        $result = $exactMax->add($oneNano);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(TimeOverflow::class, $result->unwrapErr());

        $subResult = $exactEpoch->sub($oneNano);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(TimeUnderflow::class, $subResult->unwrapErr());
    }

    public function testErrorRecovery(): void
    {
        $originalTime = SystemTime::fromTimestamp(1640995200);
        $originalTimestamp = $originalTime->seconds();

        $result = $originalTime->sub(Duration::fromSeconds(2000000000));

        $this->assertTrue($result->isErr());
        $this->assertEquals($originalTimestamp, $originalTime->seconds());

        $validResult = $originalTime->add(Duration::fromSeconds(60));
        $this->assertTrue($validResult->isOk());
    }
}
