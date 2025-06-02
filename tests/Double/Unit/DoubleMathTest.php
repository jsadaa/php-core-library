<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Double\Unit;

use Jsadaa\PhpCoreLibrary\Primitives\Double\Double;
use PHPUnit\Framework\TestCase;

final class DoubleMathTest extends TestCase
{
    public function testLn(): void
    {
        $a = Double::from(100.0);

        $ln = $a->ln();
        $this->assertEqualsWithDelta(4.605, $ln->toFloat(), 0.001);

        $log0 = Double::from(0)->ln();
        $this->assertTrue($log0->isNan());

        $logNegative = Double::from(-1)->ln();
        $this->assertTrue($logNegative->isNan());
    }

    public function testLog(): void
    {
        $a = Double::from(100.0);
        $log = $a->log(10.0);
        $this->assertSame(2.0, $log->toFloat());

        $b = Double::from(\M_E); // ~2.71828
        $logE = $b->log(\M_E);
        $this->assertSame(1.0, $logE->toFloat());

        $c = Double::from(25.0);
        $log5 = $c->log(5.0);
        $this->assertEqualsWithDelta(2.0, $log5->toFloat(), 0.0001);

        $d = Double::from(16.0);
        $base = Double::from(2.0);
        $log2 = $d->log($base);
        $this->assertSame(4.0, $log2->toFloat());

        $negative = Double::from(-10.0);
        $logNegative = $negative->log(10.0);
        $this->assertTrue($logNegative->isNan());

        $e = Double::from(10.0);
        $logNegativeBase = $e->log(-2.0);
        $this->assertTrue($logNegativeBase->isNan());

        $logBase1 = $e->log(1.0);
        $this->assertTrue($logBase1->isNan());

        $logZeroBase = $e->log(0.0);
        $this->assertTrue($logZeroBase->isNan());
    }

    public function testLog2(): void
    {
        $a = Double::from(8.0);
        $log2 = $a->log2();
        $this->assertSame(3.0, $log2->toFloat());

        $b = Double::from(9.0);
        $log2b = $b->log2();
        $this->assertEqualsWithDelta(3.17, $log2b->toFloat(), 0.01);
    }

    public function testLog10(): void
    {
        $a = Double::from(1000.0);
        $log10 = $a->log10();
        $this->assertSame(3.0, $log10->toFloat());

        $b = Double::from(500.0);
        $log10b = $b->log10();
        $this->assertEqualsWithDelta(2.699, $log10b->toFloat(), 0.001);
    }

    public function testSqrt(): void
    {
        $a = Double::from(16.0);
        $sqrt = $a->sqrt();

        $this->assertSame(4.0, $sqrt->toFloat());

        $b = Double::from(10.0);
        $sqrtb = $b->sqrt();

        $this->assertEqualsWithDelta(3.162, $sqrtb->toFloat(), 0.001);

        $negative = Double::from(-4.0);
        $sqrtNegative = $negative->sqrt();

        $this->assertTrue($sqrtNegative->isNan());

        $zero = Double::from(0.0);
        $sqrtZero = $zero->sqrt();

        $this->assertSame(0.0, $sqrtZero->toFloat());
    }

    public function testCbrt(): void
    {
        $a = Double::from(27.0);
        $this->assertSame(3.0, $a->cbrt()->toFloat());

        $b = Double::from(-8.0);
        $this->assertSame(-2.0, $b->cbrt()->toFloat());

        $c = Double::from(10.0);
        $this->assertEqualsWithDelta(2.154, $c->cbrt()->toFloat(), 0.001);
    }

    public function testExp(): void
    {
        $a = Double::from(1.0);
        $this->assertEqualsWithDelta(2.718, $a->exp()->toFloat(), 0.001);

        $b = Double::from(0.0);
        $this->assertSame(1.0, $b->exp()->toFloat());

        $c = Double::from(-1.0);
        $this->assertEqualsWithDelta(0.368, $c->exp()->toFloat(), 0.001);
    }

