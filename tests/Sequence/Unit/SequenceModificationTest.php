<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Sequence\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Error\IndexOutOfBounds;
use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use PHPUnit\Framework\TestCase;

final class SequenceModificationTest extends TestCase
{
    public function testAddItemToSequence(): void
    {
        $seq = Sequence::of('hello')->push('world');

        $this->assertSame(['hello', 'world'], $seq->toArray());
    }

    public function testAddMultipleItems(): void
    {
        $seq = Sequence::of(1)
            ->push(2)
            ->push(3)
            ->push(4);

        $this->assertSame([1, 2, 3, 4], $seq->toArray());
    }

    public function testPushToEmptySequence(): void
    {
        $seq = Sequence::new()->push('first');

        $this->assertSame(['first'], $seq->toArray());
    }

    public function testAddItemToEmptySequence(): void
    {
        $seq = Sequence::new();
        $newSequence = $seq->push(42);

        $this->assertTrue($seq->isEmpty(), 'Original Sequence should remain empty');
        $this->assertFalse($newSequence->isEmpty());
        $this->assertSame([42], $newSequence->toArray());
    }

    public function testAddItemOfSameType(): void
    {
        $seq = Sequence::of('hello');
        $newSequence = $seq->push('world');

        $this->assertSame(
            ['hello'],
            $seq->toArray(),
            'Original Sequence should remain unchanged',
        );
        $this->assertSame(['hello', 'world'], $newSequence->toArray());
    }

    public function testInsertItemAtValidIndex(): void
    {
        $seq = Sequence::of('a', 'c');
        $newSequence = $seq->insertAt(1, 'b');

        $this->assertSame(
            ['a', 'c'],
            $seq->toArray(),
            'Original Sequence should remain unchanged',
        );
        $this->assertSame(['a', 'b', 'c'], $newSequence->toArray());
    }

    public function testInsertItemAtEndIndex(): void
    {
        $seq = Sequence::of('a', 'b');
        $newSequence = $seq->insertAt(2, 'c');

        $this->assertSame(['a', 'b', 'c'], $newSequence->toArray());
    }

    public function testInsertItemAtBeginning(): void
    {
        $seq = Sequence::of('b', 'c');
        $newSequence = $seq->insertAt(0, 'a');

        $this->assertSame(['a', 'b', 'c'], $newSequence->toArray());
    }

    public function testInsertItemIntoEmptySequence(): void
    {
        $seq = Sequence::new();
        $newSequence = $seq->insertAt(0, 'first');

        $this->assertTrue($seq->isEmpty(), 'Original Sequence should remain empty');
        $this->assertSame(['first'], $newSequence->toArray());
    }

    public function testInsertAtNegativeIndex(): void
    {
        $seq = Sequence::of(1, 2, 3);
        $newSequence = $seq->insertAt(-1, 0);

        $this->assertSame([1, 2, 0, 3], $newSequence->toArray());
    }

    public function testInsertAtIndexBeyondEnd(): void
    {
        $seq = Sequence::of(1, 2);
        $newSequence = $seq->insertAt(10, 3);

        $this->assertSame([1, 2, 3], $newSequence->toArray());
    }

    public function testDedupWithIntegers(): void
    {
        $seq = Sequence::of(1, 2, 2, 3, 1, 4, 5, 3);
        $dedupedSequence = $seq->dedup();

        $this->assertSame([1, 2, 3, 4, 5], $dedupedSequence->toArray());
        $this->assertSame(
            [1, 2, 2, 3, 1, 4, 5, 3],
            $seq->toArray(),
            'Original Sequence should remain unchanged',
        );
    }

    public function testDedupWithStrings(): void
    {
        $seq = Sequence::of('a', 'b', 'a', 'c', 'd', 'b', 'e');
        $dedupedSequence = $seq->dedup();

        $this->assertSame(['a', 'b', 'c', 'd', 'e'], $dedupedSequence->toArray());
    }

    public function testDedupWithFloats(): void
    {
        $seq = Sequence::of(1.1, 2.2, 1.1, 3.3, 2.2, 4.4);
        $dedupedSequence = $seq->dedup();

        $this->assertSame([1.1, 2.2, 3.3, 4.4], $dedupedSequence->toArray());
    }

    public function testDedupWithBooleans(): void
    {
        $seq = Sequence::of(true, false, true, false, true);
        $dedupedSequence = $seq->dedup();

        $this->assertSame([true, false], $dedupedSequence->toArray());
    }

    public function testDedupWithEmptySequence(): void
    {
        $seq = Sequence::new();
        $dedupedSequence = $seq->dedup();

        $this->assertTrue($dedupedSequence->isEmpty());
    }

