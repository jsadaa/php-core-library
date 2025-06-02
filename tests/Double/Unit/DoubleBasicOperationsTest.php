<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Double\Unit;

use Jsadaa\PhpCoreLibrary\Primitives\Double\Double;
use Jsadaa\PhpCoreLibrary\Primitives\Double\Error\DivisionByZero;
use PHPUnit\Framework\TestCase;

final class DoubleBasicOperationsTest extends TestCase
{
    public function testAdd(): void
    {
        $a = Double::from(5.5);
        $b = Double::from(3.2);

        $result = $a->add($b);

        $this->assertSame(8.7, $result->toFloat());
    }

    public function testAddWithNativeFloat(): void
    {
        $a = Double::from(5.5);

        $result = $a->add(3.2);

        $this->assertSame(8.7, $result->toFloat());
    }

    public function testAddWithNativeInt(): void
    {
        $a = Double::from(5.5);

        $result = $a->add(3);

        $this->assertSame(8.5, $result->toFloat());
    }

    public function testSub(): void
    {
        $a = Double::from(10.5);
        $b = Double::from(7.2);

        $result = $a->sub($b);

        $this->assertSame(3.3, $result->toFloat());
    }

    public function testSubWithNativeFloat(): void
    {
        $a = Double::from(10.5);

        $result = $a->sub(7.2);

        $this->assertSame(3.3, $result->toFloat());
    }

    public function testSubWithNativeInt(): void
    {
        $a = Double::from(10.5);

        $result = $a->sub(7);

        $this->assertSame(3.5, $result->toFloat());
    }

    public function testMul(): void
    {
        $a = Double::from(5.5);
        $b = Double::from(2.0);

        $result = $a->mul($b);

        $this->assertSame(11.0, $result->toFloat());
    }

    public function testMulWithNativeFloat(): void
    {
        $a = Double::from(5.5);

        $result = $a->mul(2.0);

        $this->assertSame(11.0, $result->toFloat());
    }

    public function testMulWithNativeInt(): void
    {
        $a = Double::from(5.5);

        $result = $a->mul(2);

        $this->assertSame(11.0, $result->toFloat());
    }

    public function testDiv(): void
    {
        $a = Double::from(10.0);
        $b = Double::from(2.0);

        $result = $a->div($b);

        $this->assertTrue($result->isOk());
        $this->assertSame(5.0, $result->unwrap()->toFloat());
    }

    public function testDivWithNativeFloat(): void
    {
        $a = Double::from(10.0);

        $result = $a->div(2.0);

        $this->assertTrue($result->isOk());
        $this->assertSame(5.0, $result->unwrap()->toFloat());
    }

    public function testDivWithNativeInt(): void
    {
        $a = Double::from(10.0);

        $result = $a->div(2);

        $this->assertTrue($result->isOk());
        $this->assertSame(5.0, $result->unwrap()->toFloat());
    }

    public function testDivByZero(): void
    {
        $a = Double::from(10.0);
        $b = Double::from(0.0);

        $result = $a->div($b);

        $this->assertTrue($result->isErr());
        $error = $result->unwrapErr();
        $this->assertInstanceOf(DivisionByZero::class, $error);
        $this->assertSame('Division by zero', $error->getMessage());
    }

    public function testRem(): void
    {
        $a = Double::from(10.5);
        $b = Double::from(3.0);

        $result = $a->rem($b);

        $this->assertSame(1.5, $result->toFloat());
    }

    public function testRemByZero(): void
    {
        $a = Double::from(10.5);
        $b = Double::from(0.0);

        $result = $a->rem($b);

        $this->assertTrue($result->isNan());
    }

    public function testAbs(): void
    {
        $positive = Double::from(5.5);
        $negative = Double::from(-5.5);
        $zero = Double::from(0.0);

        $this->assertSame(5.5, $positive->abs()->toFloat());
        $this->assertSame(5.5, $negative->abs()->toFloat());
        $this->assertSame(0.0, $zero->abs()->toFloat());
    }

