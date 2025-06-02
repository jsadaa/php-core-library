<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Vec\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Vec\Vec;
use PHPUnit\Framework\TestCase;

final class VecConversionTest extends TestCase
{
    public function testToListWithIntegers(): void
    {
        $vec = Vec::from(1, 2, 3);

        $this->assertSame([1, 2, 3], $vec->toArray());
    }

    public function testToListWithStrings(): void
    {
        $vec = Vec::from('a', 'b', 'c');

        $this->assertSame(['a', 'b', 'c'], $vec->toArray());
    }

    public function testToListWithObjects(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();

        $vec = Vec::from($obj1, $obj2);

        $this->assertSame([$obj1, $obj2], $vec->toArray());
    }

    public function testToListWithEmptyVec(): void
    {
        $vec = Vec::new();

        $this->assertSame([], $vec->toArray());
    }

    public function testToListAfterModification(): void
    {
        $vec = Vec::from(1, 2);
        $newVec = $vec->push(3);

        $this->assertSame([1, 2, 3], $newVec->toArray());
    }

    public function testToStringWithIntegers(): void
    {
        $vec = Vec::from(1, 2, 3);

        $this->assertSame('Vec<int>', (string)$vec);
    }

    public function testToStringWithStrings(): void
    {
        $vec = Vec::from('a', 'b', 'c');

        $this->assertSame('Vec<string>', (string)$vec);
    }

    public function testToStringWithObjects(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $vec = Vec::from($obj1, $obj2);

        $this->assertSame('Vec<stdClass>', (string)$vec);
    }

    public function testToStringWithEmptyVec(): void
    {
        $vec = Vec::new();

        $this->assertSame('Vec<>', (string)$vec);
    }

    public function testToStringWithObjectsAfterModification(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();

        $vec = Vec::from($obj1);
        $newVec = $vec->push($obj2);

        $this->assertSame('Vec<stdClass>', (string)$newVec);
    }

    public function testToStringWithLongCollections(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

        $this->assertSame('Vec<int>', (string)$vec);
    }

    public function testToStringWithBooleans(): void
    {
        $vec = Vec::from(true, false, true);

        $this->assertSame('Vec<bool>', (string)$vec);
    }
}