    public function testDedupWithSingleItem(): void
    {
        $seq = Sequence::of('unique');
        $dedupedSequence = $seq->dedup();

        $this->assertSame(['unique'], $dedupedSequence->toArray());
    }

    public function testDedupWithObjects(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $obj3 = new \stdClass();

        $seq = Sequence::of($obj1, $obj2, $obj1, $obj3);
        $dedupedSequence = $seq->dedup();

        $this->assertCount(3, $dedupedSequence->toArray());

        $this->assertSame($obj1, $dedupedSequence->toArray()[0]);
        $this->assertSame($obj2, $dedupedSequence->toArray()[1]);
        $this->assertSame($obj3, $dedupedSequence->toArray()[2]);
    }

    public function testDedupWithNoDuplicates(): void
    {
        $seq = Sequence::of(1, 2, 3, 4, 5);
        $dedupedSequence = $seq->dedup();

        $this->assertSame([1, 2, 3, 4, 5], $dedupedSequence->toArray());
        $this->assertNotSame(
            $seq,
            $dedupedSequence,
            'Dedup should return a new Sequence instance even with no duplicates',
        );
    }

    public function testDedupWithMixedNumericStrings(): void
    {
        $seq = Sequence::of('1', '2', '1', '3');
        $dedupedSequence = $seq->dedup();

        $this->assertSame(['1', '2', '3'], $dedupedSequence->toArray());
    }

    public function testResizeToSmallerSize(): void
    {
        $seq = Sequence::of(1, 2, 3, 4, 5);
        $resizedSequence = $seq->resize(3, 0);

        $this->assertSame([1, 2, 3], $resizedSequence->toArray());
        $this->assertSame(
            [1, 2, 3, 4, 5],
            $seq->toArray(),
            'Original Sequence should remain unchanged',
        );
    }

    public function testResizeToLargerSize(): void
    {
        $seq = Sequence::of('a', 'b', 'c');
        $resizedSequence = $seq->resize(5, 'x');

        $this->assertSame(['a', 'b', 'c', 'x', 'x'], $resizedSequence->toArray());
        $this->assertSame(
            ['a', 'b', 'c'],
            $seq->toArray(),
            'Original Sequence should remain unchanged',
        );
    }

    public function testResizeToSameSize(): void
    {
        $seq = Sequence::of(1, 2, 3);
        $resizedSequence = $seq->resize(3, 0);

        $this->assertSame([1, 2, 3], $resizedSequence->toArray());
        $this->assertNotSame(
            $seq,
            $resizedSequence,
            'Resize should return a new Sequence instance even with same size',
        );
    }

    public function testResizeEmptySequenceToNonEmpty(): void
    {
        $seq = Sequence::new();
        $resizedSequence = $seq->resize(3, 'hello');

        $this->assertSame(['hello', 'hello', 'hello'], $resizedSequence->toArray());
        $this->assertTrue($seq->isEmpty(), 'Original Sequence should remain empty');
    }

    public function testResizeToZeroSize(): void
    {
        $seq = Sequence::of(1, 2, 3);
        $resizedSequence = $seq->resize(0, 0);

        $this->assertSame([], $resizedSequence->toArray());
        $this->assertTrue($resizedSequence->isEmpty());
    }

    public function testResizeShouldMaintainCorrectType(): void
    {
        $obj = new \stdClass();
        $seq = Sequence::of($obj);
        $obj2 = new \stdClass();
        $resizedSequence = $seq->resize(3, $obj2);

        $this->assertCount(3, $resizedSequence->toArray());
        $this->assertSame($obj, $resizedSequence->toArray()[0]);
        $this->assertSame($obj2, $resizedSequence->toArray()[1]);
        $this->assertSame($obj2, $resizedSequence->toArray()[2]);
    }

    public function testTruncateToSmallerSize(): void
    {
        $seq = Sequence::of(1, 2, 3, 4, 5);
        $truncatedSequence = $seq->truncate(3);

        $this->assertSame(
            [1, 2, 3, 4, 5],
            $seq->toArray(),
            'Original Sequence should remain unchanged',
        );
        $this->assertSame([1, 2, 3], $truncatedSequence->toArray());
        $this->assertSame(3, $truncatedSequence->len()->toInt());
    }

    public function testTruncateToLargerSize(): void
    {
        $seq = Sequence::of(1, 2, 3);
        $truncatedSequence = $seq->truncate(5);

        $this->assertSame([1, 2, 3], $truncatedSequence->toArray());
        $this->assertSame(3, $truncatedSequence->len()->toInt());
    }

