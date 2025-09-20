<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Integer\Unit;

use Jsadaa\PhpCoreLibrary\Primitives\Integer\Error\DivisionByZero;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Error\IntegerOverflow;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use PHPUnit\Framework\TestCase;

final class IntegerOverflowTest extends TestCase
{
    public function testOverflowingAdd(): void
    {
        $a = Integer::of(5);
        $b = Integer::of(3);

        $result = $a->overflowingAdd($b);

        $this->assertTrue($result->isOk());
        $this->assertSame(8, $result->unwrap()->toInt());
    }

    public function testOverflowingAddWithMaxInt(): void
    {
        $max = Integer::of(\PHP_INT_MAX);
        $one = Integer::of(1);

        $result = $max->overflowingAdd($one);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(IntegerOverflow::class, $result->unwrapErr());
    }

    public function testOverflowingSub(): void
    {
        $a = Integer::of(10);
        $b = Integer::of(7);

        $result = $a->overflowingSub($b);

        $this->assertTrue($result->isOk());
        $this->assertSame(3, $result->unwrap()->toInt());
    }

    public function testOverflowingSubWithMinInt(): void
    {
        $min = Integer::of(\PHP_INT_MIN);
        $one = Integer::of(1);

        $result = $min->overflowingSub($one);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(IntegerOverflow::class, $result->unwrapErr());
    }

    public function testOverflowingMul(): void
    {
        $a = Integer::of(5);
        $b = Integer::of(4);

        $result = $a->overflowingMul($b);

        $this->assertTrue($result->isOk());
        $this->assertSame(20, $result->unwrap()->toInt());
    }

    public function testOverflowingMulWithLargeValues(): void
    {
        // A value close to sqrt(PHP_INT_MAX)
        $largeValue = Integer::of((int)\sqrt(\PHP_INT_MAX) + 1);

        $result = $largeValue->overflowingMul($largeValue);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(IntegerOverflow::class, $result->unwrapErr());
    }

    public function testOverflowingDiv(): void
    {
        $a = Integer::of(10);
        $b = Integer::of(2);

        $result = $a->overflowingDiv($b);

        $this->assertTrue($result->isOk());
        $this->assertSame(5, $result->unwrap()->toInt());
    }

    public function testOverflowingDivByZero(): void
    {
        $a = Integer::of(10);
        $zero = Integer::of(0);

        $result = $a->overflowingDiv($zero);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(DivisionByZero::class, $result->unwrapErr());
        $this->assertSame('Division by zero', $result->unwrapErr()->getMessage());
    }

    public function testSaturatingAdd(): void
    {
        $a = Integer::of(5);
        $b = Integer::of(3);

        $result = $a->saturatingAdd($b);

        $this->assertSame(8, $result->toInt());
    }

    public function testSaturatingAddWithMaxInt(): void
    {
        $max = Integer::of(\PHP_INT_MAX);
        $one = Integer::of(1);

        $result = $max->saturatingAdd($one);

        $this->assertSame(\PHP_INT_MAX, $result->toInt(), 'Should saturate at PHP_INT_MAX');
    }

    public function testSaturatingSub(): void
    {
        $a = Integer::of(10);
        $b = Integer::of(7);

        $result = $a->saturatingSub($b);

        $this->assertSame(3, $result->toInt());
    }

    public function testSaturatingSubWithMinInt(): void
    {
        $min = Integer::of(\PHP_INT_MIN);
        $one = Integer::of(1);

        $result = $min->saturatingSub($one);

        $this->assertSame(\PHP_INT_MIN, $result->toInt(), 'Should saturate at PHP_INT_MIN');
    }

    public function testSaturatingMul(): void
    {
        $a = Integer::of(5);
        $b = Integer::of(4);

        $result = $a->saturatingMul($b);

        $this->assertSame(20, $result->toInt());
    }

    public function testSaturatingMulWithLargeValues(): void
    {
        // A value close to sqrt(PHP_INT_MAX)
        $largeValue = Integer::of((int)\sqrt(\PHP_INT_MAX) + 1);

        $result = $largeValue->saturatingMul($largeValue);

        $this->assertSame(\PHP_INT_MAX, $result->toInt(), 'Should saturate at PHP_INT_MAX');
    }

    public function testSaturatingDiv(): void
    {
        $a = Integer::of(10);
        $b = Integer::of(2);

        $result = $a->saturatingDiv($b);

        $this->assertTrue($result->isOk());
        $this->assertSame(5, $result->unwrap()->toInt());
    }

    public function testSaturatingDivByZero(): void
    {
        $a = Integer::of(10);
        $zero = Integer::of(0);

        $result = $a->saturatingDiv($zero);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(DivisionByZero::class, $result->unwrapErr());
        $this->assertSame('Division by zero', $result->unwrapErr()->getMessage());
    }
}
