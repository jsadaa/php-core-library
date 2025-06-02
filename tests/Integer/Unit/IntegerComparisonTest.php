<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Integer\Unit;

use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use PHPUnit\Framework\TestCase;

final class IntegerComparisonTest extends TestCase
{
    public function testEq(): void
    {
        $a = Integer::from(42);
        $b = Integer::from(42);
        $c = Integer::from(10);

        $this->assertTrue($a->eq($b));
        $this->assertTrue($a->eq(42));
        $this->assertFalse($a->eq($c));
        $this->assertFalse($a->eq(10));
    }

    public function testCmp(): void
    {
        $a = Integer::from(10);
        $b = Integer::from(20);
        $c = Integer::from(10);

        $this->assertSame(-1, $a->cmp($b)->toInt(), 'a < b should return -1');
        $this->assertSame(1, $b->cmp($a)->toInt(), 'b > a should return 1');
        $this->assertSame(0, $a->cmp($c)->toInt(), 'a == c should return 0');

        $this->assertSame(-1, $a->cmp(20)->toInt(), 'a < 20 should return -1');
        $this->assertSame(1, $b->cmp(10)->toInt(), 'b > 10 should return 1');
        $this->assertSame(0, $a->cmp(10)->toInt(), 'a == 10 should return 0');
    }

    public function testMin(): void
    {
        $a = Integer::from(10);
        $b = Integer::from(20);

        $this->assertSame(10, $a->min($b)->toInt());
        $this->assertSame(10, $b->min($a)->toInt());
        $this->assertSame(10, $a->min(20)->toInt());
        $this->assertSame(10, $b->min(10)->toInt());
    }

    public function testMax(): void
    {
        $a = Integer::from(10);
        $b = Integer::from(20);

        $this->assertSame(20, $a->max($b)->toInt());
        $this->assertSame(20, $b->max($a)->toInt());
        $this->assertSame(20, $a->max(20)->toInt());
        $this->assertSame(20, $b->max(10)->toInt());
    }

    public function testClamp(): void
    {
        $a = Integer::from(15);

        $this->assertSame(15, $a->clamp(10, 20)->toInt(), 'Value within range should remain unchanged');
        $this->assertSame(12, $a->clamp(10, 12)->toInt(), 'Value above max should be clamped to max');
        $this->assertSame(20, $a->clamp(20, 30)->toInt(), 'Value below min should be clamped to min');

        $this->assertSame(15, $a->clamp(Integer::from(10), Integer::from(20))->toInt());
        $this->assertSame(12, $a->clamp(Integer::from(10), Integer::from(12))->toInt());
        $this->assertSame(20, $a->clamp(Integer::from(20), Integer::from(30))->toInt());
    }

    public function testIsPositive(): void
    {
        $positive = Integer::from(10);
        $zero = Integer::from(0);
        $negative = Integer::from(-10);

        $this->assertTrue($positive->isPositive());
        $this->assertFalse($zero->isPositive());
        $this->assertFalse($negative->isPositive());
    }

    public function testIsNegative(): void
    {
        $positive = Integer::from(10);
        $zero = Integer::from(0);
        $negative = Integer::from(-10);

        $this->assertFalse($positive->isNegative());
        $this->assertFalse($zero->isNegative());
        $this->assertTrue($negative->isNegative());
    }

    public function testIsEven(): void
    {
        $even = Integer::from(10);
        $odd = Integer::from(9);
        $zero = Integer::from(0);
        $negativeEven = Integer::from(-4);
        $negativeOdd = Integer::from(-3);

        $this->assertTrue($even->isEven());
        $this->assertFalse($odd->isEven());
        $this->assertTrue($zero->isEven());
        $this->assertTrue($negativeEven->isEven());
        $this->assertFalse($negativeOdd->isEven());
    }

    public function testIsOdd(): void
    {
        $even = Integer::from(10);
        $odd = Integer::from(9);
        $zero = Integer::from(0);
        $negativeEven = Integer::from(-4);
        $negativeOdd = Integer::from(-3);

        $this->assertFalse($even->isOdd());
        $this->assertTrue($odd->isOdd());
        $this->assertFalse($zero->isOdd());
        $this->assertFalse($negativeEven->isOdd());
        $this->assertTrue($negativeOdd->isOdd());
    }

    public function testIsMultipleOf(): void
    {
        $a = Integer::from(10);

        $this->assertTrue($a->isMultipleOf(1));
        $this->assertTrue($a->isMultipleOf(2));
        $this->assertFalse($a->isMultipleOf(3));
        $this->assertTrue($a->isMultipleOf(5));
        $this->assertTrue($a->isMultipleOf(10));
        $this->assertFalse($a->isMultipleOf(20));

        $this->assertTrue($a->isMultipleOf(Integer::from(5)));
        $this->assertFalse($a->isMultipleOf(Integer::from(3)));

        $zero = Integer::from(0);
        $this->assertTrue($zero->isMultipleOf(5), 'Zero is a multiple of any number');
    }

    public function testSignum(): void
    {
        $positive = Integer::from(42);
        $zero = Integer::from(0);
        $negative = Integer::from(-42);

        $this->assertSame(1, $positive->signum()->toInt());
        $this->assertSame(0, $zero->signum()->toInt());
        $this->assertSame(-1, $negative->signum()->toInt());
    }

    public function testGreaterThan(): void
    {
        $a = Integer::from(10);
        $b = Integer::from(20);
        $c = Integer::from(10);

        $this->assertTrue($b->gt($a));
        $this->assertFalse($a->gt($b));
        $this->assertFalse($a->gt($c));
    }

    public function testLessThan(): void
    {
        $a = Integer::from(10);
        $b = Integer::from(20);
        $c = Integer::from(10);

        $this->assertTrue($a->lt($b));
        $this->assertFalse($b->lt($a));
        $this->assertFalse($a->lt($c));
    }

    public function testGreaterThanOrEqual(): void
    {
        $a = Integer::from(10);
        $b = Integer::from(20);
        $c = Integer::from(10);

        $this->assertTrue($b->ge($a));
        $this->assertFalse($a->ge($b));
        $this->assertTrue($a->ge($c));
    }

    public function testLessThanOrEqual(): void
    {
        $a = Integer::from(10);
        $b = Integer::from(20);
        $c = Integer::from(10);

        $this->assertTrue($a->le($b));
        $this->assertFalse($b->le($a));
        $this->assertTrue($a->le($c));
    }
}
