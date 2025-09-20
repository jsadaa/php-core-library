<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Time\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Time\Duration;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\ZeroDuration;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use PHPUnit\Framework\TestCase;

final class DurationTest extends TestCase
{
    public function testNew(): void
    {
        $duration = Duration::new(5, 500_000_000);
        $this->assertEquals(5, $duration->toSeconds()->toInt());
        $this->assertEquals(500_000_000, $duration->subsecNanos()->toInt());
    }

    public function testNewWithInteger(): void
    {
        $duration = Duration::new(Integer::of(3), Integer::of(250_000_000));
        $this->assertEquals(3, $duration->toSeconds()->toInt());
        $this->assertEquals(250_000_000, $duration->subsecNanos()->toInt());
    }

    public function testFromSeconds(): void
    {
        $duration = Duration::fromSeconds(10);
        $this->assertEquals(10, $duration->toSeconds()->toInt());
        $this->assertEquals(0, $duration->subsecNanos()->toInt());
    }

    public function testFromSecondsWithInteger(): void
    {
        $duration = Duration::fromSeconds(Integer::of(15));
        $this->assertEquals(15, $duration->toSeconds()->toInt());
        $this->assertEquals(0, $duration->subsecNanos()->toInt());
    }

    public function testFromMillis(): void
    {
        $duration = Duration::fromMillis(2500);
        $this->assertEquals(2, $duration->toSeconds()->toInt());
        $this->assertEquals(500, $duration->subsecMillis()->toInt());
        $this->assertEquals(500_000_000, $duration->subsecNanos()->toInt());
    }

    public function testFromMillisWithInteger(): void
    {
        $duration = Duration::fromMillis(Integer::of(1500));
        $this->assertEquals(1, $duration->toSeconds()->toInt());
        $this->assertEquals(500, $duration->subsecMillis()->toInt());
    }

    public function testFromMicros(): void
    {
        $duration = Duration::fromMicros(1_500_000);
        $this->assertEquals(1, $duration->toSeconds()->toInt());
        $this->assertEquals(500_000, $duration->subsecMicros()->toInt());
        $this->assertEquals(500_000_000, $duration->subsecNanos()->toInt());
    }

    public function testFromMicrosWithInteger(): void
    {
        $duration = Duration::fromMicros(Integer::of(2_750_000));
        $this->assertEquals(2, $duration->toSeconds()->toInt());
        $this->assertEquals(750_000, $duration->subsecMicros()->toInt());
    }

    public function testFromNanos(): void
    {
        $duration = Duration::fromNanos(2_500_000_000);
        $this->assertEquals(2, $duration->toSeconds()->toInt());
        $this->assertEquals(500_000_000, $duration->subsecNanos()->toInt());
    }

    public function testFromNanosWithInteger(): void
    {
        $duration = Duration::fromNanos(Integer::of(3_250_000_000));
        $this->assertEquals(3, $duration->toSeconds()->toInt());
        $this->assertEquals(250_000_000, $duration->subsecNanos()->toInt());
    }

    public function testFromMins(): void
    {
        $duration = Duration::fromMins(2);
        $this->assertEquals(120, $duration->toSeconds()->toInt());
        $this->assertEquals(0, $duration->subsecNanos()->toInt());
    }

    public function testFromMinsWithInteger(): void
    {
        $duration = Duration::fromMins(Integer::of(5));
        $this->assertEquals(300, $duration->toSeconds()->toInt());
    }

    public function testFromHours(): void
    {
        $duration = Duration::fromHours(2);
        $this->assertEquals(7200, $duration->toSeconds()->toInt());
        $this->assertEquals(0, $duration->subsecNanos()->toInt());
    }

    public function testFromHoursWithInteger(): void
    {
        $duration = Duration::fromHours(Integer::of(1));
        $this->assertEquals(3600, $duration->toSeconds()->toInt());
    }