    public function testTruncateToSameSize(): void
    {
        $seq = Sequence::of(1, 2, 3);
        $truncatedSequence = $seq->truncate(3);

        $this->assertSame([1, 2, 3], $truncatedSequence->toArray());
        $this->assertNotSame(
            $seq,
            $truncatedSequence,
            'Truncate should return a new Sequence instance even with same size',
        );
    }

    public function testTruncateToZeroSize(): void
    {
        $seq = Sequence::of(1, 2, 3);
        $truncatedSequence = $seq->truncate(0);

        $this->assertSame([], $truncatedSequence->toArray());
        $this->assertTrue($truncatedSequence->isEmpty());
    }

    public function testTruncateEmptySequence(): void
    {
        $seq = Sequence::new();
        $truncatedSequence = $seq->truncate(3);

        $this->assertTrue(
            $truncatedSequence->isEmpty(),
            'Truncating an empty Sequence should return an empty Sequence',
        );
    }

    public function testWindowsWithValidSize(): void
    {
        $seq = Sequence::of(1, 2, 3, 4, 5);
        $windows = $seq->windows(2);

        $this->assertSame(4, $windows->len()->toInt());

        $firstWindow = $windows->get(0)->match(static fn($v) => $v, static fn() => null);
        $this->assertInstanceOf(Sequence::class, $firstWindow);
        $this->assertSame([1, 2], $firstWindow->toArray());

        $allWindows = $windows
            ->map(static fn($window) => $window->toArray())
            ->toArray();
        $this->assertSame([[1, 2], [2, 3], [3, 4], [4, 5]], $allWindows);
    }

    public function testWindowsWithSizeEqualToCollectionLength(): void
    {
        $seq = Sequence::of('a', 'b', 'c');
        $windows = $seq->windows(3);

        $this->assertSame(1, $windows->len()->toInt());

        $window = $windows->get(0)->match(static fn($v) => $v, static fn() => null);
        $this->assertSame(['a', 'b', 'c'], $window->toArray());
    }

    public function testWindowsWithSizeGreaterThanCollectionLength(): void
    {
        $seq = Sequence::of(1, 2);
        $windows = $seq->windows(3);

        $this->assertTrue($windows->isEmpty());
    }

    public function testWindowsWithSizeOne(): void
    {
        $seq = Sequence::of('a', 'b', 'c');
        $windows = $seq->windows(1);

        $this->assertSame(3, $windows->len()->toInt());

        $allWindows = $windows
            ->map(static fn($window) => $window->toArray())
            ->toArray();
        $this->assertSame([['a'], ['b'], ['c']], $allWindows);
    }

    public function testWindowsWithEmptySequence(): void
    {
        $seq = Sequence::new();
        $windows = $seq->windows(1);

        $this->assertTrue($windows->isEmpty());
    }

    public function testOriginalSequenceRemainsUnchangedAfterWindows(): void
    {
        $seq = Sequence::of(1, 2, 3, 4);
        $original = $seq->toArray();

        $seq->windows(2);

        $this->assertSame($original, $seq->toArray());
    }

    public function testTakeWithValidCount(): void
    {
        $seq = Sequence::of(1, 2, 3, 4, 5);
        $takenSequence = $seq->take(3);

        $this->assertSame([1, 2, 3], $takenSequence->toArray());
        $this->assertSame(
            [1, 2, 3, 4, 5],
            $seq->toArray(),
            'Original Sequence should remain unchanged',
        );
    }

    public function testTakeWithCountEqualToLength(): void
    {
        $seq = Sequence::of('a', 'b', 'c');
        $takenSequence = $seq->take(3);

        $this->assertSame(['a', 'b', 'c'], $takenSequence->toArray());
        $this->assertNotSame(
            $seq,
            $takenSequence,
            'Take should return a new Sequence instance even when taking all elements',
        );
    }

    public function testTakeWithCountGreaterThanLength(): void
    {
        $seq = Sequence::of(1, 2, 3);
        $takenSequence = $seq->take(5);

        $this->assertSame([1, 2, 3], $takenSequence->toArray());
    }

    public function testTakeWithZeroCount(): void
    {
        $seq = Sequence::of(1, 2, 3);
        $takenSequence = $seq->take(0);

        $this->assertSame([], $takenSequence->toArray());
        $this->assertTrue($takenSequence->isEmpty());
    }

    public function testTakeFromEmptySequence(): void
    {
        $seq = Sequence::new();
        $takenSequence = $seq->take(3);

        $this->assertTrue(
            $takenSequence->isEmpty(),
            'Taking from an empty Sequence should return an empty Sequence',
        );
    }

