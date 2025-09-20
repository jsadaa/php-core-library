<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Sequence\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use PHPUnit\Framework\TestCase;

final class SequenceRemovalTest extends TestCase
{
    public function testClearSequence(): void
    {
        $seq = Sequence::of(1, 2, 3);
        $clearedSequence = $seq->clear();

        $this->assertFalse($seq->isEmpty(), 'Original Sequence should remain unchanged');
        $this->assertTrue($clearedSequence->isEmpty());
    }

    public function testClearEmptySequence(): void
    {
        $seq = Sequence::new();
        $clearedSequence = $seq->clear();

        $this->assertTrue($seq->isEmpty());
        $this->assertTrue($clearedSequence->isEmpty());
    }

    public function testClearPreservesOriginalSequence(): void
    {
        $seq = Sequence::of('a', 'b');
        $clearedSequence = $seq->clear();

        $this->assertSame(['a', 'b'], $seq->toArray());
        $this->assertSame([], $clearedSequence->toArray());
    }

    public function testRemoveItemAtValidIndex(): void
    {
        $seq = Sequence::of('a', 'b', 'c', 'd');
        $modifiedSequence = $seq->removeAt(1);

        $this->assertSame(['a', 'c', 'd'], $modifiedSequence->toArray());
        $this->assertSame(['a', 'b', 'c', 'd'], $seq->toArray());
    }

    public function testRemoveItemAtFirstIndex(): void
    {
        $seq = Sequence::of('a', 'b', 'c');
        $modifiedSequence = $seq->removeAt(0);

        $this->assertSame(['b', 'c'], $modifiedSequence->toArray());
        $this->assertSame(['a', 'b', 'c'], $seq->toArray());
    }

    public function testRemoveItemAtLastIndex(): void
    {
        $seq = Sequence::of('a', 'b', 'c');
        $modifiedSequence = $seq->removeAt(2);

        $this->assertSame(['a', 'b'], $modifiedSequence->toArray());
        $this->assertSame(['a', 'b', 'c'], $seq->toArray());
    }

    public function testRemoveItemFromSingleItemSequence(): void
    {
        $seq = Sequence::of('singleton');
        $modifiedSequence = $seq->removeAt(0);

        $this->assertSame([], $modifiedSequence->toArray());
        $this->assertSame(['singleton'], $seq->toArray());
    }

    public function testRemoveWithNegativeIndexReturnsUnchangedSequence(): void
    {
        $seq = Sequence::of(1, 2, 3);
        $otherSequence = $seq->removeAt(-1);

        $this->assertSame([1, 2, 3], $otherSequence->toArray());
        $this->assertSame([1, 2, 3], $seq->toArray());
    }

    public function testRemoveWithOutOfBoundsIndexReturnsUnchangedSequence(): void
    {
        $seq = Sequence::of(1, 2, 3);
        $otherSequence = $seq->removeAt(3);

        $this->assertSame([1, 2, 3], $otherSequence->toArray());
        $this->assertSame([1, 2, 3], $seq->toArray());
    }

    public function testRemoveItemFromEmptySequenceReturnsEmptySequence(): void
    {
        $emptySequence = Sequence::new();
        $otherEmptySequence = $emptySequence->removeAt(0);

        $this->assertSame([], $otherEmptySequence->toArray());
        $this->assertTrue($emptySequence->isEmpty(), 'Sequence should remain empty');
    }

    public function testRemoveWithObjects(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $obj3 = new \stdClass();

        $seq = Sequence::of($obj1, $obj2, $obj3);
        $modifiedSequence = $seq->removeAt(1);

        $this->assertSame([$obj1, $obj3], $modifiedSequence->toArray());
        $this->assertSame([$obj1, $obj2, $obj3], $seq->toArray());
    }

    public function testRemoveMultipleItemsSequentially(): void
    {
        $seq = Sequence::of('a', 'b', 'c', 'd', 'e');

        $modifiedSequence = $seq->removeAt(1);
        $this->assertSame(['a', 'c', 'd', 'e'], $modifiedSequence->toArray());
        $this->assertSame(['a', 'b', 'c', 'd', 'e'], $seq->toArray());

        $modifiedSequence = $modifiedSequence->removeAt(2);
        $this->assertSame(['a', 'c', 'e'], $modifiedSequence->toArray());
        $this->assertSame(['a', 'b', 'c', 'd', 'e'], $seq->toArray());
    }
}
