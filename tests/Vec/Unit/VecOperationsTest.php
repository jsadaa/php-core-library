<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Vec\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Vec\Vec;
use Jsadaa\PhpCoreLibrary\Modules\Option\Option;
use PHPUnit\Framework\TestCase;

final class VecOperationsTest extends TestCase
{
    public function testAppendTwoVecsOfSameType(): void
    {
        $vec1 = Vec::from(1, 2);
        $vec2 = Vec::from(3, 4);

        $result = $vec1->append($vec2);

        $this->assertSame([1, 2], $vec1->toArray(), 'Original vec1 should remain unchanged');
        $this->assertSame([3, 4], $vec2->toArray(), 'Original vec2 should remain unchanged');
        $this->assertSame([1, 2, 3, 4], $result->toArray());
    }

    public function testAppendEmptyVecToNonEmptyVec(): void
    {
        $vec1 = Vec::from('a', 'b');
        $vec2 = Vec::new();

        $result = $vec1->append($vec2);

        $this->assertSame(['a', 'b'], $result->toArray());
    }

    public function testAppendNonEmptyVecToEmptyVec(): void
    {
        $vec1 = Vec::new();
        $vec2 = Vec::from('a', 'b');

        $result = $vec1->append($vec2);

        $this->assertSame(['a', 'b'], $result->toArray());
    }

    public function testAppendTwoEmptyVecs(): void
    {
        $vec1 = Vec::new();
        $vec2 = Vec::new();

        $result = $vec1->append($vec2);

        $this->assertTrue($result->isEmpty());
    }

    public function testAppendVecsOfDifferentTypes(): void
    {
        $vec1 = Vec::from(1, 2);
        $vec2 = Vec::from('a', 'b');

        $result = $vec1->append($vec2);

        $this->assertSame([1, 2], $vec1->toArray(), 'Original vec1 should remain unchanged');
        $this->assertSame(['a', 'b'], $vec2->toArray(), 'Original vec2 should remain unchanged');
        $this->assertSame([1, 2, 'a', 'b'], $result->toArray());
    }

    public function testAppendSameVecToItself(): void
    {
        $vec = Vec::from(1, 2);

        $result = $vec->append($vec);

        $this->assertSame([1, 2, 1, 2], $result->toArray());
    }

    public function testMapOnNonEmptyVec(): void
    {
        $vec = Vec::from(1, 2, 3);
        $mappedVec = $vec->map(static fn(int $n) => $n * 2);

        $this->assertSame([1, 2, 3], $vec->toArray(), 'Original vec should remain unchanged');
        $this->assertSame([2, 4, 6], $mappedVec->toArray());
    }

    public function testMapChangingTypeOnNonEmptyVec(): void
    {
        $vec = Vec::from(1, 2, 3);
        $mappedVec = $vec->map(static fn(int $n) => (string)$n);

        $this->assertSame(['1', '2', '3'], $mappedVec->toArray());
    }

    public function testMapOnEmptyVec(): void
    {
        $vec = Vec::new();
        $mappedVec = $vec->map(static fn(int $n) => $n * 2);

        $this->assertTrue($mappedVec->isEmpty());
    }

    public function testMapToObjects(): void
    {
        $vec = Vec::from(1, 2, 3);

        $mappedVec = $vec->map(static function($n) {
            $obj = new \stdClass();
            $obj->value = $n;

            return $obj;
        });

        $this->assertSame(3, $mappedVec->len()->toInt());

        $firstObj = $mappedVec->get(0)->match(static fn($v) => $v, static fn() => null);
        $this->assertInstanceOf(\stdClass::class, $firstObj);
        $this->assertSame(1, $firstObj->value);
    }

    public function testFilterOnNonEmptyVec(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $filteredVec = $vec->filter(static fn(int $n) => $n % 2 === 0);

        $this->assertSame([1, 2, 3, 4, 5], $vec->toArray(), 'Original vec should remain unchanged');
        $this->assertSame([2, 4], $filteredVec->toArray());
    }