    public function testFromDays(): void
    {
        $duration = Duration::fromDays(1);
        $this->assertEquals(86400, $duration->toSeconds()->toInt());
        $this->assertEquals(0, $duration->subsecNanos()->toInt());
    }

    public function testFromDaysWithInteger(): void
    {
        $duration = Duration::fromDays(Integer::of(2));
        $this->assertEquals(172800, $duration->toSeconds()->toInt());
    }

    public function testFromWeeks(): void
    {
        $duration = Duration::fromWeeks(1);
        $this->assertEquals(604800, $duration->toSeconds()->toInt());
        $this->assertEquals(0, $duration->subsecNanos()->toInt());
    }

    public function testFromWeeksWithInteger(): void
    {
        $duration = Duration::fromWeeks(Integer::of(2));
        $this->assertEquals(1209600, $duration->toSeconds()->toInt());
    }

    public function testZero(): void
    {
        $duration = Duration::zero();
        $this->assertTrue($duration->isZero());
        $this->assertEquals(0, $duration->toSeconds()->toInt());
        $this->assertEquals(0, $duration->subsecNanos()->toInt());
    }

    public function testIsZero(): void
    {
        $zero = Duration::zero();
        $this->assertTrue($zero->isZero());

        $nonZero = Duration::fromNanos(1);
        $this->assertFalse($nonZero->isZero());

        $nonZeroSeconds = Duration::fromSeconds(1);
        $this->assertFalse($nonZeroSeconds->isZero());
    }

    public function testMaximum(): void
    {
        $max = Duration::maximum();
        $this->assertEquals(\PHP_INT_MAX, $max->toSeconds()->toInt());
        $this->assertEquals(999_999_999, $max->subsecNanos()->toInt());
    }

    public function testNanosecond(): void
    {
        $nano = Duration::nanosecond();
        $this->assertEquals(0, $nano->toSeconds()->toInt());
        $this->assertEquals(1, $nano->subsecNanos()->toInt());
    }

    public function testMicrosecond(): void
    {
        $micro = Duration::microsecond();
        $this->assertEquals(0, $micro->toSeconds()->toInt());
        $this->assertEquals(1000, $micro->subsecNanos()->toInt());
        $this->assertEquals(1, $micro->subsecMicros()->toInt());
    }

    public function testMillisecond(): void
    {
        $milli = Duration::millisecond();
        $this->assertEquals(0, $milli->toSeconds()->toInt());
        $this->assertEquals(1_000_000, $milli->subsecNanos()->toInt());
        $this->assertEquals(1, $milli->subsecMillis()->toInt());
    }

    public function testSecond(): void
    {
        $second = Duration::second();
        $this->assertEquals(1, $second->toSeconds()->toInt());
        $this->assertEquals(0, $second->subsecNanos()->toInt());
    }

    public function testToConversions(): void
    {
        $duration = Duration::new(2, 500_000_000); // 2.5 seconds

        $this->assertEquals(2_500_000_000, $duration->toNanos()->toInt());
        $this->assertEquals(2_500_000, $duration->toMicros()->toInt());
        $this->assertEquals(2500, $duration->toMillis()->toInt());
        $this->assertEquals(2, $duration->toSeconds()->toInt());
    }

    public function testSubsecondComponents(): void
    {
        $duration = Duration::new(5, 123_456_789);

        $this->assertEquals(123_456_789, $duration->subsecNanos()->toInt());
        $this->assertEquals(123_456, $duration->subsecMicros()->toInt());
        $this->assertEquals(123, $duration->subsecMillis()->toInt());
    }

    public function testAdd(): void
    {
        $dur1 = Duration::fromSeconds(2);
        $dur2 = Duration::fromMillis(500);
        $result = $dur1->add($dur2);

        $this->assertTrue($result->isOk());
        $sum = $result->unwrap();
        $this->assertEquals(2, $sum->toSeconds()->toInt());
        $this->assertEquals(500_000_000, $sum->subsecNanos()->toInt());
    }

