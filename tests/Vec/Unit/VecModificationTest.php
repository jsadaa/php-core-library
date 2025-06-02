<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Vec\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Vec\Error\IndexOutOfBounds;
use Jsadaa\PhpCoreLibrary\Modules\Collections\Vec\Vec;
use PHPUnit\Framework\TestCase;

final class VecModificationTest extends TestCase
{
    public function testAddItemToVec(): void
    {
        $vec = Vec::from('hello')->push('world');

        $this->assertSame(['hello', 'world'], $vec->toArray());
    }

    public function testAddMultipleItems(): void
    {
        $vec = Vec::from(1)
            ->push(2)
            ->push(3)
            ->push(4);

        $this->assertSame([1, 2, 3, 4], $vec->toArray());
    }

    public function testPushToEmptyVec(): void
    {
        $vec = Vec::new()->push('first');

        $this->assertSame(['first'], $vec->toArray());
    }

    public function testAddItemToEmptyVec(): void
    {
        $vec = Vec::new();
        $newVec = $vec->push(42);

        $this->assertTrue($vec->isEmpty(), 'Original vec should remain empty');
        $this->assertFalse($newVec->isEmpty());
        $this->assertSame([42], $newVec->toArray());
    }

    public function testAddItemOfSameType(): void
    {
        $vec = Vec::from('hello');
        $newVec = $vec->push('world');

        $this->assertSame(
            ['hello'],
            $vec->toArray(),
            'Original vec should remain unchanged',
        );
        $this->assertSame(['hello', 'world'], $newVec->toArray());
    }

    public function testInsertItemAtValidIndex(): void
    {
        $vec = Vec::from('a', 'c');
        $newVec = $vec->insertAt(1, 'b');

        $this->assertSame(
            ['a', 'c'],
            $vec->toArray(),
            'Original vec should remain unchanged',
        );
        $this->assertSame(['a', 'b', 'c'], $newVec->toArray());
    }

    public function testInsertItemAtEndIndex(): void
    {
        $vec = Vec::from('a', 'b');
        $newVec = $vec->insertAt(2, 'c');

        $this->assertSame(['a', 'b', 'c'], $newVec->toArray());
    }

    public function testInsertItemAtBeginning(): void
    {
        $vec = Vec::from('b', 'c');
        $newVec = $vec->insertAt(0, 'a');

        $this->assertSame(['a', 'b', 'c'], $newVec->toArray());
    }

    public function testInsertItemIntoEmptyVec(): void
    {
        $vec = Vec::new();
        $newVec = $vec->insertAt(0, 'first');

        $this->assertTrue($vec->isEmpty(), 'Original vec should remain empty');
        $this->assertSame(['first'], $newVec->toArray());
    }

    public function testInsertAtNegativeIndex(): void
    {
        $vec = Vec::from(1, 2, 3);
        $newVec = $vec->insertAt(-1, 0);

        $this->assertSame([1, 2, 0, 3], $newVec->toArray());
    }

    public function testInsertAtIndexBeyondEnd(): void
    {
        $vec = Vec::from(1, 2);
        $newVec = $vec->insertAt(10, 3);

        $this->assertSame([1, 2, 3], $newVec->toArray());
    }

    public function testDedupWithIntegers(): void
    {
        $vec = Vec::from(1, 2, 2, 3, 1, 4, 5, 3);
        $dedupedVec = $vec->dedup();

        $this->assertSame([1, 2, 3, 4, 5], $dedupedVec->toArray());
        $this->assertSame(
            [1, 2, 2, 3, 1, 4, 5, 3],
            $vec->toArray(),
            'Original vec should remain unchanged',
        );
    }

    public function testDedupWithStrings(): void
    {
        $vec = Vec::from('a', 'b', 'a', 'c', 'd', 'b', 'e');
        $dedupedVec = $vec->dedup();

        $this->assertSame(['a', 'b', 'c', 'd', 'e'], $dedupedVec->toArray());
    }

    public function testDedupWithFloats(): void
    {
        $vec = Vec::from(1.1, 2.2, 1.1, 3.3, 2.2, 4.4);
        $dedupedVec = $vec->dedup();

        $this->assertSame([1.1, 2.2, 3.3, 4.4], $dedupedVec->toArray());
    }

