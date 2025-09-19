<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Sequence\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use PHPUnit\Framework\TestCase;

final class SequenceInspectionTest extends TestCase
{
    public function testIsEmptyOnEmptySequence(): void
    {
        $seq = Sequence::new();

        $this->assertTrue($seq->isEmpty());
    }

    public function testIsEmptyOnNonEmptySequence(): void
    {
        $seq = Sequence::from(1);

        $this->assertFalse($seq->isEmpty());
    }

    public function testIsEmptyAfterPushingItem(): void
    {
        $seq = Sequence::new();
        $this->assertTrue($seq->isEmpty());

        $seq = $seq->push('item');
        $this->assertFalse($seq->isEmpty());
    }

    public function testLenOnEmptySequence(): void
    {
        $seq = Sequence::new();

        $this->assertSame(0, $seq->len()->toInt());
    }

    public function testLenOnNonEmptySequence(): void
    {
        $seq = Sequence::from(1, 2, 3, 4, 5);

        $this->assertSame(5, $seq->len()->toInt());

        $newSequence = $seq->push(6);
        $this->assertSame(5, $seq->len()->toInt(), 'Original Sequence length should remain unchanged');
        $this->assertSame(6, $newSequence->len()->toInt());
    }

    public function testLenAfterPushingItem(): void
    {
        $seq = Sequence::from(1, 2);
        $this->assertSame(2, $seq->len()->toInt());

        $seq = $seq->push(3);
        $this->assertSame(3, $seq->len()->toInt());
    }

    public function testContainsExistingItem(): void
    {
        $seq = Sequence::from(1, 2, 3);

        $this->assertTrue($seq->contains(2));
    }

    public function testContainsNonExistingItem(): void
    {
        $seq = Sequence::from(1, 2, 3);

        $this->assertFalse($seq->contains(5));
    }

    public function testContainsWithStrictComparison(): void
    {
        $seq = Sequence::from('1', '2', '3');

        $this->assertTrue($seq->contains('2'));
        $this->assertFalse($seq->contains(2), 'Integer 2 should not be found in string array due to strict comparison');
    }

    public function testContainsInEmptySequence(): void
    {
        $seq = Sequence::new();

        $this->assertFalse($seq->contains('anything'));
    }

    public function testContainsObject(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $obj3 = new \stdClass();

        $seq = Sequence::from($obj1, $obj2);

        $this->assertTrue($seq->contains($obj1));
        $this->assertTrue($seq->contains($obj2));
        $this->assertFalse($seq->contains($obj3));
    }

    public function testContainsObjectWithSameProperties(): void
    {
        $obj1 = new \stdClass();
        $obj1->prop = 'value';

        $obj2 = new \stdClass();
        $obj2->prop = 'value';

        $seq = Sequence::from($obj1);

        $this->assertFalse($seq->contains($obj2));
    }

    public function testAllWithAllElementsSatisfyingPredicate(): void
    {
        $seq = Sequence::from(2, 4, 6, 8);
        $result = $seq->all(static fn($n) => $n % 2 === 0);

        $this->assertTrue($result, 'All elements are even, should return true');
    }

    public function testAllWithSomeElementsNotSatisfyingPredicate(): void
    {
        $seq = Sequence::from(2, 4, 5, 8);
        $result = $seq->all(static fn($n) => $n % 2 === 0);

        $this->assertFalse($result, 'Not all elements are even, should return false');
    }

    public function testAllWithNoElementsSatisfyingPredicate(): void
    {
        $seq = Sequence::from(1, 3, 5, 7);
        $result = $seq->all(static fn($n) => $n % 2 === 0);

        $this->assertFalse($result, 'No elements are even, should return false');
    }

    public function testAllWithEmptySequence(): void
    {
        $seq = Sequence::new();
        $result = $seq->all(static fn($n) => true);

        $this->assertTrue($result, 'Empty Sequence should return true for all()');
    }

    public function testAllWithObjectPredicate(): void
    {
        $obj1 = new \stdClass();
        $obj1->value = 10;

        $obj2 = new \stdClass();
        $obj2->value = 20;

        $seq = Sequence::from($obj1, $obj2);
        $result = $seq->all(static fn($obj) => $obj->value > 5);

        $this->assertTrue($result, 'All objects have value > 5, should return true');
    }

    public function testAnyWithSomeElementsSatisfyingPredicate(): void
    {
        $seq = Sequence::from(1, 2, 3, 4);
        $result = $seq->any(static fn($n) => $n % 2 === 0);

        $this->assertTrue($result, 'Some elements are even, should return true');
    }

    public function testAnyWithAllElementsSatisfyingPredicate(): void
    {
        $seq = Sequence::from(2, 4, 6, 8);
        $result = $seq->any(static fn($n) => $n % 2 === 0);

        $this->assertTrue($result, 'All elements are even, should return true');
    }

    public function testAnyWithNoElementsSatisfyingPredicate(): void
    {
        $seq = Sequence::from(1, 3, 5, 7);
        $result = $seq->any(static fn($n) => $n % 2 === 0);

        $this->assertFalse($result, 'No elements are even, should return false');
    }

    public function testAnyWithEmptySequence(): void
    {
        $seq = Sequence::new();
        $result = $seq->any(static fn($n) => true);

        $this->assertFalse($result, 'Empty Sequence should return false for any()');
    }

    public function testAnyWithObjectPredicate(): void
    {
        $obj1 = new \stdClass();
        $obj1->value = 5;

        $obj2 = new \stdClass();
        $obj2->value = 15;

        $seq = Sequence::from($obj1, $obj2);
        $result = $seq->any(static fn($obj) => $obj->value > 10);

        $this->assertTrue($result, 'At least one object has value > 10, should return true');
    }

    public function testEqWithIdenticalSequences(): void
    {
        $seq1 = Sequence::from(1, 2, 3);
        $seq2 = Sequence::from(1, 2, 3);

        $this->assertTrue($seq1->eq($seq2), 'Identical Sequences should be equal');
    }

    public function testEqWithDifferentSequences(): void
    {
        $seq1 = Sequence::from(1, 2, 3);
        $seq2 = Sequence::from(1, 2, 4);

        $this->assertFalse($seq1->eq($seq2), 'Different Sequences should not be equal');
    }

    public function testEqWithDifferentLengthSequences(): void
    {
        $seq1 = Sequence::from(1, 2, 3);
        $seq2 = Sequence::from(1, 2);

        $this->assertFalse($seq1->eq($seq2), 'Sequences of different lengths should not be equal');
    }

    public function testEqWithEmptySequences(): void
    {
        $seq1 = Sequence::new();
        $seq2 = Sequence::new();

        $this->assertTrue($seq1->eq($seq2), 'Empty Sequences should be equal');
    }

    public function testEqWithDifferentTypes(): void
    {
        $seq1 = Sequence::from(1, 2, 3);
        $seq2 = Sequence::from('1', '2', '3');

        $this->assertFalse($seq1->eq($seq2), 'Sequences with different types should not be equal');
    }

    public function testEqWithObjectReferences(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $obj3 = new \stdClass();

        $seq1 = Sequence::from($obj1, $obj2);
        $seq2 = Sequence::from($obj1, $obj2);
        $seq3 = Sequence::from($obj1, $obj3);

        $this->assertTrue($seq1->eq($seq2), 'Sequences with same object references should be equal');
        $this->assertFalse($seq1->eq($seq3), 'Sequences with different object references should not be equal');
    }

    public function testEqWithSelf(): void
    {
        $seq = Sequence::from(1, 2, 3);

        $this->assertTrue($seq->eq($seq), 'A Sequence should be equal to itself');
    }
}
