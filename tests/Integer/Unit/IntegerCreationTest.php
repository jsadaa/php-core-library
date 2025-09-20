<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Integer\Unit;

use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use PHPUnit\Framework\TestCase;

final class IntegerCreationTest extends TestCase
{
    public function testCreateIntegerFromValue(): void
    {
        $integer = Integer::of(42);

        $this->assertSame(42, $integer->toInt());
    }

    public function testCreateIntegerFromZero(): void
    {
        $integer = Integer::of(0);

        $this->assertSame(0, $integer->toInt());
    }

    public function testCreateIntegerFromNegativeValue(): void
    {
        $integer = Integer::of(-42);

        $this->assertSame(-42, $integer->toInt());
    }

    public function testCreateIntegerFromMinValue(): void
    {
        $integer = Integer::of(\PHP_INT_MIN);

        $this->assertSame(\PHP_INT_MIN, $integer->toInt());
    }

    public function testCreateIntegerFromMaxValue(): void
    {
        $integer = Integer::of(\PHP_INT_MAX);

        $this->assertSame(\PHP_INT_MAX, $integer->toInt());
    }
}
