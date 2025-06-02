<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Double\Unit;

use Jsadaa\PhpCoreLibrary\Primitives\Double\Double;
use PHPUnit\Framework\TestCase;

final class DoubleEdgeCaseTest extends TestCase
{
    public function testEdgeCaseAddZero(): void
    {
        $a = Double::from(42.5);
        $zero = Double::from(0.0);

        $result = $a->add($zero);

        $this->assertSame(42.5, $result->toFloat());
        $this->assertNotSame($a, $result, 'Even adding zero should return a new instance');
    }

    public function testEdgeCaseSubtractSelf(): void
    {
        $a = Double::from(42.5);

        $result = $a->sub($a);

        $this->assertSame(0.0, $result->toFloat());
    }

    public function testEdgeCaseMultiplyByZero(): void
    {
        $a = Double::from(42.5);
        $zero = Double::from(0.0);

        $result = $a->mul($zero);

        $this->assertSame(0.0, $result->toFloat());
    }

    public function testEdgeCaseMultiplyByOne(): void
    {
        $a = Double::from(42.5);
        $one = Double::from(1.0);

        $result = $a->mul($one);

        $this->assertSame(42.5, $result->toFloat());
        $this->assertNotSame($a, $result, 'Even multiplying by one should return a new instance');
    }

    public function testEdgeCaseDivideBySelf(): void
    {
        $a = Double::from(42.5);

        $result = $a->div($a);

        $this->assertTrue($result->isOk());
        $this->assertSame(1.0, $result->unwrap()->toFloat());
    }

    public function testEdgeCaseDivideZeroByNumber(): void
    {
        $zero = Double::from(0.0);
        $a = Double::from(42.5);

        $result = $zero->div($a);

        $this->assertTrue($result->isOk());
        $this->assertSame(0.0, $result->unwrap()->toFloat());
    }

    public function testEdgeCasePowWithNegativeBase(): void
    {
        $negative = Double::from(-2.0);

        $pow2 = $negative->pow(2.0);
        $pow3 = $negative->pow(3.0);

        $this->assertSame(4.0, $pow2->toFloat(), '(-2)^2 should be 4');
        $this->assertSame(-8.0, $pow3->toFloat(), '(-2)^3 should be -8');
    }

    public function testEdgeCasePowWithZeroBase(): void
    {
        $zero = Double::from(0.0);

        $pow0 = $zero->pow(0.0);
        $pow1 = $zero->pow(1.0);
        $pow2 = $zero->pow(2.0);

        $this->assertSame(1.0, $pow0->toFloat(), '0^0 should be 1 (mathematical convention)');
        $this->assertSame(0.0, $pow1->toFloat(), '0^1 should be 0');
        $this->assertSame(0.0, $pow2->toFloat(), '0^2 should be 0');
    }

    public function testEdgeCaseSqrtOfPerfectSquare(): void
    {
        $a = Double::from(144.0);

        $result = $a->sqrt();

        $this->assertSame(12.0, $result->toFloat());
    }

    public function testEdgeCaseSqrtOfOne(): void
    {
        $one = Double::from(1.0);

        $result = $one->sqrt();

        $this->assertSame(1.0, $result->toFloat());
    }

    public function testEdgeCaseSqrtOfZero(): void
    {
        $zero = Double::from(0.0);

        $result = $zero->sqrt();

        $this->assertSame(0.0, $result->toFloat());
    }

    public function testEdgeCaseClampWithInvertedBoundaries(): void
    {
        $a = Double::from(15.5);

        $result = $a->clamp(20.5, 10.5);

        $this->assertSame(15.5, $result->toFloat(), 'Clamping with swapped boundaries should work correctly');

        $b = Double::from(5.5);
        $resultB = $b->clamp(20.5, 10.5);

        $this->assertSame(10.5, $resultB->toFloat(), 'Value below both boundaries should be clamped to min');
    }

    public function testEdgeCaseMapReturnsMaxFloat(): void
    {
        $a = Double::from(5.5);

        $result = $a->map(static fn() => \PHP_FLOAT_MAX);

        $this->assertSame(\PHP_FLOAT_MAX, $result->toFloat());
    }

    public function testEdgeCaseMapReturnsMinFloat(): void
    {
        $a = Double::from(5.5);

        $result = $a->map(static fn() => -\PHP_FLOAT_MAX);

        $this->assertSame(-\PHP_FLOAT_MAX, $result->toFloat());
    }

    public function testInfinityAndNaNOperations(): void
    {
        $inf = Double::infinity();
        $negInf = Double::negInfinity();
        $nan = Double::nan();
        $regular = Double::from(42.5);

        $this->assertTrue($inf->add($regular)->isInfinite());
        $this->assertTrue($inf->add($regular)->toFloat() > 0);
        $this->assertTrue($negInf->add($regular)->isInfinite());
        $this->assertTrue($negInf->add($regular)->toFloat() < 0);

        $this->assertTrue($inf->add($inf)->isInfinite());
        $this->assertTrue($negInf->add($negInf)->isInfinite());
        $this->assertTrue($negInf->add($negInf)->toFloat() < 0);

        $this->assertTrue($inf->add($negInf)->isNan());

        $this->assertTrue($nan->add($regular)->isNan());
        $this->assertTrue($nan->sub($regular)->isNan());
        $this->assertTrue($nan->mul($regular)->isNan());
        $this->assertTrue($nan->div($regular)->unwrap()->isNan());

        $this->assertTrue($regular->div(0)->isErr());

        $this->assertFalse($nan->eq($nan), 'NaN should not equal itself');
        $this->assertFalse($nan->lt($regular));
        $this->assertFalse($nan->gt($regular));
        $this->assertFalse($regular->lt($nan));
        $this->assertFalse($regular->gt($nan));
    }

    public function testApproxEqPrecision(): void
    {
        $a = Double::from(0.1);
        $b = Double::from(0.3);
        $sum = $a->add($a)->add($a); // 0.1 + 0.1 + 0.1

        $this->assertFalse($sum->eq($b), '0.1 + 0.1 + 0.1 should not exactly equal 0.3 due to floating point precision');
        $this->assertTrue($sum->approxEq($b), '0.1 + 0.1 + 0.1 should approximately equal 0.3');

        $tiny1 = Double::from(1e-16);
        $tiny2 = Double::from(1.0000000000000001e-16);

        $this->assertFalse($tiny1->eq($tiny2), 'Small differences should be detectable with exact equality');
        $this->assertTrue($tiny1->approxEq($tiny2), 'Small differences should be ignored with approximate equality');
    }

    public function testErrorHandling(): void
    {
        $invalidLog = Double::from(-1.0)->ln();
        $this->assertTrue($invalidLog->isNan());

        $invalidLog2 = Double::from(-1.0)->log2();
        $this->assertTrue($invalidLog2->isNan());

        $invalidAsin = Double::from(2.0)->asin();
        $this->assertTrue($invalidAsin->isNan());

        $invalidAcos = Double::from(-2.0)->acos();
        $this->assertTrue($invalidAcos->isNan());
    }
}
