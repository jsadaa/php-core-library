<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Sequence\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use Jsadaa\PhpCoreLibrary\Modules\Option\Option;
use PHPUnit\Framework\TestCase;

final class SequenceOperationsTest extends TestCase
{
    public function testAppendTwoSequencesOfSameType(): void
    {
        $seq1 = Sequence::from(1, 2);
        $seq2 = Sequence::from(3, 4);

        $result = $seq1->append($seq2);

        $this->assertSame([1, 2], $seq1->toArray(), 'Original Sequence1 should remain unchanged');
        $this->assertSame([3, 4], $seq2->toArray(), 'Original Sequence2 should remain unchanged');
        $this->assertSame([1, 2, 3, 4], $result->toArray());
    }

    public function testAppendEmptySequenceToNonEmptySequence(): void
    {
        $seq1 = Sequence::from('a', 'b');
        $seq2 = Sequence::new();

        $result = $seq1->append($seq2);

        $this->assertSame(['a', 'b'], $result->toArray());
    }

    public function testAppendNonEmptySequenceToEmptySequence(): void
    {
        $seq1 = Sequence::new();
        $seq2 = Sequence::from('a', 'b');

        $result = $seq1->append($seq2);

        $this->assertSame(['a', 'b'], $result->toArray());
    }

    public function testAppendTwoEmptySequences(): void
    {
        $seq1 = Sequence::new();
        $seq2 = Sequence::new();

        $result = $seq1->append($seq2);

        $this->assertTrue($result->isEmpty());
    }

    public function testAppendSequencesOfDifferentTypes(): void
    {
        $seq1 = Sequence::from(1, 2);
        $seq2 = Sequence::from('a', 'b');

        $result = $seq1->append($seq2);

        $this->assertSame([1, 2], $seq1->toArray(), 'Original Sequence1 should remain unchanged');
        $this->assertSame(['a', 'b'], $seq2->toArray(), 'Original Sequence2 should remain unchanged');
        $this->assertSame([1, 2, 'a', 'b'], $result->toArray());
    }

    public function testAppendSameSequenceToItself(): void
    {
        $seq = Sequence::from(1, 2);

        $result = $seq->append($seq);

        $this->assertSame([1, 2, 1, 2], $result->toArray());
    }

    public function testMapOnNonEmptySequence(): void
    {
        $seq = Sequence::from(1, 2, 3);
        $mappedSequence = $seq->map(static fn(int $n) => $n * 2);

        $this->assertSame([1, 2, 3], $seq->toArray(), 'Original Sequence should remain unchanged');
        $this->assertSame([2, 4, 6], $mappedSequence->toArray());
    }

    public function testMapChangingTypeOnNonEmptySequence(): void
    {
        $seq = Sequence::from(1, 2, 3);
        $mappedSequence = $seq->map(static fn(int $n) => (string)$n);

        $this->assertSame(['1', '2', '3'], $mappedSequence->toArray());
    }

    public function testMapOnEmptySequence(): void
    {
        $seq = Sequence::new();
        $mappedSequence = $seq->map(static fn(int $n) => $n * 2);

        $this->assertTrue($mappedSequence->isEmpty());
    }

    public function testMapToObjects(): void
    {
        $seq = Sequence::from(1, 2, 3);

        $mappedSequence = $seq->map(static function($n) {
            $obj = new \stdClass();
            $obj->value = $n;

            return $obj;
        });

        $this->assertSame(3, $mappedSequence->len()->toInt());

        $firstObj = $mappedSequence->get(0)->match(static fn($v) => $v, static fn() => null);
        $this->assertInstanceOf(\stdClass::class, $firstObj);
        $this->assertSame(1, $firstObj->value);
    }

    public function testFilterOnNonEmptySequence(): void
    {
        $seq = Sequence::from(1, 2, 3, 4, 5);
        $filteredSequence = $seq->filter(static fn(int $n) => $n % 2 === 0);

        $this->assertSame([1, 2, 3, 4, 5], $seq->toArray(), 'Original Sequence should remain unchanged');
        $this->assertSame([2, 4], $filteredSequence->toArray());
    }

    public function testFilterWithAllMatchingItems(): void
    {
        $seq = Sequence::from(1, 2, 3, 4, 5);
        $filteredSequence = $seq->filter(static fn(int $n) => $n > 0);

        $this->assertSame([1, 2, 3, 4, 5], $filteredSequence->toArray());
    }

