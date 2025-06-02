<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Integer\Unit;

use Jsadaa\PhpCoreLibrary\Primitives\Integer\Error\IntegerOverflow;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use PHPUnit\Framework\TestCase;

final class IntegerEdgeCaseTest extends TestCase
{
    public function testEdgeCaseAddZero(): void
    {
        $a = Integer::from(42);
        $zero = Integer::from(0);

        $result = $a->add($zero);

        $this->assertSame(42, $result->toInt());
        $this->assertNotSame($a, $result, 'Even adding zero should return a new instance');
    }

    public function testEdgeCaseSubtractSelf(): void
    {
        $a = Integer::from(42);

        $result = $a->sub($a);

        $this->assertSame(0, $result->toInt());
    }

    public function testEdgeCaseMultiplyByZero(): void
    {
        $a = Integer::from(42);
        $zero = Integer::from(0);

        $result = $a->mul($zero);

        $this->assertSame(0, $result->toInt());
    }

    public function testEdgeCaseMultiplyByOne(): void
    {
        $a = Integer::from(42);
        $one = Integer::from(1);

        $result = $a->mul($one);

        $this->assertSame(42, $result->toInt());
        $this->assertNotSame($a, $result, 'Even multiplying by one should return a new instance');
    }

    public function testEdgeCaseDivideBySelf(): void
    {
        $a = Integer::from(42);

        $result = $a->div($a);

        $this->assertTrue($result->isOk());
        $this->assertSame(1, $result->unwrap()->toInt());
    }

    public function testEdgeCaseDivideZeroByNumber(): void
    {
        $zero = Integer::from(0);
        $a = Integer::from(42);

        $result = $zero->div($a);

        $this->assertTrue($result->isOk());
        $this->assertSame(0, $result->unwrap()->toInt());
    }

    public function testEdgeCaseDivideAtIntegerBoundary(): void
    {
        $min = Integer::from(\PHP_INT_MIN);
        $negOne = Integer::from(-1);

        $result = $min->div($negOne);

        $this->assertEquals(\PHP_INT_MIN, $result->unwrap()->toInt(), 'Dividing INT_MIN by -1 should yield INT_MAX');
    }

    public function testEdgeCaseAbsOfMinInt(): void
    {
        $min = Integer::from(\PHP_INT_MIN);

        // In many languages, abs(INT_MIN) would overflow because |INT_MIN| > INT_MAX
        // But PHP handles this differently, casting to float temporarily, so we return INT_MAX
        $result = $min->abs();

        $this->assertEquals(\PHP_INT_MAX, $result->toInt(), 'abs(INT_MIN) should yield INT_MAX');
    }

    public function testEdgeCasePowWithNegativeBase(): void
    {
        $negative = Integer::from(-2);

        $pow2 = $negative->pow(2);
        $pow3 = $negative->pow(3);

        $this->assertSame(4, $pow2->toInt(), '(-2)^2 should be 4');
        $this->assertSame(-8, $pow3->toInt(), '(-2)^3 should be -8');
    }

    public function testEdgeCasePowWithZeroBase(): void
    {
        $zero = Integer::from(0);

        $pow0 = $zero->pow(0);
        $pow1 = $zero->pow(1);
        $pow2 = $zero->pow(2);

        $this->assertSame(1, $pow0->toInt(), '0^0 should be 1 (mathematical convention)');
        $this->assertSame(0, $pow1->toInt(), '0^1 should be 0');
        $this->assertSame(0, $pow2->toInt(), '0^2 should be 0');
    }

    public function testEdgeCaseSqrtOfPerfectSquare(): void
    {
        $a = Integer::from(144);

        $result = $a->sqrt();

        $this->assertSame(12, $result->toInt());
    }

    public function testEdgeCaseSqrtOfOne(): void
    {
        $one = Integer::from(1);

        $result = $one->sqrt();

        $this->assertSame(1, $result->toInt());
    }

    public function testEdgeCaseSqrtOfZero(): void
    {
        $zero = Integer::from(0);

        $result = $zero->sqrt();

        $this->assertSame(0, $result->toInt());
    }

    public function testEdgeCaseOverflowingAddNearBoundary(): void
    {
        $nearMax = Integer::from(\PHP_INT_MAX - 10);
        $small = Integer::from(5);

        $result = $nearMax->overflowingAdd($small);

        $this->assertTrue($result->isOk());
        $this->assertSame(\PHP_INT_MAX - 5, $result->unwrap()->toInt());

        $large = Integer::from(15);
        $overflow = $nearMax->overflowingAdd($large);

        $this->assertTrue($overflow->isErr());
        $this->assertInstanceOf(IntegerOverflow::class, $overflow->unwrapErr());
    }

    public function testEdgeCaseSaturatingAddNearBoundary(): void
    {
        $nearMax = Integer::from(\PHP_INT_MAX - 10);
        $small = Integer::from(5);
        $large = Integer::from(15);

        $result1 = $nearMax->saturatingAdd($small);
        $this->assertSame(\PHP_INT_MAX - 5, $result1->toInt());

        $result2 = $nearMax->saturatingAdd($large);
        $this->assertSame(\PHP_INT_MAX, $result2->toInt(), 'Should saturate at PHP_INT_MAX');
    }

    public function testEdgeCaseOverflowingSubNearBoundary(): void
    {
        $nearMin = Integer::from(\PHP_INT_MIN + 10);
        $small = Integer::from(5);

        $result = $nearMin->overflowingSub($small);

        $this->assertTrue($result->isOk());
        $this->assertSame(\PHP_INT_MIN + 5, $result->unwrap()->toInt());

        $large = Integer::from(15);
        $overflow = $nearMin->overflowingSub($large);

        $this->assertTrue($overflow->isErr());
        $this->assertInstanceOf(IntegerOverflow::class, $overflow->unwrapErr());
    }

    public function testEdgeCaseSaturatingSubNearBoundary(): void
    {
        $nearMin = Integer::from(\PHP_INT_MIN + 10);
        $small = Integer::from(5);
        $large = Integer::from(15);

        $result1 = $nearMin->saturatingSub($small);
        $this->assertSame(\PHP_INT_MIN + 5, $result1->toInt());

        $result2 = $nearMin->saturatingSub($large);
        $this->assertSame(\PHP_INT_MIN, $result2->toInt(), 'Should saturate at PHP_INT_MIN');
    }

    public function testEdgeCaseClampWithInvertedBoundaries(): void
    {
        $a = Integer::from(15);

        $result = $a->clamp(20, 10);

        $this->assertTrue($result->toInt() === 20);
    }

    public function testEdgeCaseMapReturnsMaxInt(): void
    {
        $a = Integer::from(5);

        $result = $a->map(static fn() => \PHP_INT_MAX);

        $this->assertSame(\PHP_INT_MAX, $result->toInt());
    }

    public function testEdgeCaseMapReturnsMinInt(): void
    {
        $a = Integer::from(5);

        $result = $a->map(static fn() => \PHP_INT_MIN);

        $this->assertSame(\PHP_INT_MIN, $result->toInt());
    }
}