    public function testAddWithNanosecondOverflow(): void
    {
        $dur1 = Duration::new(1, 999_999_999);
        $dur2 = Duration::new(0, 2);
        $result = $dur1->add($dur2);

        $this->assertTrue($result->isOk());
        $sum = $result->unwrap();
        $this->assertEquals(2, $sum->toSeconds()->toInt());
        $this->assertEquals(1, $sum->subsecNanos()->toInt());
    }

    public function testSaturatingAdd(): void
    {
        $dur1 = Duration::fromSeconds(2);
        $dur2 = Duration::fromSeconds(3);
        $sum = $dur1->saturatingAdd($dur2);

        $this->assertEquals(5, $sum->toSeconds()->toInt());
    }

    public function testSub(): void
    {
        $dur1 = Duration::fromSeconds(5);
        $dur2 = Duration::fromSeconds(3);
        $result = $dur1->sub($dur2);

        $this->assertTrue($result->isOk());
        $diff = $result->unwrap();
        $this->assertEquals(2, $diff->toSeconds()->toInt());
    }

    public function testSubWithNanosecondBorrowing(): void
    {
        $dur1 = Duration::new(2, 100_000_000);
        $dur2 = Duration::new(0, 200_000_000);
        $result = $dur1->sub($dur2);

        $this->assertTrue($result->isOk());
        $diff = $result->unwrap();
        $this->assertEquals(1, $diff->toSeconds()->toInt());
        $this->assertEquals(900_000_000, $diff->subsecNanos()->toInt());
    }

    public function testSaturatingSub(): void
    {
        $dur1 = Duration::fromSeconds(5);
        $dur2 = Duration::fromSeconds(3);
        $diff = $dur1->saturatingSub($dur2);

        $this->assertEquals(2, $diff->toSeconds()->toInt());

        $underflow = Duration::fromSeconds(3);
        $largerDur = Duration::fromSeconds(5);
        $zero = $underflow->saturatingSub($largerDur);
        $this->assertTrue($zero->isZero());
    }

    public function testMul(): void
    {
        $dur = Duration::fromSeconds(2);
        $result = $dur->mul(3);

        $this->assertTrue($result->isOk());
        $product = $result->unwrap();
        $this->assertEquals(6, $product->toSeconds()->toInt());
    }

    public function testMulWithFractionalSeconds(): void
    {
        $dur = Duration::new(1, 500_000_000); // 1.5 seconds
        $result = $dur->mul(3);

        $this->assertTrue($result->isOk());
        $product = $result->unwrap();
        $this->assertEquals(4, $product->toSeconds()->toInt());
        $this->assertEquals(500_000_000, $product->subsecNanos()->toInt());
    }

    public function testMulWithInteger(): void
    {
        $dur = Duration::fromSeconds(4);
        $result = $dur->mul(Integer::of(2));

        $this->assertTrue($result->isOk());
        $product = $result->unwrap();
        $this->assertEquals(8, $product->toSeconds()->toInt());
    }

    public function testSaturatingMul(): void
    {
        $dur = Duration::fromSeconds(2);
        $product = $dur->saturatingMul(3);

        $this->assertEquals(6, $product->toSeconds()->toInt());
    }

    public function testDiv(): void
    {
        $dur = Duration::fromSeconds(10);
        $result = $dur->div(2);

        $this->assertTrue($result->isOk());
        $quotient = $result->unwrap();
        $this->assertEquals(5, $quotient->toSeconds()->toInt());
    }

    public function testDivWithRemainder(): void
    {
        $dur = Duration::fromSeconds(10);
        $result = $dur->div(3);

        $this->assertTrue($result->isOk());
        $quotient = $result->unwrap();
        $this->assertEquals(3, $quotient->toSeconds()->toInt());
        $this->assertEquals(333_333_333, $quotient->subsecNanos()->toInt());
    }

