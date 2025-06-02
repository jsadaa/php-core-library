<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Vec\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Vec\Vec;
use PHPUnit\Framework\TestCase;

final class VecAccessTest extends TestCase
{
    public function testGetExistingItem(): void
    {
        $vec = Vec::from('a', 'b', 'c');
        $option = $vec->get(1);

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame('b', $value);
    }

    public function testGetNonExistingItem(): void
    {
        $vec = Vec::from('a', 'b', 'c');
        $option = $vec->get(5);

        $this->assertTrue($option->isNone());
    }

    public function testGetItemAtZeroIndex(): void
    {
        $vec = Vec::from('a', 'b', 'c');
        $option = $vec->get(0);

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame('a', $value);
    }

    public function testGetItemAtLastIndex(): void
    {
        $vec = Vec::from('a', 'b', 'c');
        $option = $vec->get(2);

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame('c', $value);
    }

    public function testGetItemFromEmptyVec(): void
    {
        $vec = Vec::new();
        $option = $vec->get(0);

        $this->assertTrue($option->isNone());
    }

    public function testGetItemWithNegativeIndex(): void
    {
        $vec = Vec::from(1, 2, 3);
        $option = $vec->get(-1);

        $this->assertTrue($option->isNone());
    }

    public function testGetObjectItem(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $vec = Vec::from($obj1, $obj2);

        $option = $vec->get(1);

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame($obj2, $value);
    }

    public function testFirstElement(): void
    {
        $vec = Vec::from('a', 'b', 'c');
        $option = $vec->first();

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame('a', $value);
    }

    public function testFirstElementFromEmptyVec(): void
    {
        $vec = Vec::new();
        $option = $vec->first();

        $this->assertTrue($option->isNone());
    }

    public function testFirstElementFromSingleItemVec(): void
    {
        $vec = Vec::from('single');
        $option = $vec->first();

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame('single', $value);
    }

    public function testLastElement(): void
    {
        $vec = Vec::from('a', 'b', 'c');
        $option = $vec->last();

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame('c', $value);
    }

    public function testLastElementFromEmptyVec(): void
    {
        $vec = Vec::new();
        $option = $vec->last();

        $this->assertTrue($option->isNone());
    }

    public function testLastElementFromSingleItemVec(): void
    {
        $vec = Vec::from('single');
        $option = $vec->last();

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame('single', $value);
    }

    public function testFirstAndLastOnSameVec(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);

        $first = $vec->first()->match(
            static fn($v) => $v,
            static fn() => null,
        );

        $last = $vec->last()->match(
            static fn($v) => $v,
            static fn() => null,
        );

        $this->assertSame(1, $first);
        $this->assertSame(5, $last);
    }

    public function testFindExistingElement(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $option = $vec->find(static fn($x) => $x > 3);

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame(4, $value);
    }

    public function testFindNonExistingElement(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $option = $vec->find(static fn($x) => $x > 10);

        $this->assertTrue($option->isNone());
    }

    public function testFindInEmptyVec(): void
    {
        $vec = Vec::new();
        $option = $vec->find(static fn($x) => true);

        $this->assertTrue($option->isNone());
    }

    public function testFindFirstElement(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $option = $vec->find(static fn($x) => $x === 1);

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame(1, $value);
    }

    public function testFindLastElement(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 5);
        $option = $vec->find(static fn($x) => $x === 5);

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame(5, $value);
    }

    public function testFindWithMultipleMatches(): void
    {
        $vec = Vec::from(1, 2, 3, 4, 2, 5);
        $option = $vec->find(static fn($x) => $x === 2);

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame(2, $value);
    }

    public function testFindObject(): void
    {
        $obj1 = new \stdClass();
        $obj1->id = 1;

        $obj2 = new \stdClass();
        $obj2->id = 2;

        $obj3 = new \stdClass();
        $obj3->id = 3;

        $vec = Vec::from($obj1, $obj2, $obj3);
        $option = $vec->find(static fn($obj) => $obj->id === 2);

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame($obj2, $value);
    }

    public function testIndexOfExistingElement(): void
    {
        $vec = Vec::from('a', 'b', 'c', 'd');
        $option = $vec->indexOf('c');

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v->toInt(),
            static fn() => null,
        );
        $this->assertSame(2, $value);
    }

    public function testIndexOfFirstElement(): void
    {
        $vec = Vec::from('a', 'b', 'c');
        $option = $vec->indexOf('a');

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v->toInt(),
            static fn() => null,
        );
        $this->assertSame(0, $value);
    }

    public function testIndexOfLastElement(): void
    {
        $vec = Vec::from('a', 'b', 'c');
        $option = $vec->indexOf('c');

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v->toInt(),
            static fn() => null,
        );
        $this->assertSame(2, $value);
    }

    public function testIndexOfNonExistingElement(): void
    {
        $vec = Vec::from('a', 'b', 'c');
        $option = $vec->indexOf('z');

        $this->assertTrue($option->isNone());
    }

    public function testIndexOfInEmptyVec(): void
    {
        $vec = Vec::new();
        $option = $vec->indexOf('a');

        $this->assertTrue($option->isNone());
    }

    public function testIndexOfDuplicateElement(): void
    {
        $vec = Vec::from('a', 'b', 'a', 'c');
        $option = $vec->indexOf('a');

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v->toInt(),
            static fn() => null,
        );
        $this->assertSame(0, $value);
    }

    public function testIndexOfObject(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $vec = Vec::from($obj1, $obj2);

        $option = $vec->indexOf($obj2);

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v->toInt(),
            static fn() => null,
        );
        $this->assertSame(1, $value);
    }

    public function testIndexOfDifferentButEqualObjects(): void
    {
        $obj1 = new \stdClass();
        $obj1->id = 1;

        $obj2 = new \stdClass();
        $obj2->id = 1;

        $vec = Vec::from($obj1);
        $option = $vec->indexOf($obj2);

        $this->assertTrue($option->isNone());
    }
}
