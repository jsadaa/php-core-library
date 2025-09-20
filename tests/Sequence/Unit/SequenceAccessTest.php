<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Sequence\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use PHPUnit\Framework\TestCase;

final class SequenceAccessTest extends TestCase
{
    public function testGetExistingItem(): void
    {
        $seq = Sequence::of('a', 'b', 'c');
        $option = $seq->get(1);

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame('b', $value);
    }

    public function testGetNonExistingItem(): void
    {
        $seq = Sequence::of('a', 'b', 'c');
        $option = $seq->get(5);

        $this->assertTrue($option->isNone());
    }

    public function testGetItemAtZeroIndex(): void
    {
        $seq = Sequence::of('a', 'b', 'c');
        $option = $seq->get(0);

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame('a', $value);
    }

    public function testGetItemAtLastIndex(): void
    {
        $seq = Sequence::of('a', 'b', 'c');
        $option = $seq->get(2);

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame('c', $value);
    }

    public function testGetItemFromEmptySequence(): void
    {
        $seq = Sequence::new();
        $option = $seq->get(0);

        $this->assertTrue($option->isNone());
    }

    public function testGetItemWithNegativeIndex(): void
    {
        $seq = Sequence::of(1, 2, 3);
        $option = $seq->get(-1);

        $this->assertTrue($option->isNone());
    }

    public function testGetObjectItem(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $seq = Sequence::of($obj1, $obj2);

        $option = $seq->get(1);

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame($obj2, $value);
    }

    public function testFirstElement(): void
    {
        $seq = Sequence::of('a', 'b', 'c');
        $option = $seq->first();

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame('a', $value);
    }

    public function testFirstElementFromEmptySequence(): void
    {
        $seq = Sequence::new();
        $option = $seq->first();

        $this->assertTrue($option->isNone());
    }

    public function testFirstElementFromSingleItemSequence(): void
    {
        $seq = Sequence::of('single');
        $option = $seq->first();

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame('single', $value);
    }

    public function testLastElement(): void
    {
        $seq = Sequence::of('a', 'b', 'c');
        $option = $seq->last();

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame('c', $value);
    }

    public function testLastElementFromEmptySequence(): void
    {
        $seq = Sequence::new();
        $option = $seq->last();

        $this->assertTrue($option->isNone());
    }

    public function testLastElementFromSingleItemSequence(): void
    {
        $seq = Sequence::of('single');
        $option = $seq->last();

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame('single', $value);
    }

    public function testFirstAndLastOnSameSequence(): void
    {
        $seq = Sequence::of(1, 2, 3, 4, 5);

        $first = $seq->first()->match(
            static fn($v) => $v,
            static fn() => null,
        );

        $last = $seq->last()->match(
            static fn($v) => $v,
            static fn() => null,
        );

        $this->assertSame(1, $first);
        $this->assertSame(5, $last);
    }

    public function testFindExistingElement(): void
    {
        $seq = Sequence::of(1, 2, 3, 4, 5);
        $option = $seq->find(static fn($x) => $x > 3);

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame(4, $value);
    }

    public function testFindNonExistingElement(): void
    {
        $seq = Sequence::of(1, 2, 3, 4, 5);
        $option = $seq->find(static fn($x) => $x > 10);

        $this->assertTrue($option->isNone());
    }

    public function testFindInEmptySequence(): void
    {
        $seq = Sequence::new();
        $option = $seq->find(static fn($x) => true);

        $this->assertTrue($option->isNone());
    }

    public function testFindFirstElement(): void
    {
        $seq = Sequence::of(1, 2, 3, 4, 5);
        $option = $seq->find(static fn($x) => $x === 1);

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame(1, $value);
    }

    public function testFindLastElement(): void
    {
        $seq = Sequence::of(1, 2, 3, 4, 5);
        $option = $seq->find(static fn($x) => $x === 5);

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame(5, $value);
    }

    public function testFindWithMultipleMatches(): void
    {
        $seq = Sequence::of(1, 2, 3, 4, 2, 5);
        $option = $seq->find(static fn($x) => $x === 2);

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

        $seq = Sequence::of($obj1, $obj2, $obj3);
        $option = $seq->find(static fn($obj) => $obj->id === 2);

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v,
            static fn() => null,
        );
        $this->assertSame($obj2, $value);
    }

    public function testIndexOfExistingElement(): void
    {
        $seq = Sequence::of('a', 'b', 'c', 'd');
        $option = $seq->indexOf('c');

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v->toInt(),
            static fn() => null,
        );
        $this->assertSame(2, $value);
    }

    public function testIndexOfFirstElement(): void
    {
        $seq = Sequence::of('a', 'b', 'c');
        $option = $seq->indexOf('a');

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v->toInt(),
            static fn() => null,
        );
        $this->assertSame(0, $value);
    }

    public function testIndexOfLastElement(): void
    {
        $seq = Sequence::of('a', 'b', 'c');
        $option = $seq->indexOf('c');

        $this->assertTrue($option->isSome());
        $value = $option->match(
            static fn($v) => $v->toInt(),
            static fn() => null,
        );
        $this->assertSame(2, $value);
    }

    public function testIndexOfNonExistingElement(): void
    {
        $seq = Sequence::of('a', 'b', 'c');
        $option = $seq->indexOf('z');

        $this->assertTrue($option->isNone());
    }

    public function testIndexOfInEmptySequence(): void
    {
        $seq = Sequence::new();
        $option = $seq->indexOf('a');

        $this->assertTrue($option->isNone());
    }

    public function testIndexOfDuplicateElement(): void
    {
        $seq = Sequence::of('a', 'b', 'a', 'c');
        $option = $seq->indexOf('a');

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
        $seq = Sequence::of($obj1, $obj2);

        $option = $seq->indexOf($obj2);

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

        $seq = Sequence::of($obj1);
        $option = $seq->indexOf($obj2);

        $this->assertTrue($option->isNone());
    }
}
