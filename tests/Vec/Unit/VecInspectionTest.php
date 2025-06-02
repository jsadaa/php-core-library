<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Vec\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Vec\Vec;
use PHPUnit\Framework\TestCase;

final class VecInspectionTest extends TestCase
{
    public function testIsEmptyOnEmptyVec(): void
    {
        $vec = Vec::new();

        $this->assertTrue($vec->isEmpty());
    }

    public function testIsEmptyOnNonEmptyVec(): void
    {
        $vec = Vec::from(1);

        $this->assertFalse($vec->isEmpty());
    }

    public function testIsEmptyAfterPushingItem(): void
    {
        $vec = Vec::new();
        $this->assertTrue($vec->isEmpty());

        $vec = $vec->push('item');
        $this->assertFalse($vec->isEmpty());
    }

    public function testLenOnEmptyVec(): void
    {
        $vec = Vec::new();

        $this->assertSame(0, $vec->len()->toInt());
    }

    public function testLenOnNonEmptyVec(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);

        $this->assertSame(5, $vec->len()->toInt());

        $newVec = $vec->push(6);
        $this->assertSame(5, $vec->len()->toInt(), 'Original vec length should remain unchanged');
        $this->assertSame(6, $newVec->len()->toInt());
    }

    public function testLenAfterPushingItem(): void
    {
        $vec = Vec::from(1, 2);
        $this->assertSame(2, $vec->len()->toInt());

        $vec = $vec->push(3);
        $this->assertSame(3, $vec->len()->toInt());
    }

    public function testContainsExistingItem(): void
    {
        $vec = Vec::from(1, 2, 3);

        $this->assertTrue($vec->contains(2));
    }

    public function testContainsNonExistingItem(): void
    {
        $vec = Vec::from(1, 2, 3);

        $this->assertFalse($vec->contains(5));
    }

    public function testContainsWithStrictComparison(): void
    {
        $vec = Vec::from('1', '2', '3');

        $this->assertTrue($vec->contains('2'));
        $this->assertFalse($vec->contains(2), 'Integer 2 should not be found in string array due to strict comparison');
    }

    public function testContainsInEmptyVec(): void
    {
        $vec = Vec::new();

        $this->assertFalse($vec->contains('anything'));
    }

    public function testContainsObject(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $obj3 = new \stdClass();

        $vec = Vec::from($obj1, $obj2);

        $this->assertTrue($vec->contains($obj1));
        $this->assertTrue($vec->contains($obj2));
        $this->assertFalse($vec->contains($obj3));
    }

    public function testContainsObjectWithSameProperties(): void
    {
        $obj1 = new \stdClass();
        $obj1->prop = 'value';

        $obj2 = new \stdClass();
        $obj2->prop = 'value';

        $vec = Vec::from($obj1);

        $this->assertFalse($vec->contains($obj2));
    }

    public function testAllWithAllElementsSatisfyingPredicate(): void
    {
        $vec = Vec::from(2, 4, 6, 8);
        $result = $vec->all(static fn($n) => $n % 2 === 0);

        $this->assertTrue($result, 'All elements are even, should return true');
    }

    public function testAllWithSomeElementsNotSatisfyingPredicate(): void
    {
        $vec = Vec::from(2, 4, 5, 8);
        $result = $vec->all(static fn($n) => $n % 2 === 0);

        $this->assertFalse($result, 'Not all elements are even, should return false');
    }

    public function testAllWithNoElementsSatisfyingPredicate(): void
    {
        $vec = Vec::from(1, 3, 5, 7);
        $result = $vec->all(static fn($n) => $n % 2 === 0);

        $this->assertFalse($result, 'No elements are even, should return false');
    }

    public function testAllWithEmptyVec(): void
    {
        $vec = Vec::new();
        $result = $vec->all(static fn($n) => true);

        $this->assertTrue($result, 'Empty Vec should return true for all()');
    }

    public function testAllWithObjectPredicate(): void
    {
        $obj1 = new \stdClass();
        $obj1->value = 10;

        $obj2 = new \stdClass();
        $obj2->value = 20;

        $vec = Vec::from($obj1, $obj2);
        $result = $vec->all(static fn($obj) => $obj->value > 5);

        $this->assertTrue($result, 'All objects have value > 5, should return true');
    }

    public function testAnyWithSomeElementsSatisfyingPredicate(): void
    {
        $vec = Vec::from(1, 2, 3, 4);
        $result = $vec->any(static fn($n) => $n % 2 === 0);

        $this->assertTrue($result, 'Some elements are even, should return true');
    }

    public function testAnyWithAllElementsSatisfyingPredicate(): void
    {
        $vec = Vec::from(2, 4, 6, 8);
        $result = $vec->any(static fn($n) => $n % 2 === 0);

        $this->assertTrue($result, 'All elements are even, should return true');
    }

    public function testAnyWithNoElementsSatisfyingPredicate(): void
    {
        $vec = Vec::from(1, 3, 5, 7);
        $result = $vec->any(static fn($n) => $n % 2 === 0);

        $this->assertFalse($result, 'No elements are even, should return false');
    }

    public function testAnyWithEmptyVec(): void
    {
        $vec = Vec::new();
        $result = $vec->any(static fn($n) => true);

        $this->assertFalse($result, 'Empty Vec should return false for any()');
    }

    public function testAnyWithObjectPredicate(): void
    {
        $obj1 = new \stdClass();
        $obj1->value = 5;

        $obj2 = new \stdClass();
        $obj2->value = 15;

        $vec = Vec::from($obj1, $obj2);
        $result = $vec->any(static fn($obj) => $obj->value > 10);

        $this->assertTrue($result, 'At least one object has value > 10, should return true');
    }

    public function testEqWithIdenticalVecs(): void
    {
        $vec1 = Vec::from(1, 2, 3);
        $vec2 = Vec::from(1, 2, 3);

        $this->assertTrue($vec1->eq($vec2), 'Identical Vecs should be equal');
    }

    public function testEqWithDifferentVecs(): void
    {
        $vec1 = Vec::from(1, 2, 3);
        $vec2 = Vec::from(1, 2, 4);

        $this->assertFalse($vec1->eq($vec2), 'Different Vecs should not be equal');
    }

    public function testEqWithDifferentLengthVecs(): void
    {
        $vec1 = Vec::from(1, 2, 3);
        $vec2 = Vec::from(1, 2);

        $this->assertFalse($vec1->eq($vec2), 'Vecs of different lengths should not be equal');
    }

    public function testEqWithEmptyVecs(): void
    {
        $vec1 = Vec::new();
        $vec2 = Vec::new();

        $this->assertTrue($vec1->eq($vec2), 'Empty Vecs should be equal');
    }

    public function testEqWithDifferentTypes(): void
    {
        $vec1 = Vec::from(1, 2, 3);
        $vec2 = Vec::from('1', '2', '3');

        $this->assertFalse($vec1->eq($vec2), 'Vecs with different types should not be equal');
    }

    public function testEqWithObjectReferences(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $obj3 = new \stdClass();

        $vec1 = Vec::from($obj1, $obj2);
        $vec2 = Vec::from($obj1, $obj2);
        $vec3 = Vec::from($obj1, $obj3);

        $this->assertTrue($vec1->eq($vec2), 'Vecs with same object references should be equal');
        $this->assertFalse($vec1->eq($vec3), 'Vecs with different object references should not be equal');
    }

    public function testEqWithSelf(): void
    {
        $vec = Vec::from(1, 2, 3);

        $this->assertTrue($vec->eq($vec), 'A Vec should be equal to itself');
    }
}
