<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Sequence\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use PHPUnit\Framework\TestCase;

final class SequenceCreationTest extends TestCase
{
    public function testCreateEmptySequence(): void
    {
        $seq = Sequence::new();

        $this->assertTrue($seq->isEmpty());
        $this->assertSame([], $seq->toArray());
    }

    public function testCreateSequenceWithItems(): void
    {
        $seq = Sequence::of(1, 2, 3);

        $this->assertFalse($seq->isEmpty());
        $this->assertSame([1, 2, 3], $seq->toArray());
    }

    public function testCreateSequenceWithStrings(): void
    {
        $seq = Sequence::of('a', 'b', 'c');

        $this->assertFalse($seq->isEmpty());
        $this->assertSame(['a', 'b', 'c'], $seq->toArray());
    }

    public function testCreateSequenceWithFloats(): void
    {
        $seq = Sequence::of(1.1, 2.2, 3.3);

        $this->assertFalse($seq->isEmpty());
        $this->assertSame([1.1, 2.2, 3.3], $seq->toArray());
    }

    public function testCreateSequenceWithBooleans(): void
    {
        $seq = Sequence::of(true, false, true);

        $this->assertFalse($seq->isEmpty());
        $this->assertSame([true, false, true], $seq->toArray());
    }

    public function testCreateSequenceWithObjects(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();

        $seq = Sequence::of($obj1, $obj2);

        $this->assertSame([$obj1, $obj2], $seq->toArray());
    }

    public function testCreateSequenceWithSingleItem(): void
    {
        $seq = Sequence::of(42);

        $this->assertSame([42], $seq->toArray());
        $this->assertSame(1, $seq->size());
    }

    public function testCreateEmptySequenceWithFromMethod(): void
    {
        $seq = Sequence::of();

        $this->assertTrue($seq->isEmpty());
    }
}