    public function testDivByZero(): void
    {
        $dur = Duration::fromSeconds(10);
        $result = $dur->div(0);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(ZeroDuration::class, $result->unwrapErr());
    }

    public function testDivByNegative(): void
    {
        $dur = Duration::fromSeconds(10);
        $result = $dur->div(-1);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(ZeroDuration::class, $result->unwrapErr());
    }

    public function testDivWithInteger(): void
    {
        $dur = Duration::fromSeconds(20);
        $result = $dur->div(Integer::of(4));

        $this->assertTrue($result->isOk());
        $quotient = $result->unwrap();
        $this->assertEquals(5, $quotient->toSeconds()->toInt());
    }

    public function testDivDuration(): void
    {
        $dur1 = Duration::fromSeconds(10);
        $dur2 = Duration::fromSeconds(5);
        $result = $dur1->divDuration($dur2);

        $this->assertTrue($result->isOk());
        $factor = $result->unwrap();
        $this->assertEquals(2.0, $factor->toFloat());
    }

    public function testDivDurationWithFractional(): void
    {
        $dur1 = Duration::fromSeconds(5);
        $dur2 = Duration::fromSeconds(2);
        $result = $dur1->divDuration($dur2);

        $this->assertTrue($result->isOk());
        $factor = $result->unwrap();
        $this->assertEquals(2.5, $factor->toFloat());
    }

    public function testDivDurationByZero(): void
    {
        $dur1 = Duration::fromSeconds(10);
        $dur2 = Duration::zero();
        $result = $dur1->divDuration($dur2);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(ZeroDuration::class, $result->unwrapErr());
    }

    public function testDivDurationZeroNumerator(): void
    {
        $dur1 = Duration::zero();
        $dur2 = Duration::fromSeconds(5);
        $result = $dur1->divDuration($dur2);

        $this->assertTrue($result->isOk());
        $factor = $result->unwrap();
        $this->assertEquals(0.0, $factor->toFloat());
    }

    public function testAbsDiff(): void
    {
        $dur1 = Duration::fromSeconds(5);
        $dur2 = Duration::fromSeconds(3);

        $diff1 = $dur1->absDiff($dur2);
        $diff2 = $dur2->absDiff($dur1);

        $this->assertEquals(2, $diff1->toSeconds()->toInt());
        $this->assertEquals(2, $diff2->toSeconds()->toInt());
        $this->assertTrue($diff1->eq($diff2));
    }

    public function testComparisons(): void
    {
        $dur1 = Duration::fromSeconds(5);
        $dur2 = Duration::fromSeconds(3);
        $dur3 = Duration::fromSeconds(5);

        $this->assertTrue($dur1->eq($dur3));
        $this->assertFalse($dur1->eq($dur2));

        $this->assertTrue($dur1->ne($dur2));
        $this->assertFalse($dur1->ne($dur3));

        $this->assertTrue($dur2->lt($dur1));
        $this->assertFalse($dur1->lt($dur2));
        $this->assertFalse($dur1->lt($dur3));

        $this->assertTrue($dur2->le($dur1));
        $this->assertTrue($dur1->le($dur3));
        $this->assertFalse($dur1->le($dur2));

        $this->assertTrue($dur1->gt($dur2));
        $this->assertFalse($dur2->gt($dur1));
        $this->assertFalse($dur1->gt($dur3));

        $this->assertTrue($dur1->ge($dur2));
        $this->assertTrue($dur1->ge($dur3));
        $this->assertFalse($dur2->ge($dur1));
    }

    public function testCmp(): void
    {
        $dur1 = Duration::fromSeconds(5);
        $dur2 = Duration::fromSeconds(3);
        $dur3 = Duration::fromSeconds(5);

        $this->assertEquals(1, $dur1->cmp($dur2)->toInt());
        $this->assertEquals(-1, $dur2->cmp($dur1)->toInt());
        $this->assertEquals(0, $dur1->cmp($dur3)->toInt());
    }

