<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Str\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use PHPUnit\Framework\TestCase;

final class StrSplittingTest extends TestCase
{
    public function testSplitWithSimpleDelimiter(): void
    {
        $str = Str::of('apple,banana,cherry');
        $parts = $str->split(',');

        $this->assertInstanceOf(Sequence::class, $parts);
        $this->assertSame(3, $parts->size());

        $this->assertInstanceOf(Str::class, $parts->get(0)->unwrapOr(null));
        $this->assertSame('apple', $parts->get(0)->unwrapOr(null)->toString());
        $this->assertSame('banana', $parts->get(1)->unwrapOr(null)->toString());
        $this->assertSame('cherry', $parts->get(2)->unwrapOr(null)->toString());
    }

    public function testSplitWithUtf8Delimiter(): void
    {
        $str = Str::of('apple😀banana😀cherry');
        $parts = $str->split('😀');

        $this->assertSame(3, $parts->size());
        $this->assertSame('apple', $parts->get(0)->unwrapOr(null)->toString());
        $this->assertSame('banana', $parts->get(1)->unwrapOr(null)->toString());
        $this->assertSame('cherry', $parts->get(2)->unwrapOr(null)->toString());
    }

    public function testSplitWithNonExistentDelimiter(): void
    {
        $str = Str::of('Hello world');
        $parts = $str->split(',');

        $this->assertSame(1, $parts->size());
        $this->assertSame('Hello world', $parts->get(0)->unwrapOr(null)->toString());
    }

    public function testSplitWithEmptyString(): void
    {
        $str = Str::new();
        $parts = $str->split(',');

        $this->assertTrue($parts->isEmpty());
    }

    public function testSplitWithEmptyDelimiter(): void
    {
        $str = Str::of('Hello');

        $parts = $str->split('');

        $this->assertSame(5, $parts->size());
        $this->assertSame('H', $parts->get(0)->unwrapOr(null)->toString());
        $this->assertSame('e', $parts->get(1)->unwrapOr(null)->toString());
        $this->assertSame('l', $parts->get(2)->unwrapOr(null)->toString());
        $this->assertSame('l', $parts->get(3)->unwrapOr(null)->toString());
        $this->assertSame('o', $parts->get(4)->unwrapOr(null)->toString());
    }

    public function testSplitAtWithValidIndex(): void
    {
        $str = Str::of('Hello world');
        $parts = $str->splitAt(5);

        $this->assertInstanceOf(Sequence::class, $parts);
        $this->assertSame(2, $parts->size());

        $this->assertInstanceOf(Str::class, $parts->get(0)->unwrapOr(null));
        $this->assertSame('Hello', $parts->get(0)->unwrapOr(null)->toString());
        $this->assertSame(' world', $parts->get(1)->unwrapOr(null)->toString());
    }

    public function testSplitAtWithUtf8(): void
    {
        $str = Str::of('Hello 😀 world');
        $parts = $str->splitAt(6);

        $this->assertSame(2, $parts->size());

        $this->assertInstanceOf(Str::class, $parts->get(0)->unwrapOr(null));
        $this->assertInstanceOf(Str::class, $parts->get(1)->unwrapOr(null));

        $this->assertSame('Hello ', $parts->get(0)->unwrapOr(null)->toString());
        $this->assertSame('😀 world', $parts->get(1)->unwrapOr(null)->toString());
    }

    public function testSplitAtWithOutOfBoundsIndex(): void
    {
        $str = Str::of('Hello');
        $parts = $str->splitAt(10);

        $this->assertEquals('Hello', $parts->get(0)->unwrap());
        $this->assertEquals('', $parts->get(1)->unwrap());
    }

    public function testSplitAtWithNegativeIndex(): void
    {
        $str = Str::of('Hello');
        $parts = $str->splitAt(-1);

        $this->assertEquals('Hell', $parts->get(0)->unwrap());
        $this->assertEquals('o', $parts->get(1)->unwrap());
    }

    public function testSplitAtWithEmptyString(): void
    {
        $str = Str::new();
        $parts = $str->splitAt(0);

        $this->assertTrue($parts->isEmpty());
    }

    public function testSplitAtBeginning(): void
    {
        $str = Str::of('Hello');
        $parts = $str->splitAt(0);

        $this->assertSame(2, $parts->size());
        $this->assertSame('', $parts->get(0)->unwrapOr(null)->toString());
        $this->assertSame('Hello', $parts->get(1)->unwrapOr(null)->toString());
    }

    public function testSplitAtEnd(): void
    {
        $str = Str::of('Hello');
        $parts = $str->splitAt(5);

        $this->assertSame(2, $parts->size());
        $this->assertSame('Hello', $parts->get(0)->unwrapOr(null)->toString());
        $this->assertSame('', $parts->get(1)->unwrapOr(null)->toString());
    }

    public function testSplitWhitespace(): void
    {
        $str = Str::of('Hello world	test string');
        $parts = $str->splitWhitespace();

        $this->assertSame(4, $parts->size());
        $this->assertSame('Hello', $parts->get(0)->unwrapOr(null)->toString());
        $this->assertSame('world', $parts->get(1)->unwrapOr(null)->toString());
        $this->assertSame('test', $parts->get(2)->unwrapOr(null)->toString());
        $this->assertSame('string', $parts->get(3)->unwrapOr(null)->toString());
    }

    public function testSplitWhitespaceWithConsecutiveSpaces(): void
    {
        $str = Str::of('Hello  world  test');
        $parts = $str->splitWhitespace();

        $this->assertSame(3, $parts->size());
        $this->assertSame('Hello', $parts->get(0)->unwrapOr(null)->toString());
        $this->assertSame('world', $parts->get(1)->unwrapOr(null)->toString());
        $this->assertSame('test', $parts->get(2)->unwrapOr(null)->toString());
    }

    public function testSplitWhitespaceWithUtf8Whitespace(): void
    {
        $str = Str::of('Hello  😀  Test');
        $parts = $str->splitWhitespace();

        $this->assertSame(3, $parts->size());
        $this->assertSame('Hello', $parts->get(0)->unwrapOr(null)->toString());
        $this->assertSame('😀', $parts->get(1)->unwrapOr(null)->toString());
        $this->assertSame('Test', $parts->get(2)->unwrapOr(null)->toString());
    }

    public function testSplitWhitespaceWithEmptyString(): void
    {
        $str = Str::new();
        $parts = $str->splitWhitespace();

        $this->assertTrue($parts->isEmpty());
    }

    public function testSplitWhitespaceWithOnlyWhitespace(): void
    {
        $str = Str::of("  \t\n  ");
        $parts = $str->splitWhitespace();

        $this->assertTrue($parts->all(static fn($s) => $s->isEmpty()));
    }
}