    public function testAbsDiff(): void
    {
        $a = Double::from(10.5);
        $b = Double::from(15.2);
        $c = Double::from(5.3);

        $this->assertEqualsWithDelta(4.7, $a->absDiff($b)->toFloat(), 0.0001);
        $this->assertEqualsWithDelta(4.7, $b->absDiff($a)->toFloat(), 0.0001);
        $this->assertEqualsWithDelta(5.2, $a->absDiff(5.3)->toFloat(), 0.0001);
        $this->assertEqualsWithDelta(5.2, $c->absDiff(10.5)->toFloat(), 0.0001);
    }

    public function testPow(): void
    {
        $base = Double::from(2.0);

        $this->assertSame(1.0, $base->pow(0)->toFloat());
        $this->assertSame(2.0, $base->pow(1)->toFloat());
        $this->assertSame(4.0, $base->pow(2)->toFloat());
        $this->assertSame(8.0, $base->pow(3)->toFloat());
        $this->assertSame(8.0, $base->pow(Double::from(3))->toFloat());
        $this->assertSame(0.5, $base->pow(-1)->toFloat(), 'Negative exponent should work with floats');

        $fractionalExp = $base->pow(0.5);
        $this->assertEqualsWithDelta(1.4142, $fractionalExp->toFloat(), 0.0001);
    }

    public function testRound(): void
    {
        $a = Double::from(3.2);
        $b = Double::from(3.5);
        $c = Double::from(3.8);
        $d = Double::from(-3.2);
        $e = Double::from(-3.5);
        $f = Double::from(-3.8);

        $this->assertSame(3, $a->round()->toInt());
        $this->assertSame(4, $b->round()->toInt());
        $this->assertSame(4, $c->round()->toInt());
        $this->assertSame(-3, $d->round()->toInt());
        $this->assertSame(-4, $e->round()->toInt());
        $this->assertSame(-4, $f->round()->toInt());
    }

    public function testFloor(): void
    {
        $a = Double::from(3.2);
        $b = Double::from(3.8);
        $c = Double::from(-3.2);
        $d = Double::from(-3.8);

        $this->assertSame(3, $a->floor()->toInt());
        $this->assertSame(3, $b->floor()->toInt());
        $this->assertSame(-4, $c->floor()->toInt());
        $this->assertSame(-4, $d->floor()->toInt());
    }

    public function testCeil(): void
    {
        $a = Double::from(3.2);
        $b = Double::from(3.8);
        $c = Double::from(-3.2);
        $d = Double::from(-3.8);

        $this->assertSame(4, $a->ceil()->toInt());
        $this->assertSame(4, $b->ceil()->toInt());
        $this->assertSame(-3, $c->ceil()->toInt());
        $this->assertSame(-3, $d->ceil()->toInt());
    }

    public function testTrunc(): void
    {
        $a = Double::from(3.2);
        $b = Double::from(3.8);
        $c = Double::from(-3.2);
        $d = Double::from(-3.8);

        $this->assertSame(3, $a->trunc()->toInt());
        $this->assertSame(3, $b->trunc()->toInt());
        $this->assertSame(-3, $c->trunc()->toInt());
        $this->assertSame(-3, $d->trunc()->toInt());
    }

    public function testFract(): void
    {
        $a = Double::from(3.25);
        $b = Double::from(-3.25);

        $this->assertSame(0.25, $a->fract()->toFloat());
        $this->assertSame(0.75, $b->fract()->toFloat());
    }

    public function testMap(): void
    {
        $a = Double::from(5.5);

        $doubled = $a->map(static fn($x) => $x * 2);
        $this->assertSame(11.0, $doubled->toFloat());

        $squared = $a->map(static fn($x) => $x * $x);
        $this->assertSame(30.25, $squared->toFloat());

        $negated = $a->map(static fn($x) => -$x);
        $this->assertSame(-5.5, $negated->toFloat());
    }

    public function testToInt(): void
    {
        $a = Double::from(3.7);
        $b = Double::from(-3.7);

        $this->assertSame(3, $a->toInt());
        $this->assertSame(-3, $b->toInt());
    }
}
