<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Integer\Unit;

use Jsadaa\PhpCoreLibrary\Primitives\Integer\Error\DivisionByZero;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use PHPUnit\Framework\TestCase;

final class IntegerBasicOperationsTest extends TestCase
{
    public function testAdd(): void
    {
        $a = Integer::from(5);
        $b = Integer::from(3);

        $result = $a->add($b);

        $this->assertSame(8, $result->toInt());
    }

    public function testAddWithNativeInt(): void
    {
        $a = Integer::from(5);

        $result = $a->add(3);

        $this->assertSame(8, $result->toInt());
    }

    public function testSub(): void
    {
        $a = Integer::from(10);
        $b = Integer::from(7);

        $result = $a->sub($b);

        $this->assertSame(3, $result->toInt());
    }

    public function testSubWithNativeInt(): void
    {
        $a = Integer::from(10);

        $result = $a->sub(7);

        $this->assertSame(3, $result->toInt());
    }

    public function testMul(): void
    {
        $a = Integer::from(5);
        $b = Integer::from(4);

        $result = $a->mul($b);

        $this->assertSame(20, $result->toInt());
    }

    public function testMulWithNativeInt(): void
    {
        $a = Integer::from(5);

        $result = $a->mul(4);

        $this->assertSame(20, $result->toInt());
    }

    public function testDiv(): void
    {
        $a = Integer::from(10);
        $b = Integer::from(2);

        $result = $a->div($b);

        $this->assertTrue($result->isOk());
        $this->assertSame(5, $result->unwrap()->toInt());
    }

    public function testDivWithNativeInt(): void
    {
        $a = Integer::from(10);

        $result = $a->div(2);

        $this->assertTrue($result->isOk());
        $this->assertSame(5, $result->unwrap()->toInt());
    }

    public function testDivByZero(): void
    {
        $a = Integer::from(10);
        $b = Integer::from(0);

        $result = $a->div($b);

        $this->assertTrue($result->isErr());
        $error = $result->unwrapErr();
        $this->assertInstanceOf(DivisionByZero::class, $error);
        $this->assertSame('Division by zero', $error->getMessage());
    }

    public function testDivWithRounding(): void
    {
        $a = Integer::from(10);
        $b = Integer::from(3);

        $result = $a->div($b);

        $this->assertTrue($result->isOk());
        $this->assertSame(3, $result->unwrap()->toInt(), 'Integer division should truncate toward zero');
    }

    public function testDivFloor(): void
    {
        $a = Integer::from(10);
        $b = Integer::from(3);

        $result = $a->divFloor($b);

        $this->assertTrue($result->isOk());
        $this->assertSame(3, $result->unwrap()->toInt(), 'Floor division with positive numbers');

        $negativeA = Integer::from(-10);
        $resultNegative = $negativeA->divFloor($b);

        $this->assertTrue($resultNegative->isOk());
        $this->assertSame(-4, $resultNegative->unwrap()->toInt(), 'Floor division with negative dividend should round toward negative infinity');
    }

    public function testDivCeil(): void
    {
        $a = Integer::from(10);
        $b = Integer::from(3);

        $result = $a->divCeil($b);

        $this->assertTrue($result->isOk());
        $this->assertSame(4, $result->unwrap()->toInt(), 'Ceiling division with positive numbers');

        $negativeA = Integer::from(-10);
        $resultNegative = $negativeA->divCeil($b);

        $this->assertTrue($resultNegative->isOk());
        $this->assertSame(-3, $resultNegative->unwrap()->toInt(), 'Ceiling division with negative dividend should round toward zero');
    }

    public function testAbs(): void
    {
        $positive = Integer::from(5);
        $negative = Integer::from(-5);
        $zero = Integer::from(0);

        $this->assertSame(5, $positive->abs()->toInt());
        $this->assertSame(5, $negative->abs()->toInt());
        $this->assertSame(0, $zero->abs()->toInt());
    }

    public function testAbsDiff(): void
    {
        $a = Integer::from(10);
        $b = Integer::from(15);
        $c = Integer::from(5);

        $this->assertSame(5, $a->absDiff($b)->toInt());
        $this->assertSame(5, $b->absDiff($a)->toInt());
        $this->assertSame(5, $a->absDiff(5)->toInt());
        $this->assertSame(5, $c->absDiff(10)->toInt());
    }

    public function testPow(): void
    {
        $base = Integer::from(2);

        $this->assertSame(1, $base->pow(0)->toInt());
        $this->assertSame(2, $base->pow(1)->toInt());
        $this->assertSame(4, $base->pow(2)->toInt());
        $this->assertSame(8, $base->pow(3)->toInt());
        $this->assertSame(8, $base->pow(Integer::from(3))->toInt());

        $this->assertSame(0, $base->pow(-1)->toInt());
    }
}