    public function testFilterAndMapChained(): void
    {
        $seq = Sequence::from(1, 2, 3, 4, 5);

        $result = $seq->filter(static fn(int $n) => $n % 2 === 0)  // [2, 4]
            ->map(static fn(int $n) => $n * 10);         // [20, 40]

        $this->assertSame([20, 40], $result->toArray());
    }

    public function testTakeWhileWithIntegers(): void
    {
        $seq = Sequence::from(1, 2, 3, 4, 5, 6);
        $result = $seq->takeWhile(static fn(int $n) => $n < 4);

        $this->assertSame([1, 2, 3], $result->toArray());
        $this->assertSame([1, 2, 3, 4, 5, 6], $seq->toArray(), 'Original Sequence should remain unchanged');
    }

    public function testTakeWhileWithAllMatchingItems(): void
    {
        $seq = Sequence::from(1, 2, 3, 4, 5);
        $result = $seq->takeWhile(static fn(int $n) => $n > 0);

        $this->assertSame([1, 2, 3, 4, 5], $result->toArray());
    }

    public function testTakeWhileWithNoMatchingItems(): void
    {
        $seq = Sequence::from(1, 2, 3, 4, 5);
        $result = $seq->takeWhile(static fn(int $n) => $n > 5);

        $this->assertTrue($result->isEmpty());
    }

    public function testTakeWhileWithEmptySequence(): void
    {
        $seq = Sequence::new();
        $result = $seq->takeWhile(static fn($n) => true);

        $this->assertTrue($result->isEmpty());
    }

    public function testTakeWhileWithStrings(): void
    {
        $seq = Sequence::from('apple', 'banana', 'cherry', 'date', 'elderberry');
        $result = $seq->takeWhile(static fn(string $s) => \strlen($s) < 6);

        $this->assertSame(['apple'], $result->toArray());
    }

    public function testSkipWhileWithIntegers(): void
    {
        $seq = Sequence::from(1, 2, 3, 4, 5, 6);
        $result = $seq->skipWhile(static fn(int $n) => $n < 4);

        $this->assertSame([4, 5, 6], $result->toArray());
        $this->assertSame([1, 2, 3, 4, 5, 6], $seq->toArray(), 'Original Sequence should remain unchanged');
    }

    public function testSkipWhileWithAllMatchingItems(): void
    {
        $seq = Sequence::from(1, 2, 3, 4, 5);
        $result = $seq->skipWhile(static fn(int $n) => $n > 0);

        $this->assertTrue($result->isEmpty());
    }

    public function testSkipWhileWithNoMatchingItems(): void
    {
        $seq = Sequence::from(1, 2, 3, 4, 5);
        $result = $seq->skipWhile(static fn(int $n) => $n > 5);

        $this->assertSame([1, 2, 3, 4, 5], $result->toArray());
    }

    public function testSkipWhileWithEmptySequence(): void
    {
        $seq = Sequence::new();
        $result = $seq->skipWhile(static fn($n) => true);

        $this->assertTrue($result->isEmpty());
    }

    public function testSkipWhileWithStrings(): void
    {
        $seq = Sequence::from('apple', 'banana', 'cherry', 'date', 'elderberry');
        $result = $seq->skipWhile(static fn(string $s) => \strlen($s) < 6);

        $this->assertSame(['banana', 'cherry', 'date', 'elderberry'], $result->toArray());
    }