    public function testCmpWithNanoseconds(): void
    {
        $dur1 = Duration::new(1, 500_000_000);
        $dur2 = Duration::new(1, 250_000_000);

        $this->assertEquals(1, $dur1->cmp($dur2)->toInt());
        $this->assertEquals(-1, $dur2->cmp($dur1)->toInt());
    }

    public function testMax(): void
    {
        $dur1 = Duration::fromSeconds(5);
        $dur2 = Duration::fromSeconds(3);

        $max = $dur1->max($dur2);
        $this->assertTrue($max->eq($dur1));

        $max2 = $dur2->max($dur1);
        $this->assertTrue($max2->eq($dur1));
    }

    public function testMin(): void
    {
        $dur1 = Duration::fromSeconds(5);
        $dur2 = Duration::fromSeconds(3);

        $min = $dur1->min($dur2);
        $this->assertTrue($min->eq($dur2));

        $min2 = $dur2->min($dur1);
        $this->assertTrue($min2->eq($dur2));
    }

    public function testClamp(): void
    {
        $dur = Duration::fromSeconds(5);
        $min = Duration::fromSeconds(3);
        $max = Duration::fromSeconds(7);

        $clamped = $dur->clamp($min, $max);
        $this->assertTrue($clamped->eq($dur));

        $low = Duration::fromSeconds(1);
        $clampedLow = $low->clamp($min, $max);
        $this->assertTrue($clampedLow->eq($min));

        $high = Duration::fromSeconds(10);
        $clampedHigh = $high->clamp($min, $max);
        $this->assertTrue($clampedHigh->eq($max));
    }

    public function testConstants(): void
    {
        $this->assertEquals(1_000, Duration::NANOS_PER_MICRO);
        $this->assertEquals(1_000_000, Duration::NANOS_PER_MILLI);
        $this->assertEquals(1_000_000_000, Duration::NANOS_PER_SECOND);
        $this->assertEquals(1_000, Duration::MILLIS_PER_SECOND);
        $this->assertEquals(1_000_000, Duration::MICROS_PER_SECOND);
        $this->assertEquals(60, Duration::SECONDS_PER_MINUTE);
        $this->assertEquals(3_600, Duration::SECONDS_PER_HOUR);
        $this->assertEquals(86_400, Duration::SECONDS_PER_DAY);
        $this->assertEquals(604_800, Duration::SECONDS_PER_WEEK);
        $this->assertEquals(999_999_999, Duration::MAX_NANOS);
    }

    public function testPrecisionMaintenance(): void
    {
        $duration = Duration::fromNanos(123_456_789);

        $this->assertEquals(0, $duration->toSeconds()->toInt());
        $this->assertEquals(123_456_789, $duration->subsecNanos()->toInt());
        $this->assertEquals(123_456, $duration->subsecMicros()->toInt());
        $this->assertEquals(123, $duration->subsecMillis()->toInt());
    }

    public function testLargeValues(): void
    {
        $largeDuration = Duration::fromDays(365);
        $expectedSeconds = 365 * 24 * 60 * 60;
        $this->assertEquals($expectedSeconds, $largeDuration->toSeconds()->toInt());
    }

    public function testEdgeCases(): void
    {
        $maxNanos = Duration::new(0, 999_999_999);
        $this->assertEquals(0, $maxNanos->toSeconds()->toInt());
        $this->assertEquals(999_999_999, $maxNanos->subsecNanos()->toInt());

        $oneNano = Duration::nanosecond();
        $result = $maxNanos->add($oneNano);
        $this->assertTrue($result->isOk());
        $overflow = $result->unwrap();
        $this->assertEquals(1, $overflow->toSeconds()->toInt());
        $this->assertEquals(0, $overflow->subsecNanos()->toInt());
    }
}
