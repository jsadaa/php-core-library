<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Vec\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Vec\Vec;
use PHPUnit\Framework\TestCase;

final class VecCreationTest extends TestCase
{
    public function testCreateEmptyVec(): void
    {
        $vec = Vec::new();

        $this->assertTrue($vec->isEmpty());
        $this->assertSame([], $vec->toArray());
    }

    public function testCreateVecWithItems(): void
    {
        $vec = Vec::from(1, 2, 3);

        $this->assertFalse($vec->isEmpty());
        $this->assertSame([1, 2, 3], $vec->toArray());
    }

    public function testCreateVecWithStrings(): void
    {
        $vec = Vec::from('a', 'b', 'c');

        $this->assertFalse($vec->isEmpty());
        $this->assertSame(['a', 'b', 'c'], $vec->toArray());
    }

    public function testCreateVecWithFloats(): void
    {
        $vec = Vec::from(1.1, 2.2, 3.3);

        $this->assertFalse($vec->isEmpty());
        $this->assertSame([1.1, 2.2, 3.3], $vec->toArray());
    }

    public function testCreateVecWithBooleans(): void
    {
        $vec = Vec::from(true, false, true);

        $this->assertFalse($vec->isEmpty());
        $this->assertSame([true, false, true], $vec->toArray());
    }

    public function testCreateVecWithObjects(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();

        $vec = Vec::from($obj1, $obj2);

        $this->assertSame([$obj1, $obj2], $vec->toArray());
    }

    public function testCreateVecWithSingleItem(): void
    {
        $vec = Vec::from(42);

        $this->assertSame([42], $vec->toArray());
        $this->assertSame(1, $vec->len()->toInt());
    }

    public function testCreateEmptyVecWithFromMethod(): void
    {
        $vec = Vec::from();

        $this->assertTrue($vec->isEmpty());
    }
}
