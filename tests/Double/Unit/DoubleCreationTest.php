<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Double\Unit;

use Jsadaa\PhpCoreLibrary\Primitives\Double\Double;
use PHPUnit\Framework\TestCase;

final class DoubleCreationTest extends TestCase
{
    public function testCreateDoubleFromValue(): void
    {
        $double = Double::of(42.5);

        $this->assertSame(42.5, $double->toFloat());
    }

    public function testCreateDoubleFromZero(): void
    {
        $double = Double::of(0.0);

        $this->assertSame(0.0, $double->toFloat());
    }

    public function testCreateDoubleFromNegativeValue(): void
    {
        $double = Double::of(-42.5);

        $this->assertSame(-42.5, $double->toFloat());
    }

    public function testCreateDoubleFromInteger(): void
    {
        $double = Double::of(42);

        $this->assertSame(42.0, $double->toFloat());
    }

    public function testCreateDoubleFromAnotherDouble(): void
    {
        $original = Double::of(42.5);
        $double = Double::of($original);

        $this->assertSame(42.5, $double->toFloat());
        $this->assertNotSame($original, $double, 'Should create a new instance');
    }

    public function testCreateDoubleFromSpecialValues(): void
    {
        $pi = Double::pi();
        $e = Double::e();
        $inf = Double::infinity();
        $negInf = Double::negInfinity();
        $nan = Double::nan();

        $this->assertSame(\M_PI, $pi->toFloat());
        $this->assertSame(\M_E, $e->toFloat());
        $this->assertTrue(\is_infinite($inf->toFloat()));
        $this->assertTrue($inf->toFloat() > 0);
        $this->assertTrue(\is_infinite($negInf->toFloat()));
        $this->assertTrue($negInf->toFloat() < 0);
        $this->assertTrue(\is_nan($nan->toFloat()));
    }
}
