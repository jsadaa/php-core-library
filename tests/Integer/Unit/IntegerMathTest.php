<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Integer\Unit;

use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use PHPUnit\Framework\TestCase;

final class IntegerMathTest extends TestCase
{
    public function testLog(): void
    {
        $a = Integer::of(100);

        $log = $a->log();
        $this->assertSame(4, $log->toInt(), 'floor(log(100)) should be 4');

        $log2 = $a->log(2);
        $this->assertSame(6, $log2->toInt(), 'floor(log2(100)) should be 6');

        $log10 = $a->log(10);
        $this->assertSame(2, $log10->toInt(), 'floor(log10(100)) should be 2');

        $log0 = Integer::of(0)->log();
        $this->assertSame(\PHP_INT_MIN, $log0->toInt(), 'log(0) should return PHP_INT_MIN');

        $logNegative = Integer::of(-1)->log();
        $this->assertSame(\PHP_INT_MIN, $logNegative->toInt(), 'log(-1) should return PHP_INT_MIN');

        $logInvalidBase = Integer::of(10)->log(0);
        $this->assertSame(\PHP_INT_MIN, $logInvalidBase->toInt(), 'log with base 0 should return PHP_INT_MIN');

        $logBase1 = Integer::of(10)->log(1);
        $this->assertSame(\PHP_INT_MIN, $logBase1->toInt(), 'log with base 1 should return PHP_INT_MIN');
    }

    public function testLog2(): void
    {
        $a = Integer::of(8);
        $log2 = $a->log2();
        $this->assertSame(3, $log2->toInt(), 'log2(8) should be 3');

        $b = Integer::of(9);
        $log2b = $b->log2();
        $this->assertSame(3, $log2b->toInt(), 'floor(log2(9)) should be 3');

        $log0 = Integer::of(0)->log2();
        $this->assertSame(\PHP_INT_MIN, $log0->toInt(), 'log2(0) should return PHP_INT_MIN');

        $logNegative = Integer::of(-1)->log2();
        $this->assertSame(\PHP_INT_MIN, $logNegative->toInt(), 'log2(-1) should return PHP_INT_MIN');
    }

    public function testLog10(): void
    {
        $a = Integer::of(1000);
        $log10 = $a->log10();
        $this->assertSame(3, $log10->toInt(), 'log10(1000) should be 3');

        $b = Integer::of(999);
        $log10b = $b->log10();
        $this->assertSame(2, $log10b->toInt(), 'floor(log10(999)) should be 2');

        $log0 = Integer::of(0)->log10();
        $this->assertSame(\PHP_INT_MIN, $log0->toInt(), 'log10(0) should return PHP_INT_MIN');

        $logNegative = Integer::of(-1)->log10();
        $this->assertSame(\PHP_INT_MIN, $logNegative->toInt(), 'log10(-1) should return PHP_INT_MIN');
    }

    public function testSqrt(): void
    {
        $a = Integer::of(16);
        $sqrt = $a->sqrt();
        $this->assertSame(4, $sqrt->toInt());

        $b = Integer::of(10);
        $sqrtb = $b->sqrt();
        $this->assertSame(3, $sqrtb->toInt(), 'floor(sqrt(10)) should be 3');

        $negative = Integer::of(-4);
        $sqrtNegative = $negative->sqrt();
        $this->assertSame(\PHP_INT_MIN, $sqrtNegative->toInt(), 'sqrt of negative number should return PHP_INT_MIN');

        $zero = Integer::of(0);
        $sqrtZero = $zero->sqrt();
        $this->assertSame(0, $sqrtZero->toInt());
    }

    public function testMap(): void
    {
        $a = Integer::of(5);

        $doubled = $a->map(static fn($x) => $x * 2);
        $this->assertSame(10, $doubled->toInt());

        $squared = $a->map(static fn($x) => $x * $x);
        $this->assertSame(25, $squared->toInt());

        $negated = $a->map(static fn($x) => -$x);
        $this->assertSame(-5, $negated->toInt());

        $incremented = $a->map(static fn($x) => $x + 1);
        $this->assertSame(6, $incremented->toInt());

        $complex = $a->map(static fn($x) => ($x * 2) + 3);
        $this->assertSame(13, $complex->toInt());
    }
}