    public function testTrigonometricFunctions(): void
    {
        $zero = Double::from(0.0);
        $halfPi = Double::pi()->div(2.0)->unwrap();
        $pi = Double::pi();

        $this->assertSame(0.0, $zero->sin()->toFloat());
        $this->assertEqualsWithDelta(1.0, $halfPi->sin()->toFloat(), 0.000001);
        $this->assertEqualsWithDelta(0.0, $pi->sin()->toFloat(), 0.000001);

        $this->assertSame(1.0, $zero->cos()->toFloat());
        $this->assertEqualsWithDelta(0.0, $halfPi->cos()->toFloat(), 0.000001);
        $this->assertEqualsWithDelta(-1.0, $pi->cos()->toFloat(), 0.000001);

        $this->assertSame(0.0, $zero->tan()->toFloat());
        // tan(π/4) = 1
        $this->assertEqualsWithDelta(
            1.0,
            Double::pi()->div(4.0)->unwrap()->tan()->toFloat(),
            0.000001,
        );
    }

    public function testInverseTrigonometricFunctions(): void
    {
        $zero = Double::from(0.0);
        $half = Double::from(0.5);
        $one = Double::from(1.0);
        $outOfRange = Double::from(1.5);

        $this->assertSame(0.0, $zero->asin()->toFloat());
        $this->assertEqualsWithDelta(
            0.5236, // π/6
            $half->asin()->toFloat(),
            0.001,
        );
        $this->assertEqualsWithDelta(
            1.5708, // π/2
            $one->asin()->toFloat(),
            0.001,
        );
        $this->assertTrue($outOfRange->asin()->isNan());

        $this->assertEqualsWithDelta(
            1.5708, // π/2
            $zero->acos()->toFloat(),
            0.001,
        );
        $this->assertEqualsWithDelta(
            1.0472, // π/3
            $half->acos()->toFloat(),
            0.001,
        );
        $this->assertSame(0.0, $one->acos()->toFloat());
        $this->assertTrue($outOfRange->acos()->isNan());

        $this->assertSame(0.0, $zero->atan()->toFloat());
        $this->assertEqualsWithDelta(
            0.4636, // ~π/6.8
            $half->atan()->toFloat(),
            0.001,
        );
        $this->assertEqualsWithDelta(
            0.7854, // π/4
            $one->atan()->toFloat(),
            0.001,
        );
    }

    public function testAtan2(): void
    {
        $y1 = Double::from(1.0);
        $x1 = Double::from(1.0);
        $this->assertEqualsWithDelta(
            0.7854, // π/4
            $y1->atan2($x1)->toFloat(),
            0.001,
        );

        $y2 = Double::from(1.0);
        $x2 = Double::from(-1.0);
        $this->assertEqualsWithDelta(
            2.3562, // 3π/4
            $y2->atan2($x2)->toFloat(),
            0.001,
        );

        $y3 = Double::from(-1.0);
        $x3 = Double::from(-1.0);
        $this->assertEqualsWithDelta(
            -2.3562, // -3π/4
            $y3->atan2($x3)->toFloat(),
            0.001,
        );
    }

    public function testHyperbolicFunctions(): void
    {
        $zero = Double::from(0.0);
        $one = Double::from(1.0);

        $this->assertSame(0.0, $zero->sinh()->toFloat());
        $this->assertEqualsWithDelta(1.1752, $one->sinh()->toFloat(), 0.001);

        $this->assertSame(1.0, $zero->cosh()->toFloat());
        $this->assertEqualsWithDelta(1.5431, $one->cosh()->toFloat(), 0.001);

        $this->assertSame(0.0, $zero->tanh()->toFloat());
        $this->assertEqualsWithDelta(0.7616, $one->tanh()->toFloat(), 0.001);
    }

    public function testToRadiansAndToDegrees(): void
    {
        $degrees90 = Double::from(90.0);
        $radians = $degrees90->toRadians();
        $this->assertEqualsWithDelta(1.5708, $radians->toFloat(), 0.001); // π/2

        $degrees = $radians->toDegrees();
        $this->assertEqualsWithDelta(90.0, $degrees->toFloat(), 0.001);
    }
}