    public function testDedupWithBooleans(): void
    {
        $vec = Vec::from(true, false, true, false, true);
        $dedupedVec = $vec->dedup();

        $this->assertSame([true, false], $dedupedVec->toArray());
    }

    public function testDedupWithEmptyVec(): void
    {
        $vec = Vec::new();
        $dedupedVec = $vec->dedup();

        $this->assertTrue($dedupedVec->isEmpty());
    }

    public function testDedupWithSingleItem(): void
    {
        $vec = Vec::from('unique');
        $dedupedVec = $vec->dedup();

        $this->assertSame(['unique'], $dedupedVec->toArray());
    }

    public function testDedupWithObjects(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $obj3 = new \stdClass();

        $vec = Vec::from($obj1, $obj2, $obj1, $obj3);
        $dedupedVec = $vec->dedup();

        $this->assertCount(3, $dedupedVec->toArray());

        $this->assertSame($obj1, $dedupedVec->toArray()[0]);
        $this->assertSame($obj2, $dedupedVec->toArray()[1]);
        $this->assertSame($obj3, $dedupedVec->toArray()[2]);
    }

    public function testDedupWithNoDuplicates(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $dedupedVec = $vec->dedup();

        $this->assertSame([1, 2, 3, 4, 5], $dedupedVec->toArray());
        $this->assertNotSame(
            $vec,
            $dedupedVec,
            'Dedup should return a new Vec instance even with no duplicates',
        );
    }

    public function testDedupWithMixedNumericStrings(): void
    {
        $vec = Vec::from('1', '2', '1', '3');
        $dedupedVec = $vec->dedup();

        $this->assertSame(['1', '2', '3'], $dedupedVec->toArray());
    }

    public function testResizeToSmallerSize(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $resizedVec = $vec->resize(3, 0);

        $this->assertSame([1, 2, 3], $resizedVec->toArray());
        $this->assertSame(
            [1, 2, 3, 4, 5],
            $vec->toArray(),
            'Original vec should remain unchanged',
        );
    }

    public function testResizeToLargerSize(): void
    {
        $vec = Vec::from('a', 'b', 'c');
        $resizedVec = $vec->resize(5, 'x');

        $this->assertSame(['a', 'b', 'c', 'x', 'x'], $resizedVec->toArray());
        $this->assertSame(
            ['a', 'b', 'c'],
            $vec->toArray(),
            'Original vec should remain unchanged',
        );
    }

    public function testResizeToSameSize(): void
    {
        $vec = Vec::from(1, 2, 3);
        $resizedVec = $vec->resize(3, 0);

        $this->assertSame([1, 2, 3], $resizedVec->toArray());
        $this->assertNotSame(
            $vec,
            $resizedVec,
            'Resize should return a new Vec instance even with same size',
        );
    }

    public function testResizeEmptyVecToNonEmpty(): void
    {
        $vec = Vec::new();
        $resizedVec = $vec->resize(3, 'hello');

        $this->assertSame(['hello', 'hello', 'hello'], $resizedVec->toArray());
        $this->assertTrue($vec->isEmpty(), 'Original vec should remain empty');
    }

    public function testResizeToZeroSize(): void
    {
        $vec = Vec::from(1, 2, 3);
        $resizedVec = $vec->resize(0, 0);

        $this->assertSame([], $resizedVec->toArray());
        $this->assertTrue($resizedVec->isEmpty());
    }

    public function testResizeShouldMaintainCorrectType(): void
    {
        $obj = new \stdClass();
        $vec = Vec::from($obj);
        $obj2 = new \stdClass();
        $resizedVec = $vec->resize(3, $obj2);

        $this->assertCount(3, $resizedVec->toArray());
        $this->assertSame($obj, $resizedVec->toArray()[0]);
        $this->assertSame($obj2, $resizedVec->toArray()[1]);
        $this->assertSame($obj2, $resizedVec->toArray()[2]);
    }

    public function testTruncateToSmallerSize(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $truncatedVec = $vec->truncate(3);

        $this->assertSame(
            [1, 2, 3, 4, 5],
            $vec->toArray(),
            'Original vec should remain unchanged',
        );
        $this->assertSame([1, 2, 3], $truncatedVec->toArray());
        $this->assertSame(3, $truncatedVec->len()->toInt());
    }