    public function testFilterWithAllMatchingItems(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $filteredVec = $vec->filter(static fn(int $n) => $n > 0);

        $this->assertSame([1, 2, 3, 4, 5], $filteredVec->toArray());
    }

    public function testFilterAndMapChained(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);

        $result = $vec->filter(static fn(int $n) => $n % 2 === 0)  // [2, 4]
            ->map(static fn(int $n) => $n * 10);         // [20, 40]

        $this->assertSame([20, 40], $result->toArray());
    }

    public function testTakeWhileWithIntegers(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5, 6);
        $result = $vec->takeWhile(static fn(int $n) => $n < 4);

        $this->assertSame([1, 2, 3], $result->toArray());
        $this->assertSame([1, 2, 3, 4, 5, 6], $vec->toArray(), 'Original vec should remain unchanged');
    }

    public function testTakeWhileWithAllMatchingItems(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $result = $vec->takeWhile(static fn(int $n) => $n > 0);

        $this->assertSame([1, 2, 3, 4, 5], $result->toArray());
    }

    public function testTakeWhileWithNoMatchingItems(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $result = $vec->takeWhile(static fn(int $n) => $n > 5);

        $this->assertTrue($result->isEmpty());
    }

    public function testTakeWhileWithEmptyVec(): void
    {
        $vec = Vec::new();
        $result = $vec->takeWhile(static fn($n) => true);

        $this->assertTrue($result->isEmpty());
    }

    public function testTakeWhileWithStrings(): void
    {
        $vec = Vec::from('apple', 'banana', 'cherry', 'date', 'elderberry');
        $result = $vec->takeWhile(static fn(string $s) => \strlen($s) < 6);

        $this->assertSame(['apple'], $result->toArray());
    }

    public function testSkipWhileWithIntegers(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5, 6);
        $result = $vec->skipWhile(static fn(int $n) => $n < 4);

        $this->assertSame([4, 5, 6], $result->toArray());
        $this->assertSame([1, 2, 3, 4, 5, 6], $vec->toArray(), 'Original vec should remain unchanged');
    }

    public function testSkipWhileWithAllMatchingItems(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $result = $vec->skipWhile(static fn(int $n) => $n > 0);

        $this->assertTrue($result->isEmpty());
    }

    public function testSkipWhileWithNoMatchingItems(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $result = $vec->skipWhile(static fn(int $n) => $n > 5);

        $this->assertSame([1, 2, 3, 4, 5], $result->toArray());
    }

    public function testSkipWhileWithEmptyVec(): void
    {
        $vec = Vec::new();
        $result = $vec->skipWhile(static fn($n) => true);

        $this->assertTrue($result->isEmpty());
    }

    public function testSkipWhileWithStrings(): void
    {
        $vec = Vec::from('apple', 'banana', 'cherry', 'date', 'elderberry');
        $result = $vec->skipWhile(static fn(string $s) => \strlen($s) < 6);

        $this->assertSame(['banana', 'cherry', 'date', 'elderberry'], $result->toArray());
    }

    public function testTakeWhileAndSkipWhileChained(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

        $result = $vec->takeWhile(static fn(int $n) => $n <= 8)  // [1, 2, 3, 4, 5, 6, 7, 8]
            ->skipWhile(static fn(int $n) => $n < 4);  // [4, 5, 6, 7, 8]

        $this->assertSame([4, 5, 6, 7, 8], $result->toArray());
    }

    public function testFoldWithIntegers(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $sum = $vec->fold(static fn(int $acc, int $n) => $acc + $n, 0);

        $this->assertSame(15, $sum);
        $this->assertSame([1, 2, 3, 4, 5], $vec->toArray(), 'Original vec should remain unchanged');
    }

    public function testFoldWithInitialValue(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $sum = $vec->fold(static fn(int $acc, int $n) => $acc + $n, 10);

        $this->assertSame(25, $sum);
    }

    public function testFoldWithEmptyVec(): void
    {
        $vec = Vec::new();
        $result = $vec->fold(static fn(int $acc, int $n) => $acc + $n, 42);

        $this->assertSame(42, $result, 'Should return the initial value for an empty Vec');
    }

    public function testFoldWithStrings(): void
    {
        $vec = Vec::from('a', 'b', 'c', 'd');
        $result = $vec->fold(static fn(string $acc, string $s) => $acc . $s, '');

        $this->assertSame('abcd', $result);
    }

    public function testFoldWithTypeConversion(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $result = $vec->fold(static fn(string $acc, int $n) => $acc . $n, '');

        $this->assertSame('12345', $result);
    }

    public function testFoldToCreateArray(): void
    {
        $vec = Vec::from('apple', 'banana', 'cherry');
        $result = $vec->fold(static function(array $acc, string $item) {
            $acc[] = \strtoupper($item);

            return $acc;
        }, []);

        $this->assertSame(['APPLE', 'BANANA', 'CHERRY'], $result);
    }

    public function testFoldWithChainedOperations(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

        $sum = $vec->filter(static fn(int $n) => $n % 2 === 0)
            ->fold(static fn(int $acc, int $n) => $acc + $n, 0);

        $this->assertSame(30, $sum); // 2 + 4 + 6 + 8 + 10 = 30
    }

    public function testFoldToFindMaxValue(): void
    {
        $vec = Vec::from(5, 2, 9, 1, 7, 3);
        $max = $vec->fold(static function(int $max, int $value) {
            return \max($value, $max);
        }, \PHP_INT_MIN);

        $this->assertSame(9, $max);
    }

    public function testReverseWithIntegers(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $reversedVec = $vec->reverse();

        $this->assertSame([1, 2, 3, 4, 5], $vec->toArray(), 'Original vec should remain unchanged');
        $this->assertSame([5, 4, 3, 2, 1], $reversedVec->toArray());
    }

    public function testReverseWithStrings(): void
    {
        $vec = Vec::from('a', 'b', 'c');
        $reversedVec = $vec->reverse();

        $this->assertSame(['c', 'b', 'a'], $reversedVec->toArray());
    }

    public function testReverseWithEmptyVec(): void
    {
        $vec = Vec::new();
        $reversedVec = $vec->reverse();

        $this->assertTrue($reversedVec->isEmpty());
    }

    public function testReverseWithSingleElement(): void
    {
        $vec = Vec::from(42);
        $reversedVec = $vec->reverse();

        $this->assertSame([42], $reversedVec->toArray());
        $this->assertSame(1, $reversedVec->len()->toInt());
    }

    public function testReverseWithObjects(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $obj3 = new \stdClass();

        $vec = Vec::from($obj1, $obj2, $obj3);
        $reversedVec = $vec->reverse();

        $this->assertSame([$obj3, $obj2, $obj1], $reversedVec->toArray());
    }

    public function testReverseAndThenReverse(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $doubleReversedVec = $vec->reverse()->reverse();

        $this->assertSame([1, 2, 3, 4, 5], $doubleReversedVec->toArray(), 'Double reverse should return to original order');
    }

    public function testFlatMapWithIntegers(): void
    {
        $vec = Vec::from(1, 2, 3);
        $result = $vec->flatMap(static fn($n) => [$n, $n * 2]);

        $this->assertSame([1, 2, 2, 4, 3, 6], $result->toArray());
        $this->assertSame([1, 2, 3], $vec->toArray(), 'Original vec should remain unchanged');
    }

    public function testFlatMapWithEmptyVec(): void
    {
        $vec = Vec::new();
        $result = $vec->flatMap(static fn($n) => [$n, $n * 2]);

        $this->assertTrue($result->isEmpty());
    }

    public function testFlatMapWithEmptyResults(): void
    {
        $vec = Vec::from(1, 2, 3);
        $result = $vec->flatMap(static fn($n) => []);

        $this->assertTrue($result->isEmpty());
    }

    public function testFlatMapWithNestedArrays(): void
    {
        $vec = Vec::from('a', 'b');
        $result = $vec->flatMap(static fn($c) => [[$c], [$c, $c]]);

        $this->assertSame(4, $result->len()->toInt());
        $this->assertTrue(\is_array($result->get(0)->match(static fn($v) => $v, static fn() => null)));
    }

    public function testFlatMapWithObjects(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();

        $vec = Vec::from($obj1);
        $result = $vec->flatMap(static fn($obj) => [$obj, $obj2]);

        $this->assertSame(2, $result->len()->toInt());
        $this->assertSame($obj1, $result->get(0)->match(static fn($v) => $v, static fn() => null));
        $this->assertSame($obj2, $result->get(1)->match(static fn($v) => $v, static fn() => null));
    }

    public function testFlatMapWithNestedVecs(): void
    {
        $vec = Vec::from(1, 2);
        $result = $vec->flatMap(static fn($n) => [Vec::from($n, $n * 2)]);

        $this->assertSame(2, $result->len()->toInt());
        $firstItem = $result->get(0)->match(static fn($v) => $v, static fn() => null);
        $this->assertInstanceOf(Vec::class, $firstItem);
    }

    public function testFlatMapChainedWithOtherOperations(): void
    {
        $vec = Vec::from(1, 2, 3);
        $result = $vec
            ->flatMap(static fn($n) => [$n, $n * 2])
            ->filter(static fn($n) => $n % 2 === 0)
            ->map(static fn($n) => $n * 10);

        $this->assertSame([20, 20, 40, 60], $result->toArray());
    }

    public function testFlattenWithNestedVecs(): void
    {
        $nestedVec = Vec::from(
            Vec::from(1, 2),
            Vec::from(3, 4),
        );
        $flattened = $nestedVec->flatten();

        $this->assertSame([1, 2, 3, 4], $flattened->toArray());
        $this->assertSame(4, $flattened->len()->toInt());
    }

    public function testFlattenWithEmptyVec(): void
    {
        $vec = Vec::new();
        $flattened = $vec->flatten();

        $this->assertTrue($flattened->isEmpty());
    }

    public function testFlattenWithMixedContent(): void
    {
        $vec = Vec::from(
            1,
            [2, 3],
            Vec::from(4, 5),
        );
        $flattened = $vec->flatten();

        $this->assertSame([1, 2, 3, 4, 5], $flattened->toArray());
    }

    public function testFlattenWithNestedEmpty(): void
    {
        $vec = Vec::from(
            Vec::new(),
            Vec::from(1, 2),
        );
        $flattened = $vec->flatten();

        $this->assertSame([1, 2], $flattened->toArray());
    }

    public function testFlattenOnNonNestedVec(): void
    {
        $vec = Vec::from(1, 2, 3);
        $flattened = $vec->flatten();

        $this->assertSame([1, 2, 3], $flattened->toArray());
    }

    public function testSortWithIntegers(): void
    {
        $vec = Vec::from(5, 3, 1, 4, 2);
        $sorted = $vec->sort();

        $this->assertSame([5, 3, 1, 4, 2], $vec->toArray(), 'Original vec should remain unchanged');
        $this->assertSame([1, 2, 3, 4, 5], $sorted->toArray());
    }

    public function testSortWithFloats(): void
    {
        $vec = Vec::from(3.5, 1.2, 4.8, 2.1);
        $sorted = $vec->sort();

        $this->assertSame([1.2, 2.1, 3.5, 4.8], $sorted->toArray());
    }

    public function testSortWithStrings(): void
    {
        $vec = Vec::from('banana', 'apple', 'cherry', 'date');
        $sorted = $vec->sort();

        $this->assertSame(['apple', 'banana', 'cherry', 'date'], $sorted->toArray());
    }

    public function testSortWithMixedCase(): void
    {
        $vec = Vec::from('Apple', 'banana', 'Cherry', 'date');
        $sorted = $vec->sort();

        $this->assertSame(['Apple', 'Cherry', 'banana', 'date'], $sorted->toArray());
    }

    public function testSortWithEmptyVec(): void
    {
        $vec = Vec::new();
        $sorted = $vec->sort();

        $this->assertTrue($sorted->isEmpty());
    }

    public function testSortWithSingleElement(): void
    {
        $vec = Vec::from(42);
        $sorted = $vec->sort();

        $this->assertSame([42], $sorted->toArray());
    }

    public function testSortByWithCustomComparator(): void
    {
        $vec = Vec::from(5, 3, 1, 4, 2);

        $sortedDescending = $vec->sortBy(static fn($a, $b) => $b <=> $a);

        $this->assertSame([5, 3, 1, 4, 2], $vec->toArray(), 'Original vec should remain unchanged');
        $this->assertSame([5, 4, 3, 2, 1], $sortedDescending->toArray());
    }

    public function testSortByWithStringLength(): void
    {
        $vec = Vec::from('apple', 'banana', 'kiwi', 'pear', 'strawberry');

        $sortedByLength = $vec->sortBy(static fn($a, $b) => \strlen($a) <=> \strlen($b));

        $this->assertSame(['kiwi', 'pear', 'apple', 'banana', 'strawberry'], $sortedByLength->toArray());
    }

    public function testSortByWithObjects(): void
    {
        $obj1 = new \stdClass();
        $obj1->value = 3;

        $obj2 = new \stdClass();
        $obj2->value = 1;

        $obj3 = new \stdClass();
        $obj3->value = 2;

        $vec = Vec::from($obj1, $obj2, $obj3);

        $sortedByValue = $vec->sortBy(static fn($a, $b) => $a->value <=> $b->value);

        $firstValue = $sortedByValue->get(0)->match(static fn($v) => $v->value, static fn() => null);
        $secondValue = $sortedByValue->get(1)->match(static fn($v) => $v->value, static fn() => null);
        $thirdValue = $sortedByValue->get(2)->match(static fn($v) => $v->value, static fn() => null);

        $this->assertSame(1, $firstValue);
        $this->assertSame(2, $secondValue);
        $this->assertSame(3, $thirdValue);
    }

    public function testSortByCaseInsensitive(): void
    {
        $vec = Vec::from('Apple', 'banana', 'Cherry', 'date');

        $sortedCaseInsensitive = $vec->sortBy(static fn($a, $b) => \strcasecmp($a, $b));

        $this->assertSame(['Apple', 'banana', 'Cherry', 'date'], $sortedCaseInsensitive->toArray());
    }

    public function testSortByWithEmptyVec(): void
    {
        $vec = Vec::new();
        $sorted = $vec->sortBy(static fn($a, $b) => $a <=> $b);

        $this->assertTrue($sorted->isEmpty());
    }

    public function testSortVsCustomSortBy(): void
    {
        $vec = Vec::from(5, 3, 1, 4, 2);

        $regularSort = $vec->sort();
        $equivalentSortBy = $vec->sortBy(static fn($a, $b) => $a <=> $b);

        $this->assertSame($regularSort->toArray(), $equivalentSortBy->toArray(),
            'sort() and sortBy() with default comparator should produce the same result');
    }

    public function testFilterMapWithSomeResults(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $result = $vec->filterMap(static function($n) {
            return $n % 2 === 0 ? Option::some($n * 2) : Option::none();
        });

        $this->assertSame([4, 8], $result->toArray());
        $this->assertSame([1, 2, 3, 4, 5], $vec->toArray(), 'Original vec should remain unchanged');
    }

    public function testFilterMapWithAllSome(): void
    {
        $vec = Vec::from(1, 2, 3);
        $result = $vec->filterMap(static function($n) {
            return Option::some($n * 10);
        });

        $this->assertSame([10, 20, 30], $result->toArray());
    }

    public function testFilterMapWithAllNone(): void
    {
        $vec = Vec::from(1, 2, 3);
        $result = $vec->filterMap(static function($n) {
            return Option::none();
        });

        $this->assertTrue($result->isEmpty());
    }

    public function testFilterMapWithEmptyVec(): void
    {
        $vec = Vec::new();
        $result = $vec->filterMap(static function($n) {
            return Option::some($n);
        });

        $this->assertTrue($result->isEmpty());
    }

    public function testFilterMapWithObjects(): void
    {
        $obj1 = new \stdClass();
        $obj1->value = 10;

        $obj2 = new \stdClass();
        $obj2->value = 5;

        $vec = Vec::from($obj1, $obj2);
        $result = $vec->filterMap(static function($obj) {
            return $obj->value > 7 ? Option::some($obj->value) : Option::none();
        });

        $this->assertSame([10], $result->toArray());
    }

    public function testFindMapWithMatch(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $result = $vec->findMap(static function($n) {
            return $n % 2 === 0 ? Option::some($n * 10) : Option::none();
        });

        $this->assertTrue($result->isSome());
        $this->assertSame(20, $result->unwrap());
        $this->assertSame([1, 2, 3, 4, 5], $vec->toArray(), 'Original vec should remain unchanged');
    }

    public function testFindMapWithNoMatch(): void
    {
        $vec = Vec::from(1, 3, 5, 7, 9);
        $result = $vec->findMap(static function($n) {
            return $n % 2 === 0 ? Option::some($n * 10) : Option::none();
        });

        $this->assertTrue($result->isNone());
    }

    public function testFindMapWithEmptyVec(): void
    {
        $vec = Vec::new();
        $result = $vec->findMap(static function($n) {
            return Option::some($n);
        });

        $this->assertTrue($result->isNone());
    }

    public function testFindMapFirstMatchOnly(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $count = 0;

        $result = $vec->findMap(static function($n) use (&$count) {
            $count++;

            return $n === 3 ? Option::some($n * 10) : Option::none();
        });

        $this->assertTrue($result->isSome());
        $this->assertSame(30, $result->unwrap());
        $this->assertSame(3, $count, 'Should stop after finding the match');
    }

    public function testZipWithEqualSizeVecs(): void
    {
        $vec1 = Vec::from(1, 2, 3);
        $vec2 = Vec::from('a', 'b', 'c');

        $zipped = $vec1->zip($vec2);

        $this->assertSame(3, $zipped->len()->toInt());
        $this->assertSame([1, 2, 3], $vec1->toArray(), 'Original vec1 should remain unchanged');
        $this->assertSame(['a', 'b', 'c'], $vec2->toArray(), 'Original vec2 should remain unchanged');

        $firstPair = $zipped->get(0)->match(static fn($v) => $v, static fn() => null);
        $this->assertInstanceOf(Vec::class, $firstPair);
        $this->assertSame([1, 'a'], $firstPair->toArray());

        $secondPair = $zipped->get(1)->match(static fn($v) => $v, static fn() => null);
        $this->assertSame([2, 'b'], $secondPair->toArray());

        $thirdPair = $zipped->get(2)->match(static fn($v) => $v, static fn() => null);
        $this->assertSame([3, 'c'], $thirdPair->toArray());
    }

    public function testZipWithVecsDifferentLengths(): void
    {
        $vec1 = Vec::from(1, 2, 3, 4);
        $vec2 = Vec::from('a', 'b');

        $zipped = $vec1->zip($vec2);

        $this->assertSame(2, $zipped->len()->toInt());

        $firstPair = $zipped->get(0)->match(static fn($v) => $v, static fn() => null);
        $this->assertSame([1, 'a'], $firstPair->toArray());

        $secondPair = $zipped->get(1)->match(static fn($v) => $v, static fn() => null);
        $this->assertSame([2, 'b'], $secondPair->toArray());
    }

    public function testZipWithEmptyVec(): void
    {
        $vec1 = Vec::from(1, 2, 3);
        $vec2 = Vec::new();

        $zipped = $vec1->zip($vec2);

        $this->assertTrue($zipped->isEmpty());
    }

    public function testZipWithObjectElements(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $obj3 = new \stdClass();
        $obj4 = new \stdClass();

        $vec1 = Vec::from($obj1, $obj2);
        $vec2 = Vec::from($obj3, $obj4);

        $zipped = $vec1->zip($vec2);

        $this->assertSame(2, $zipped->len()->toInt());

        $firstPair = $zipped->get(0)->match(static fn($v) => $v, static fn() => null);
        $this->assertSame([$obj1, $obj3], $firstPair->toArray());

        $secondPair = $zipped->get(1)->match(static fn($v) => $v, static fn() => null);
        $this->assertSame([$obj2, $obj4], $secondPair->toArray());
    }
}
