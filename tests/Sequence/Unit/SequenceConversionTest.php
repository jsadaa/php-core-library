<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Sequence\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use PHPUnit\Framework\TestCase;

final class SequenceConversionTest extends TestCase
{
    public function testToListWithIntegers(): void
    {
        $seq = Sequence::of(1, 2, 3);

        $this->assertSame([1, 2, 3], $seq->toArray());
    }

    public function testToListWithStrings(): void
    {
        $seq = Sequence::of('a', 'b', 'c');

        $this->assertSame(['a', 'b', 'c'], $seq->toArray());
    }

    public function testToListWithObjects(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();

        $seq = Sequence::of($obj1, $obj2);

        $this->assertSame([$obj1, $obj2], $seq->toArray());
    }

    public function testToListWithEmptySequence(): void
    {
        $seq = Sequence::new();

        $this->assertSame([], $seq->toArray());
    }

    public function testToListAfterModification(): void
    {
        $seq = Sequence::of(1, 2);
        $newSequence = $seq->push(3);

        $this->assertSame([1, 2, 3], $newSequence->toArray());
    }

    public function testToStringWithIntegers(): void
    {
        $seq = Sequence::of(1, 2, 3);

        $this->assertSame('Sequence<int>', (string)$seq);
    }

    public function testToStringWithStrings(): void
    {
        $seq = Sequence::of('a', 'b', 'c');

        $this->assertSame('Sequence<string>', (string)$seq);
    }

    public function testToStringWithObjects(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $seq = Sequence::of($obj1, $obj2);

        $this->assertSame('Sequence<stdClass>', (string)$seq);
    }

    public function testToStringWithEmptySequence(): void
    {
        $seq = Sequence::new();

        $this->assertSame('Sequence<>', (string)$seq);
    }

    public function testToStringWithObjectsAfterModification(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();

        $seq = Sequence::of($obj1);
        $newSequence = $seq->push($obj2);

        $this->assertSame('Sequence<stdClass>', (string)$newSequence);
    }

    public function testToStringWithLongCollections(): void
    {
        $seq = Sequence::of(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

        $this->assertSame('Sequence<int>', (string)$seq);
    }

    public function testToStringWithBooleans(): void
    {
        $seq = Sequence::of(true, false, true);

        $this->assertSame('Sequence<bool>', (string)$seq);
    }
}