    public function testTruncateToLargerSize(): void
    {
        $vec = Vec::from(1, 2, 3);
        $truncatedVec = $vec->truncate(5);

        $this->assertSame([1, 2, 3], $truncatedVec->toArray());
        $this->assertSame(3, $truncatedVec->len()->toInt());
    }

    public function testTruncateToSameSize(): void
    {
        $vec = Vec::from(1, 2, 3);
        $truncatedVec = $vec->truncate(3);

        $this->assertSame([1, 2, 3], $truncatedVec->toArray());
        $this->assertNotSame(
            $vec,
            $truncatedVec,
            'Truncate should return a new Vec instance even with same size',
        );
    }

    public function testTruncateToZeroSize(): void
    {
        $vec = Vec::from(1, 2, 3);
        $truncatedVec = $vec->truncate(0);

        $this->assertSame([], $truncatedVec->toArray());
        $this->assertTrue($truncatedVec->isEmpty());
    }

    public function testTruncateEmptyVec(): void
    {
        $vec = Vec::new();
        $truncatedVec = $vec->truncate(3);

        $this->assertTrue(
            $truncatedVec->isEmpty(),
            'Truncating an empty Vec should return an empty Vec',
        );
    }

    public function testWindowsWithValidSize(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $windows = $vec->windows(2);

        $this->assertSame(4, $windows->len()->toInt());

        $firstWindow = $windows->get(0)->match(static fn($v) => $v, static fn() => null);
        $this->assertInstanceOf(Vec::class, $firstWindow);
        $this->assertSame([1, 2], $firstWindow->toArray());

        $allWindows = $windows
            ->map(static fn($window) => $window->toArray())
            ->toArray();
        $this->assertSame([[1, 2], [2, 3], [3, 4], [4, 5]], $allWindows);
    }

    public function testWindowsWithSizeEqualToCollectionLength(): void
    {
        $vec = Vec::from('a', 'b', 'c');
        $windows = $vec->windows(3);

        $this->assertSame(1, $windows->len()->toInt());

        $window = $windows->get(0)->match(static fn($v) => $v, static fn() => null);
        $this->assertSame(['a', 'b', 'c'], $window->toArray());
    }

    public function testWindowsWithSizeGreaterThanCollectionLength(): void
    {
        $vec = Vec::from(1, 2);
        $windows = $vec->windows(3);

        $this->assertTrue($windows->isEmpty());
    }

    public function testWindowsWithSizeOne(): void
    {
        $vec = Vec::from('a', 'b', 'c');
        $windows = $vec->windows(1);

        $this->assertSame(3, $windows->len()->toInt());

        $allWindows = $windows
            ->map(static fn($window) => $window->toArray())
            ->toArray();
        $this->assertSame([['a'], ['b'], ['c']], $allWindows);
    }

    public function testWindowsWithEmptyVec(): void
    {
        $vec = Vec::new();
        $windows = $vec->windows(1);

        $this->assertTrue($windows->isEmpty());
    }

    public function testOriginalVecRemainsUnchangedAfterWindows(): void
    {
        $vec = Vec::from(1, 2, 3, 4);
        $original = $vec->toArray();

        $vec->windows(2);

        $this->assertSame($original, $vec->toArray());
    }

    public function testTakeWithValidCount(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $takenVec = $vec->take(3);

        $this->assertSame([1, 2, 3], $takenVec->toArray());
        $this->assertSame(
            [1, 2, 3, 4, 5],
            $vec->toArray(),
            'Original vec should remain unchanged',
        );
    }

    public function testTakeWithCountEqualToLength(): void
    {
        $vec = Vec::from('a', 'b', 'c');
        $takenVec = $vec->take(3);

        $this->assertSame(['a', 'b', 'c'], $takenVec->toArray());
        $this->assertNotSame(
            $vec,
            $takenVec,
            'Take should return a new Vec instance even when taking all elements',
        );
    }

    public function testTakeWithCountGreaterThanLength(): void
    {
        $vec = Vec::from(1, 2, 3);
        $takenVec = $vec->take(5);

        $this->assertSame([1, 2, 3], $takenVec->toArray());
    }

    public function testTakeWithZeroCount(): void
    {
        $vec = Vec::from(1, 2, 3);
        $takenVec = $vec->take(0);

        $this->assertSame([], $takenVec->toArray());
        $this->assertTrue($takenVec->isEmpty());
    }