    public function testSkipWithValidCount(): void
    {
        $seq = Sequence::of(1, 2, 3, 4, 5);
        $skippedSequence = $seq->skip(2);

        $this->assertSame([3, 4, 5], $skippedSequence->toArray());
        $this->assertSame(
            [1, 2, 3, 4, 5],
            $seq->toArray(),
            'Original Sequence should remain unchanged',
        );
    }

    public function testSkipWithCountEqualToLength(): void
    {
        $seq = Sequence::of('a', 'b', 'c');
        $skippedSequence = $seq->skip(3);

        $this->assertTrue(
            $skippedSequence->isEmpty(),
            'Skipping all elements should result in an empty Sequence',
        );
    }

    public function testSkipWithCountGreaterThanLength(): void
    {
        $seq = Sequence::of(1, 2, 3);
        $skippedSequence = $seq->skip(5);

        $this->assertTrue($skippedSequence->isEmpty());
    }

    public function testSkipWithZeroCount(): void
    {
        $seq = Sequence::of(1, 2, 3);
        $skippedSequence = $seq->skip(0);

        $this->assertSame([1, 2, 3], $skippedSequence->toArray());
        $this->assertNotSame(
            $seq,
            $skippedSequence,
            'Skip should return a new Sequence instance even with count of 0',
        );
    }

    public function testSkipFromEmptySequence(): void
    {
        $seq = Sequence::new();
        $skippedSequence = $seq->skip(3);

        $this->assertTrue(
            $skippedSequence->isEmpty(),
            'Skipping from an empty Sequence should return an empty Sequence',
        );
    }

    public function testChainedTakeAndSkip(): void
    {
        $seq = Sequence::of(1, 2, 3, 4, 5, 6, 7, 8);
        $result = $seq->take(5)->skip(2);

        $this->assertSame([3, 4, 5], $result->toArray());
    }

    public function testSwapValidIndices(): void
    {
        $seq = Sequence::of('a', 'b', 'c', 'd');
        $swapped = $seq->swap(0, 2)->unwrap();

        $this->assertSame(
            ['a', 'b', 'c', 'd'],
            $seq->toArray(),
            'Original Sequence should remain unchanged',
        );
        $this->assertSame(['c', 'b', 'a', 'd'], $swapped->toArray());
    }

    public function testSwapSameIndex(): void
    {
        $seq = Sequence::of(1, 2, 3);
        $swapped = $seq->swap(1, 1)->unwrap();

        $this->assertSame(
            [1, 2, 3],
            $swapped->toArray(),
            'Swapping an index with itself should not change anything',
        );
        $this->assertNotSame(
            $seq,
            $swapped,
            'Swap should return a new Sequence instance even when indices are the same',
        );
    }

    public function testSwapAdjacentIndices(): void
    {
        $seq = Sequence::of('x', 'y', 'z');
        $swapped = $seq->swap(0, 1)->unwrap();

        $this->assertSame(['y', 'x', 'z'], $swapped->toArray());
    }

    public function testSwapFirstAndLastIndices(): void
    {
        $seq = Sequence::of(1, 2, 3, 4, 5);
        $swapped = $seq->swap(0, 4)->unwrap();

        $this->assertSame([5, 2, 3, 4, 1], $swapped->toArray());
    }

    public function testSwapWithObjects(): void
    {
        $obj1 = new \stdClass();
        $obj1->value = 'first';

        $obj2 = new \stdClass();
        $obj2->value = 'second';

        $seq = Sequence::of($obj1, $obj2);
        $swapped = $seq->swap(0, 1)->unwrap();

        $this->assertSame(
            $obj2,
            $swapped->get(0)->match(static fn($v) => $v, static fn() => null),
        );
        $this->assertSame(
            $obj1,
            $swapped->get(1)->match(static fn($v) => $v, static fn() => null),
        );
    }

    public function testSwapOutOfBoundsNegativeIndex(): void
    {
        $seq = Sequence::of(1, 2, 3)->swap(-1, 1);

        $this->assertTrue($seq->isErr());
        $this->assertInstanceOf(IndexOutOfBounds::class, $seq->unwrapErr());
    }

    public function testSwapOutOfBoundsIndexTooLarge(): void
    {
        $seq = Sequence::of(1, 2, 3)->swap(1, 3);

        $this->assertTrue($seq->isErr());
        $this->assertInstanceOf(IndexOutOfBounds::class, $seq->unwrapErr());
    }

    public function testSwapBothIndicesOutOfBounds(): void
    {
        $seq = Sequence::of(1, 2, 3)->swap(10, 20);

        $this->assertTrue($seq->isErr());
        $this->assertInstanceOf(IndexOutOfBounds::class, $seq->unwrapErr());
    }
}