    public function testTakeWhileAndSkipWhileChained(): void
    {
        $seq = Sequence::from(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

        $result = $seq->takeWhile(static fn(int $n) => $n <= 8)  // [1, 2, 3, 4, 5, 6, 7, 8]
            ->skipWhile(static fn(int $n) => $n < 4);  // [4, 5, 6, 7, 8]

        $this->assertSame([4, 5, 6, 7, 8], $result->toArray());
    }

    public function testFoldWithIntegers(): void
    {
        $seq = Sequence::from(1, 2, 3, 4, 5);
        $sum = $seq->fold(static fn(int $acc, int $n) => $acc + $n, 0);

        $this->assertSame(15, $sum);
        $this->assertSame([1, 2, 3, 4, 5], $seq->toArray(), 'Original Sequence should remain unchanged');
    }

    public function testFoldWithInitialValue(): void
    {
        $seq = Sequence::from(1, 2, 3, 4, 5);
        $sum = $seq->fold(static fn(int $acc, int $n) => $acc + $n, 10);

        $this->assertSame(25, $sum);
    }

    public function testFoldWithEmptySequence(): void
    {
        $seq = Sequence::new();
        $result = $seq->fold(static fn(int $acc, int $n) => $acc + $n, 42);

        $this->assertSame(42, $result, 'Should return the initial value for an empty Sequence');
    }

    public function testFoldWithStrings(): void
    {
        $seq = Sequence::from('a', 'b', 'c', 'd');
        $result = $seq->fold(static fn(string $acc, string $s) => $acc . $s, '');

        $this->assertSame('abcd', $result);
    }

    public function testFoldWithTypeConversion(): void
    {
        $seq = Sequence::from(1, 2, 3, 4, 5);
        $result = $seq->fold(static fn(string $acc, int $n) => $acc . $n, '');

        $this->assertSame('12345', $result);
    }

    public function testFoldToCreateArray(): void
    {
        $seq = Sequence::from('apple', 'banana', 'cherry');
        $result = $seq->fold(static function(array $acc, string $item) {
            $acc[] = \strtoupper($item);

            return $acc;
        }, []);

        $this->assertSame(['APPLE', 'BANANA', 'CHERRY'], $result);
    }

    public function testFoldWithChainedOperations(): void
    {
        $seq = Sequence::from(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

        $sum = $seq->filter(static fn(int $n) => $n % 2 === 0)
            ->fold(static fn(int $acc, int $n) => $acc + $n, 0);

        $this->assertSame(30, $sum); // 2 + 4 + 6 + 8 + 10 = 30
    }

    public function testFoldToFindMaxValue(): void
    {
        $seq = Sequence::from(5, 2, 9, 1, 7, 3);
        $max = $seq->fold(static function(int $max, int $value) {
            return \max($value, $max);
        }, \PHP_INT_MIN);

        $this->assertSame(9, $max);
    }

    public function testReverseWithIntegers(): void
    {
        $seq = Sequence::from(1, 2, 3, 4, 5);
        $reversedSequence = $seq->reverse();

        $this->assertSame([1, 2, 3, 4, 5], $seq->toArray(), 'Original Sequence should remain unchanged');
        $this->assertSame([5, 4, 3, 2, 1], $reversedSequence->toArray());
    }

    public function testReverseWithStrings(): void
    {
        $seq = Sequence::from('a', 'b', 'c');
        $reversedSequence = $seq->reverse();

        $this->assertSame(['c', 'b', 'a'], $reversedSequence->toArray());
    }

    public function testReverseWithEmptySequence(): void
    {
        $seq = Sequence::new();
        $reversedSequence = $seq->reverse();

        $this->assertTrue($reversedSequence->isEmpty());
    }

    public function testReverseWithSingleElement(): void
    {
        $seq = Sequence::from(42);
        $reversedSequence = $seq->reverse();

        $this->assertSame([42], $reversedSequence->toArray());
        $this->assertSame(1, $reversedSequence->len()->toInt());
    }

    public function testReverseWithObjects(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $obj3 = new \stdClass();

        $seq = Sequence::from($obj1, $obj2, $obj3);
        $reversedSequence = $seq->reverse();

        $this->assertSame([$obj3, $obj2, $obj1], $reversedSequence->toArray());
    }

    public function testReverseAndThenReverse(): void
    {
        $seq = Sequence::from(1, 2, 3, 4, 5);
        $doubleReversedSequence = $seq->reverse()->reverse();

        $this->assertSame([1, 2, 3, 4, 5], $doubleReversedSequence->toArray(), 'Double reverse should return to original order');
    }

    public function testFlatMapWithIntegers(): void
    {
        $seq = Sequence::from(1, 2, 3);
        $result = $seq->flatMap(static fn($n) => [$n, $n * 2]);

        $this->assertSame([1, 2, 2, 4, 3, 6], $result->toArray());
        $this->assertSame([1, 2, 3], $seq->toArray(), 'Original Sequence should remain unchanged');
    }

    public function testFlatMapWithEmptySequence(): void
    {
        $seq = Sequence::new();
        $result = $seq->flatMap(static fn($n) => [$n, $n * 2]);

        $this->assertTrue($result->isEmpty());
    }

    public function testFlatMapWithEmptyResults(): void
    {
        $seq = Sequence::from(1, 2, 3);
        $result = $seq->flatMap(static fn($n) => []);

        $this->assertTrue($result->isEmpty());
    }

    public function testFlatMapWithNestedArrays(): void
    {
        $seq = Sequence::from('a', 'b');
        $result = $seq->flatMap(static fn($c) => [[$c], [$c, $c]]);

        $this->assertSame(4, $result->len()->toInt());
        $this->assertTrue(\is_array($result->get(0)->match(static fn($v) => $v, static fn() => null)));
    }

    public function testFlatMapWithObjects(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();

        $seq = Sequence::from($obj1);
        $result = $seq->flatMap(static fn($obj) => [$obj, $obj2]);

        $this->assertSame(2, $result->len()->toInt());
        $this->assertSame($obj1, $result->get(0)->match(static fn($v) => $v, static fn() => null));
        $this->assertSame($obj2, $result->get(1)->match(static fn($v) => $v, static fn() => null));
    }

    public function testFlatMapWithNestedSequences(): void
    {
        $seq = Sequence::from(1, 2);
        $result = $seq->flatMap(static fn($n) => [Sequence::from($n, $n * 2)]);

        $this->assertSame(2, $result->len()->toInt());
        $firstItem = $result->get(0)->match(static fn($v) => $v, static fn() => null);
        $this->assertInstanceOf(Sequence::class, $firstItem);
    }

    public function testFlatMapChainedWithOtherOperations(): void
    {
        $seq = Sequence::from(1, 2, 3);
        $result = $seq
            ->flatMap(static fn($n) => [$n, $n * 2])
            ->filter(static fn($n) => $n % 2 === 0)
            ->map(static fn($n) => $n * 10);

        $this->assertSame([20, 20, 40, 60], $result->toArray());
    }

    public function testFlattenWithNestedSequences(): void
    {
        $nestedSequence = Sequence::from(
            Sequence::from(1, 2),
            Sequence::from(3, 4),
        );
        $flattened = $nestedSequence->flatten();

        $this->assertSame([1, 2, 3, 4], $flattened->toArray());
        $this->assertSame(4, $flattened->len()->toInt());
    }

    public function testFlattenWithEmptySequence(): void
    {
        $seq = Sequence::new();
        $flattened = $seq->flatten();

        $this->assertTrue($flattened->isEmpty());
    }

    public function testFlattenWithMixedContent(): void
    {
        $seq = Sequence::from(
            1,
            [2, 3],
            Sequence::from(4, 5),
        );
        $flattened = $seq->flatten();

        $this->assertSame([1, 2, 3, 4, 5], $flattened->toArray());
    }

    public function testFlattenWithNestedEmpty(): void
    {
        $seq = Sequence::from(
            Sequence::new(),
            Sequence::from(1, 2),
        );
        $flattened = $seq->flatten();

        $this->assertSame([1, 2], $flattened->toArray());
    }

    public function testFlattenOnNonNestedSequence(): void
    {
        $seq = Sequence::from(1, 2, 3);
        $flattened = $seq->flatten();

        $this->assertSame([1, 2, 3], $flattened->toArray());
    }

    public function testSortWithIntegers(): void
    {
        $seq = Sequence::from(5, 3, 1, 4, 2);
        $sorted = $seq->sort();

        $this->assertSame([5, 3, 1, 4, 2], $seq->toArray(), 'Original Sequence should remain unchanged');
        $this->assertSame([1, 2, 3, 4, 5], $sorted->toArray());
    }

    public function testSortWithFloats(): void
    {
        $seq = Sequence::from(3.5, 1.2, 4.8, 2.1);
        $sorted = $seq->sort();

        $this->assertSame([1.2, 2.1, 3.5, 4.8], $sorted->toArray());
    }

    public function testSortWithStrings(): void
    {
        $seq = Sequence::from('banana', 'apple', 'cherry', 'date');
        $sorted = $seq->sort();

        $this->assertSame(['apple', 'banana', 'cherry', 'date'], $sorted->toArray());
    }

    public function testSortWithMixedCase(): void
    {
        $seq = Sequence::from('Apple', 'banana', 'Cherry', 'date');
        $sorted = $seq->sort();

        $this->assertSame(['Apple', 'Cherry', 'banana', 'date'], $sorted->toArray());
    }

    public function testSortWithEmptySequence(): void
    {
        $seq = Sequence::new();
        $sorted = $seq->sort();

        $this->assertTrue($sorted->isEmpty());
    }

    public function testSortWithSingleElement(): void
    {
        $seq = Sequence::from(42);
        $sorted = $seq->sort();

        $this->assertSame([42], $sorted->toArray());
    }

    public function testSortByWithCustomComparator(): void
    {
        $seq = Sequence::from(5, 3, 1, 4, 2);

        $sortedDescending = $seq->sortBy(static fn($a, $b) => $b <=> $a);

        $this->assertSame([5, 3, 1, 4, 2], $seq->toArray(), 'Original Sequence should remain unchanged');
        $this->assertSame([5, 4, 3, 2, 1], $sortedDescending->toArray());
    }

    public function testSortByWithStringLength(): void
    {
        $seq = Sequence::from('apple', 'banana', 'kiwi', 'pear', 'strawberry');

        $sortedByLength = $seq->sortBy(static fn($a, $b) => \strlen($a) <=> \strlen($b));

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

        $seq = Sequence::from($obj1, $obj2, $obj3);

        $sortedByValue = $seq->sortBy(static fn($a, $b) => $a->value <=> $b->value);

        $firstValue = $sortedByValue->get(0)->match(static fn($v) => $v->value, static fn() => null);
        $secondValue = $sortedByValue->get(1)->match(static fn($v) => $v->value, static fn() => null);
        $thirdValue = $sortedByValue->get(2)->match(static fn($v) => $v->value, static fn() => null);

        $this->assertSame(1, $firstValue);
        $this->assertSame(2, $secondValue);
        $this->assertSame(3, $thirdValue);
    }

    public function testSortByCaseInsensitive(): void
    {
        $seq = Sequence::from('Apple', 'banana', 'Cherry', 'date');

        $sortedCaseInsensitive = $seq->sortBy(static fn($a, $b) => \strcasecmp($a, $b));

        $this->assertSame(['Apple', 'banana', 'Cherry', 'date'], $sortedCaseInsensitive->toArray());
    }

    public function testSortByWithEmptySequence(): void
    {
        $seq = Sequence::new();
        $sorted = $seq->sortBy(static fn($a, $b) => $a <=> $b);

        $this->assertTrue($sorted->isEmpty());
    }

    public function testSortVsCustomSortBy(): void
    {
        $seq = Sequence::from(5, 3, 1, 4, 2);

        $regularSort = $seq->sort();
        $equivalentSortBy = $seq->sortBy(static fn($a, $b) => $a <=> $b);

        $this->assertSame($regularSort->toArray(), $equivalentSortBy->toArray(),
            'sort() and sortBy() with default comparator should produce the same result');
    }

    public function testFilterMapWithSomeResults(): void
    {
        $seq = Sequence::from(1, 2, 3, 4, 5);
        $result = $seq->filterMap(static function($n) {
            return $n % 2 === 0 ? Option::some($n * 2) : Option::none();
        });

        $this->assertSame([4, 8], $result->toArray());
        $this->assertSame([1, 2, 3, 4, 5], $seq->toArray(), 'Original Sequence should remain unchanged');
    }

    public function testFilterMapWithAllSome(): void
    {
        $seq = Sequence::from(1, 2, 3);
        $result = $seq->filterMap(static function($n) {
            return Option::some($n * 10);
        });

        $this->assertSame([10, 20, 30], $result->toArray());
    }

    public function testFilterMapWithAllNone(): void
    {
        $seq = Sequence::from(1, 2, 3);
        $result = $seq->filterMap(static function($n) {
            return Option::none();
        });

        $this->assertTrue($result->isEmpty());
    }

    public function testFilterMapWithEmptySequence(): void
    {
        $seq = Sequence::new();
        $result = $seq->filterMap(static function($n) {
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

        $seq = Sequence::from($obj1, $obj2);
        $result = $seq->filterMap(static function($obj) {
            return $obj->value > 7 ? Option::some($obj->value) : Option::none();
        });

        $this->assertSame([10], $result->toArray());
    }

    public function testFindMapWithMatch(): void
    {
        $seq = Sequence::from(1, 2, 3, 4, 5);
        $result = $seq->findMap(static function($n) {
            return $n % 2 === 0 ? Option::some($n * 10) : Option::none();
        });

        $this->assertTrue($result->isSome());
        $this->assertSame(20, $result->unwrap());
        $this->assertSame([1, 2, 3, 4, 5], $seq->toArray(), 'Original Sequence should remain unchanged');
    }

    public function testFindMapWithNoMatch(): void
    {
        $seq = Sequence::from(1, 3, 5, 7, 9);
        $result = $seq->findMap(static function($n) {
            return $n % 2 === 0 ? Option::some($n * 10) : Option::none();
        });

        $this->assertTrue($result->isNone());
    }

    public function testFindMapWithEmptySequence(): void
    {
        $seq = Sequence::new();
        $result = $seq->findMap(static function($n) {
            return Option::some($n);
        });

        $this->assertTrue($result->isNone());
    }

    public function testFindMapFirstMatchOnly(): void
    {
        $seq = Sequence::from(1, 2, 3, 4, 5);
        $count = 0;

        $result = $seq->findMap(static function($n) use (&$count) {
            $count++;

            return $n === 3 ? Option::some($n * 10) : Option::none();
        });

        $this->assertTrue($result->isSome());
        $this->assertSame(30, $result->unwrap());
        $this->assertSame(3, $count, 'Should stop after finding the match');
    }

    public function testZipWithEqualSizeSequences(): void
    {
        $seq1 = Sequence::from(1, 2, 3);
        $seq2 = Sequence::from('a', 'b', 'c');

        $zipped = $seq1->zip($seq2);

        $this->assertSame(3, $zipped->len()->toInt());
        $this->assertSame([1, 2, 3], $seq1->toArray(), 'Original Sequence1 should remain unchanged');
        $this->assertSame(['a', 'b', 'c'], $seq2->toArray(), 'Original Sequence2 should remain unchanged');

        $firstPair = $zipped->get(0)->match(static fn($v) => $v, static fn() => null);
        $this->assertInstanceOf(Sequence::class, $firstPair);
        $this->assertSame([1, 'a'], $firstPair->toArray());

        $secondPair = $zipped->get(1)->match(static fn($v) => $v, static fn() => null);
        $this->assertSame([2, 'b'], $secondPair->toArray());

        $thirdPair = $zipped->get(2)->match(static fn($v) => $v, static fn() => null);
        $this->assertSame([3, 'c'], $thirdPair->toArray());
    }

    public function testZipWithSequencesDifferentLengths(): void
    {
        $seq1 = Sequence::from(1, 2, 3, 4);
        $seq2 = Sequence::from('a', 'b');

        $zipped = $seq1->zip($seq2);

        $this->assertSame(2, $zipped->len()->toInt());

        $firstPair = $zipped->get(0)->match(static fn($v) => $v, static fn() => null);
        $this->assertSame([1, 'a'], $firstPair->toArray());

        $secondPair = $zipped->get(1)->match(static fn($v) => $v, static fn() => null);
        $this->assertSame([2, 'b'], $secondPair->toArray());
    }

    public function testZipWithEmptySequence(): void
    {
        $seq1 = Sequence::from(1, 2, 3);
        $seq2 = Sequence::new();

        $zipped = $seq1->zip($seq2);

        $this->assertTrue($zipped->isEmpty());
    }

    public function testZipWithObjectElements(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $obj3 = new \stdClass();
        $obj4 = new \stdClass();

        $seq1 = Sequence::from($obj1, $obj2);
        $seq2 = Sequence::from($obj3, $obj4);

        $zipped = $seq1->zip($seq2);

        $this->assertSame(2, $zipped->len()->toInt());

        $firstPair = $zipped->get(0)->match(static fn($v) => $v, static fn() => null);
        $this->assertSame([$obj1, $obj3], $firstPair->toArray());

        $secondPair = $zipped->get(1)->match(static fn($v) => $v, static fn() => null);
        $this->assertSame([$obj2, $obj4], $secondPair->toArray());
    }
}