    public function testTakeFromEmptyVec(): void
    {
        $vec = Vec::new();
        $takenVec = $vec->take(3);

        $this->assertTrue(
            $takenVec->isEmpty(),
            'Taking from an empty Vec should return an empty Vec',
        );
    }

    public function testSkipWithValidCount(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $skippedVec = $vec->skip(2);

        $this->assertSame([3, 4, 5], $skippedVec->toArray());
        $this->assertSame(
            [1, 2, 3, 4, 5],
            $vec->toArray(),
            'Original vec should remain unchanged',
        );
    }

    public function testSkipWithCountEqualToLength(): void
    {
        $vec = Vec::from('a', 'b', 'c');
        $skippedVec = $vec->skip(3);

        $this->assertTrue(
            $skippedVec->isEmpty(),
            'Skipping all elements should result in an empty Vec',
        );
    }

    public function testSkipWithCountGreaterThanLength(): void
    {
        $vec = Vec::from(1, 2, 3);
        $skippedVec = $vec->skip(5);

        $this->assertTrue($skippedVec->isEmpty());
    }

    public function testSkipWithZeroCount(): void
    {
        $vec = Vec::from(1, 2, 3);
        $skippedVec = $vec->skip(0);

        $this->assertSame([1, 2, 3], $skippedVec->toArray());
        $this->assertNotSame(
            $vec,
            $skippedVec,
            'Skip should return a new Vec instance even with count of 0',
        );
    }

    public function testSkipFromEmptyVec(): void
    {
        $vec = Vec::new();
        $skippedVec = $vec->skip(3);

        $this->assertTrue(
            $skippedVec->isEmpty(),
            'Skipping from an empty Vec should return an empty Vec',
        );
    }

    public function testChainedTakeAndSkip(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5, 6, 7, 8);
        $result = $vec->take(5)->skip(2);

        $this->assertSame([3, 4, 5], $result->toArray());
    }

    public function testSwapValidIndices(): void
    {
        $vec = Vec::from('a', 'b', 'c', 'd');
        $swapped = $vec->swap(0, 2)->unwrap();

        $this->assertSame(
            ['a', 'b', 'c', 'd'],
            $vec->toArray(),
            'Original vec should remain unchanged',
        );
        $this->assertSame(['c', 'b', 'a', 'd'], $swapped->toArray());
    }

    public function testSwapSameIndex(): void
    {
        $vec = Vec::from(1, 2, 3);
        $swapped = $vec->swap(1, 1)->unwrap();

        $this->assertSame(
            [1, 2, 3],
            $swapped->toArray(),
            'Swapping an index with itself should not change anything',
        );
        $this->assertNotSame(
            $vec,
            $swapped,
            'Swap should return a new Vec instance even when indices are the same',
        );
    }

    public function testSwapAdjacentIndices(): void
    {
        $vec = Vec::from('x', 'y', 'z');
        $swapped = $vec->swap(0, 1)->unwrap();

        $this->assertSame(['y', 'x', 'z'], $swapped->toArray());
    }

    public function testSwapFirstAndLastIndices(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $swapped = $vec->swap(0, 4)->unwrap();

        $this->assertSame([5, 2, 3, 4, 1], $swapped->toArray());
    }

    public function testSwapWithObjects(): void
    {
        $obj1 = new \stdClass();
        $obj1->value = 'first';

        $obj2 = new \stdClass();
        $obj2->value = 'second';

        $vec = Vec::from($obj1, $obj2);
        $swapped = $vec->swap(0, 1)->unwrap();

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
        $vec = Vec::from(1, 2, 3)->swap(-1, 1);

        $this->assertTrue($vec->isErr());
        $this->assertInstanceOf(IndexOutOfBounds::class, $vec->unwrapErr());
    }

    public function testSwapOutOfBoundsIndexTooLarge(): void
    {
        $vec = Vec::from(1, 2, 3)->swap(1, 3);

        $this->assertTrue($vec->isErr());
        $this->assertInstanceOf(IndexOutOfBounds::class, $vec->unwrapErr());
    }

    public function testSwapBothIndicesOutOfBounds(): void
    {
        $vec = Vec::from(1, 2, 3)->swap(10, 20);

        $this->assertTrue($vec->isErr());
        $this->assertInstanceOf(IndexOutOfBounds::class, $vec->unwrapErr());
    }
}
