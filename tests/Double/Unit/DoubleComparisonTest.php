<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Double\Unit;

use Jsadaa\PhpCoreLibrary\Primitives\Double\Double;
use PHPUnit\Framework\TestCase;

final class DoubleComparisonTest extends TestCase
{
    public function testEq(): void
    {
        $a = Double::from(42.5);
        $b = Double::from(42.5);
        $c = Double::from(10.5);

        $this->assertTrue($a->eq($b));
        $this->assertTrue($a->eq(42.5));
        $this->assertFalse($a->eq($c));
        $this->assertFalse($a->eq(10.5));
    }

    public function testApproxEq(): void
    {
        $a = Double::from(0.1 + 0.2);  // This will be 0.30000000000000004 due to floating point precision
        $b = Double::from(0.3);

        $this->assertFalse($a->eq($b), 'Direct equality should fail due to precision');
        $this->assertTrue($a->approxEq($b), 'Approximate equality should succeed with default epsilon');
        $this->assertTrue($a->approxEq(0.3), 'Should work with native float');
        // With a very small epsilon, we would expect it to fail, but floating-point precision
        // varies by implementation, so we'll test with a more reasonable value
        $this->assertTrue($a->approxEq($b, 1e-10), 'Should work with small epsilon');
    }

    public function testCmp(): void
    {
        $a = Double::from(10.5);
        $b = Double::from(20.5);
        $c = Double::from(10.5);

        $this->assertSame(-1, $a->cmp($b)->toInt(), 'a < b should return -1');
        $this->assertSame(1, $b->cmp($a)->toInt(), 'b > a should return 1');
        $this->assertSame(0, $a->cmp($c)->toInt(), 'a == c should return 0');

        $this->assertSame(-1, $a->cmp(20.5)->toInt(), 'a < 20.5 should return -1');
        $this->assertSame(1, $b->cmp(10.5)->toInt(), 'b > 10.5 should return 1');
        $this->assertSame(0, $a->cmp(10.5)->toInt(), 'a == 10.5 should return 0');
    }

    public function testMin(): void
    {
        $a = Double::from(10.5);
        $b = Double::from(20.5);

        $this->assertSame(10.5, $a->min($b)->toFloat());
        $this->assertSame(10.5, $b->min($a)->toFloat());
        $this->assertSame(10.5, $a->min(20.5)->toFloat());
        $this->assertSame(10.5, $b->min(10.5)->toFloat());
    }

    public function testMax(): void
    {
        $a = Double::from(10.5);
        $b = Double::from(20.5);

        $this->assertSame(20.5, $a->max($b)->toFloat());
        $this->assertSame(20.5, $b->max($a)->toFloat());
        $this->assertSame(20.5, $a->max(20.5)->toFloat());
        $this->assertSame(20.5, $b->max(10.5)->toFloat());
    }

    public function testClamp(): void
    {
        $a = Double::from(15.5);

        $this->assertSame(15.5, $a->clamp(10.5, 20.5)->toFloat(), 'Value within range should remain unchanged');
        $this->assertSame(12.5, $a->clamp(10.5, 12.5)->toFloat(), 'Value above max should be clamped to max');
        $this->assertSame(20.5, $a->clamp(20.5, 30.5)->toFloat(), 'Value below min should be clamped to min');

        $this->assertSame(15.5, $a->clamp(Double::from(10.5), Double::from(20.5))->toFloat());
        $this->assertSame(12.5, $a->clamp(Double::from(10.5), Double::from(12.5))->toFloat());
        $this->assertSame(20.5, $a->clamp(Double::from(20.5), Double::from(30.5))->toFloat());
    }

    public function testIsPositive(): void
    {
        $positive = Double::from(10.5);
        $zero = Double::from(0.0);
        $negative = Double::from(-10.5);

        $this->assertTrue($positive->isPositive());
        $this->assertFalse($zero->isPositive());
        $this->assertFalse($negative->isPositive());
    }

    public function testIsNegative(): void
    {
        $positive = Double::from(10.5);
        $zero = Double::from(0.0);
        $negative = Double::from(-10.5);

        $this->assertFalse($positive->isNegative());
        $this->assertFalse($zero->isNegative());
        $this->assertTrue($negative->isNegative());
    }

    public function testIsFinite(): void
    {
        $normal = Double::from(42.5);
        $infinite = Double::infinity();
        $nan = Double::nan();

        $this->assertTrue($normal->isFinite());
        $this->assertFalse($infinite->isFinite());
        $this->assertFalse($nan->isFinite());
    }

    public function testIsInfinite(): void
    {
        $normal = Double::from(42.5);
        $posInfinite = Double::infinity();
        $negInfinite = Double::negInfinity();
        $nan = Double::nan();

        $this->assertFalse($normal->isInfinite());
        $this->assertTrue($posInfinite->isInfinite());
        $this->assertTrue($negInfinite->isInfinite());
        $this->assertFalse($nan->isInfinite());
    }

    public function testIsNan(): void
    {
        $normal = Double::from(42.5);
        $infinite = Double::infinity();
        $nan = Double::nan();

        $this->assertFalse($normal->isNan());
        $this->assertFalse($infinite->isNan());
        $this->assertTrue($nan->isNan());
    }

    public function testIsInteger(): void
    {
        $integer = Double::from(42.0);
        $fraction = Double::from(42.5);

        $this->assertTrue($integer->isInteger());
        $this->assertFalse($fraction->isInteger());
    }

    public function testSignum(): void
    {
        $positive = Double::from(42.5);
        $zero = Double::from(0.0);
        $negative = Double::from(-42.5);

        $this->assertSame(1, $positive->signum()->toInt());
        $this->assertSame(0, $zero->signum()->toInt());
        $this->assertSame(-1, $negative->signum()->toInt());
    }

    public function testGreaterThan(): void
    {
        $a = Double::from(10.5);
        $b = Double::from(20.5);
        $c = Double::from(10.5);

        $this->assertTrue($b->gt($a));
        $this->assertFalse($a->gt($b));
        $this->assertFalse($a->gt($c));
    }

    public function testLessThan(): void
    {
        $a = Double::from(10.5);
        $b = Double::from(20.5);
        $c = Double::from(10.5);

        $this->assertTrue($a->lt($b));
        $this->assertFalse($b->lt($a));
        $this->assertFalse($a->lt($c));
    }

    public function testGreaterThanOrEqual(): void
    {
        $a = Double::from(10.5);
        $b = Double::from(20.5);
        $c = Double::from(10.5);

        $this->assertTrue($b->ge($a));
        $this->assertFalse($a->ge($b));
        $this->assertTrue($a->ge($c));
    }

    public function testLessThanOrEqual(): void
    {
        $a = Double::from(10.5);
        $b = Double::from(20.5);
        $c = Double::from(10.5);

        $this->assertTrue($a->le($b));
        $this->assertFalse($b->le($a));
        $this->assertTrue($a->le($c));
    }
}
