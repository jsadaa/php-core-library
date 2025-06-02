<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Vec\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Vec\Vec;
use PHPUnit\Framework\TestCase;

final class VecRemovalTest extends TestCase
{
    public function testClearVec(): void
    {
        $vec = Vec::from(1, 2, 3);
        $clearedVec = $vec->clear();

        $this->assertFalse($vec->isEmpty(), 'Original vec should remain unchanged');
        $this->assertTrue($clearedVec->isEmpty());
    }

    public function testClearEmptyVec(): void
    {
        $vec = Vec::new();
        $clearedVec = $vec->clear();

        $this->assertTrue($vec->isEmpty());
        $this->assertTrue($clearedVec->isEmpty());
    }

    public function testClearPreservesOriginalVec(): void
    {
        $vec = Vec::from('a', 'b');
        $clearedVec = $vec->clear();

        $this->assertSame(['a', 'b'], $vec->toArray());
        $this->assertSame([], $clearedVec->toArray());
    }

    public function testRemoveItemAtValidIndex(): void
    {
        $vec = Vec::from('a', 'b', 'c', 'd');
        $modifiedVec = $vec->removeAt(1);

        $this->assertSame(['a', 'c', 'd'], $modifiedVec->toArray());
        $this->assertSame(['a', 'b', 'c', 'd'], $vec->toArray());
    }

    public function testRemoveItemAtFirstIndex(): void
    {
        $vec = Vec::from('a', 'b', 'c');
        $modifiedVec = $vec->removeAt(0);

        $this->assertSame(['b', 'c'], $modifiedVec->toArray());
        $this->assertSame(['a', 'b', 'c'], $vec->toArray());
    }

    public function testRemoveItemAtLastIndex(): void
    {
        $vec = Vec::from('a', 'b', 'c');
        $modifiedVec = $vec->removeAt(2);

        $this->assertSame(['a', 'b'], $modifiedVec->toArray());
        $this->assertSame(['a', 'b', 'c'], $vec->toArray());
    }

    public function testRemoveItemFromSingleItemVec(): void
    {
        $vec = Vec::from('singleton');
        $modifiedVec = $vec->removeAt(0);

        $this->assertSame([], $modifiedVec->toArray());
        $this->assertSame(['singleton'], $vec->toArray());
    }

    public function testRemoveWithNegativeIndexReturnsUnchangedVec(): void
    {
        $vec = Vec::from(1, 2, 3);
        $otherVec = $vec->removeAt(-1);

        $this->assertSame([1, 2, 3], $otherVec->toArray());
        $this->assertSame([1, 2, 3], $vec->toArray());
    }

    public function testRemoveWithOutOfBoundsIndexReturnsUnchangedVec(): void
    {
        $vec = Vec::from(1, 2, 3);
        $otherVec = $vec->removeAt(3);

        $this->assertSame([1, 2, 3], $otherVec->toArray());
        $this->assertSame([1, 2, 3], $vec->toArray());
    }

    public function testRemoveItemFromEmptyVecReturnsEmptyVec(): void
    {
        $emptyVec = Vec::new();
        $otherEmptyVec = $emptyVec->removeAt(0);

        $this->assertSame([], $otherEmptyVec->toArray());
        $this->assertTrue($emptyVec->isEmpty(), 'Vec should remain empty');
    }

    public function testRemoveWithObjects(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $obj3 = new \stdClass();

        $vec = Vec::from($obj1, $obj2, $obj3);
        $modifiedVec = $vec->removeAt(1);

        $this->assertSame([$obj1, $obj3], $modifiedVec->toArray());
        $this->assertSame([$obj1, $obj2, $obj3], $vec->toArray());
    }

    public function testRemoveMultipleItemsSequentially(): void
    {
        $vec = Vec::from('a', 'b', 'c', 'd', 'e');

        $modifiedVec = $vec->removeAt(1);
        $this->assertSame(['a', 'c', 'd', 'e'], $modifiedVec->toArray());
        $this->assertSame(['a', 'b', 'c', 'd', 'e'], $vec->toArray());

        $modifiedVec = $modifiedVec->removeAt(2);
        $this->assertSame(['a', 'c', 'e'], $modifiedVec->toArray());
        $this->assertSame(['a', 'b', 'c', 'd', 'e'], $vec->toArray());
    }
}
