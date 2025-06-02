<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Time\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Time\Duration;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\DurationCalculationInvalid;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\DurationOverflow;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\ZeroDuration;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use PHPUnit\Framework\TestCase;

final class DurationErrorTest extends TestCase
{
    public function testAddOverflow(): void
    {
        $maxDuration = Duration::new(\PHP_INT_MAX, 999_999_999);
        $smallDuration = Duration::fromSeconds(1);

        $result = $maxDuration->add($smallDuration);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(DurationOverflow::class, $result->unwrapErr());
    }

    public function testAddNanosecondOverflow(): void
    {
        $maxSeconds = Duration::new(\PHP_INT_MAX, 0);
        $oneNano = Duration::fromNanos(1_000_000_000); // This adds 1 second

        $result = $maxSeconds->add($oneNano);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(DurationOverflow::class, $result->unwrapErr());
    }

    public function testSubUnderflow(): void
    {
        $smallDuration = Duration::fromSeconds(1);
        $largeDuration = Duration::fromSeconds(10);

        $result = $smallDuration->sub($largeDuration);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(DurationOverflow::class, $result->unwrapErr());
    }

    public function testSubNanosecondBorrowingUnderflow(): void
    {
        $duration1 = Duration::new(5, 100_000_000); // 5.1 seconds
        $duration2 = Duration::new(10, 200_000_000); // 10.2 seconds

        $result = $duration1->sub($duration2);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(DurationOverflow::class, $result->unwrapErr());
    }

    public function testMulOverflow(): void
    {
        $largeDuration = Duration::new((int)(\PHP_INT_MAX / 2), 0);
        $multiplier = 3;

        $result = $largeDuration->mul($multiplier);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(DurationOverflow::class, $result->unwrapErr());
    }

    public function testMulOverflowWithInteger(): void
    {
        $largeDuration = Duration::new((int)(\PHP_INT_MAX / 2), 0);
        $multiplier = Integer::from(3);

        $result = $largeDuration->mul($multiplier);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(DurationOverflow::class, $result->unwrapErr());
    }

    public function testMulNanosecondOverflow(): void
    {
        $duration = Duration::new(\PHP_INT_MAX, 500_000_000);
        $multiplier = 2;

        $result = $duration->mul($multiplier);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(DurationOverflow::class, $result->unwrapErr());
    }

    public function testDivByZero(): void
    {
        $duration = Duration::fromSeconds(10);

        $result = $duration->div(0);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(ZeroDuration::class, $result->unwrapErr());
        $this->assertEquals('Division by zero', $result->unwrapErr()->getMessage());
    }

    public function testDivByNegative(): void
    {
        $duration = Duration::fromSeconds(10);

        $result = $duration->div(-5);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(ZeroDuration::class, $result->unwrapErr());
        $this->assertEquals('Division by zero', $result->unwrapErr()->getMessage());
    }

    public function testDivByZeroInteger(): void
    {
        $duration = Duration::fromSeconds(10);

        $result = $duration->div(Integer::from(0));

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(ZeroDuration::class, $result->unwrapErr());
    }

    public function testDivDurationByZero(): void
    {
        $duration1 = Duration::fromSeconds(10);
        $zeroDuration = Duration::zero();

        $result = $duration1->divDuration($zeroDuration);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(ZeroDuration::class, $result->unwrapErr());
        $this->assertEquals('Cannot divide by a zero duration', $result->unwrapErr()->getMessage());
    }

    public function testDivDurationInvalidResult(): void
    {
        // Create a scenario that might produce invalid floating point results
        // This is harder to reproduce consistently, but we can test the error path
        $duration1 = Duration::new(\PHP_INT_MAX, 999_999_999);
        $duration2 = Duration::fromNanos(1);

        $result = $duration1->divDuration($duration2);

        // This might succeed or fail depending on floating point limits
        // If it fails, it should be DurationCalculationInvalid
        if ($result->isErr()) {
            $this->assertInstanceOf(DurationCalculationInvalid::class, $result->unwrapErr());
        } else {
            // If it succeeds, the result should be finite
            $value = $result->unwrap();
            $this->assertTrue(\is_finite($value->toFloat()));
        }
    }

    public function testSaturatingAddPreventsCrash(): void
    {
        $maxDuration = Duration::new(\PHP_INT_MAX, 999_999_999);
        $smallDuration = Duration::fromSeconds(1);

        $result = $maxDuration->saturatingAdd($smallDuration);

        $this->assertTrue($result->eq(Duration::maximum()));
    }

    public function testSaturatingSubPreventsCrash(): void
    {
        $smallDuration = Duration::fromSeconds(1);
        $largeDuration = Duration::fromSeconds(10);

        $result = $smallDuration->saturatingSub($largeDuration);

        $this->assertTrue($result->eq(Duration::zero()));
    }

    public function testSaturatingMulPreventsCrash(): void
    {
        $largeDuration = Duration::new((int)(\PHP_INT_MAX / 2), 0);
        $multiplier = 3;

        $result = $largeDuration->saturatingMul($multiplier);

        $this->assertTrue($result->eq(Duration::maximum()));
    }

    public function testErrorInheritance(): void
    {
        $overflow = new DurationOverflow();
        $this->assertInstanceOf(\OverflowException::class, $overflow);

        $zeroDuration = new ZeroDuration();
        $this->assertInstanceOf(\InvalidArgumentException::class, $zeroDuration);

        $calculationInvalid = new DurationCalculationInvalid();
        $this->assertInstanceOf(\RuntimeException::class, $calculationInvalid);
    }

    public function testComplexOverflowScenarios(): void
    {
        $largeDuration1 = Duration::new((int)(\PHP_INT_MAX / 2), 500_000_000);
        $largeDuration2 = Duration::new((int)(\PHP_INT_MAX / 2), 600_000_000);

        $addResult = $largeDuration1->add($largeDuration2);
        $this->assertTrue($addResult->isErr());
        $this->assertInstanceOf(DurationOverflow::class, $addResult->unwrapErr());

        $saturatingResult = $largeDuration1->saturatingAdd($largeDuration2);
        $this->assertTrue($saturatingResult->eq(Duration::maximum()));
    }

    public function testEdgeCaseOverflows(): void
    {
        $edgeDuration = Duration::new(1, 999_999_999);
        $oneNano = Duration::fromNanos(1);

        $result = $edgeDuration->add($oneNano);
        $this->assertTrue($result->isOk());

        $sum = $result->unwrap();
        $this->assertEquals(2, $sum->toSeconds()->toInt());
        $this->assertEquals(0, $sum->subsecNanos()->toInt());

        $maxSeconds = Duration::new(\PHP_INT_MAX, 999_999_999);
        $overflowResult = $maxSeconds->add($oneNano);
        $this->assertTrue($overflowResult->isErr());
        $this->assertInstanceOf(DurationOverflow::class, $overflowResult->unwrapErr());
    }

    public function testCascadingOverflows(): void
    {
        $duration = Duration::new(\PHP_INT_MAX, 0);

        $mulResult = $duration->mul(2);
        $this->assertTrue($mulResult->isErr());

        $addResult = $duration->add(Duration::fromSeconds(1));
        $this->assertTrue($addResult->isErr());

        $this->assertInstanceOf(DurationOverflow::class, $mulResult->unwrapErr());
        $this->assertInstanceOf(DurationOverflow::class, $addResult->unwrapErr());
    }
}
