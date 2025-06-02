<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Str\Unit;

use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use PHPUnit\Framework\TestCase;

final class StrAccessTest extends TestCase
{
    public function testGetValidIndices(): void
    {
        $str = Str::from('Hello');
        $substr = $str->getRange(0, 2);

        $this->assertTrue($substr->isSome());
        $value = $substr->unwrapOr(null);
        $this->assertInstanceOf(Str::class, $value);
        $this->assertSame('He', $value->toString());
    }

    public function testGetUtf8String(): void
    {
        $str = Str::from('Héllö');
        $substr = $str->getRange(0, 3);

        $this->assertTrue($substr->isSome());
        $value = $substr->unwrapOr(null);
        $this->assertSame('Hél', $value->toString());
    }

    public function testGetComplexUtf8(): void
    {
        $str = Str::from('Hello 🌍 你好 مرحبا');
        $substr = $str->getRange(6, 1);

        $this->assertTrue($substr->isSome());
        $value = $substr->unwrapOr(null);
        $this->assertSame('🌍', $value->toString());

        $substr2 = $str->getRange(8, 2);
        $this->assertTrue($substr2->isSome());
        $value2 = $substr2->unwrapOr(null);
        $this->assertSame('你好', $value2->toString());
    }

    public function testGetOutOfBoundsIndices(): void
    {
        $str = Str::from('Hello');

        $substr1 = $str->getRange(10, 2);
        $this->assertTrue(
            $substr1->isNone(),
            'Start index beyond string length should return None',
        );

        $substr2 = $str->getRange(3, 10);
        $this->assertTrue($substr2->isSome());
        $this->assertEquals(Str::from('lo'), $substr2->unwrapOr(Str::from('')));

        $substr3 = $str->getRange(-1, 2);
        $this->assertTrue(
            $substr3->isNone(),
            'Negative start index should return None',
        );
    }

    public function testGetEdgeCase(): void
    {
        $str = Str::from('Hello');
        $substr = $str->getRange(2, 3);

        $this->assertTrue($substr->isSome());
        $value = $substr->unwrapOr(null);
        $this->assertSame('llo', $value->toString());
    }

    public function testGetOnEmptyString(): void
    {
        $str = Str::new();
        $substr = $str->getRange(0, 1);

        $this->assertTrue($substr->isNone());
    }

    public function testFindAsciiSubstring(): void
    {
        $str = Str::from('Hello world');
        $position = $str->find('world');

        $this->assertTrue($position->isSome());
        $this->assertSame(6, $position->unwrapOr(-1));
    }

    public function testFindUtf8Substring(): void
    {
        $str = Str::from('Héllö wörld');

        $position = $str->find('ö');

        $this->assertTrue($position->isSome());
        $this->assertEquals(4, $position->unwrapOr(-1));
    }

    public function testFindNonExistentSubstring(): void
    {
        $str = Str::from('Hello world');
        $position = $str->find('goodbye');

        $this->assertTrue($position->isNone());
    }

    public function testFindEmptySubstring(): void
    {
        $str = Str::from('Hello');
        $position = $str->find('');

        $this->assertTrue($position->isSome());
        $this->assertSame(0, $position->unwrapOr(-1));
    }

    public function testFindOnEmptyString(): void
    {
        $str = Str::new();
        $position = $str->find('a');

        $this->assertTrue($position->isNone());
    }

    public function testChars(): void
    {
        $str = Str::from('Hello');
        $chars = $str->chars();

        $this->assertSame(5, $chars->len()->toInt());
        $this->assertSame(['H', 'e', 'l', 'l', 'o'], $chars->map(static fn(Str $char) => $char->toString())->toArray());
    }

    public function testCharsWithUtf8(): void
    {
        $str = Str::from('Héllö');
        $chars = $str->chars();

        $this->assertSame(5, $chars->len()->toInt());
        $this->assertEquals(['H', 'é', 'l', 'l', 'ö'], $chars->map(static fn(Str $char) => $char->toString())->toArray());
    }

    public function testCharsWithEmoji(): void
    {
        $str = Str::from('Hello😀');
        $chars = $str->chars();

        $this->assertSame(6, $chars->len()->toInt());
        $this->assertEquals(['H', 'e', 'l', 'l', 'o', '😀'], $chars->map(static fn(Str $char) => $char->toString())->toArray());
    }

    public function testCharsOnEmptyString(): void
    {
        $str = Str::new();
        $chars = $str->chars();

        $this->assertTrue($chars->isEmpty());
    }

    public function testBytes(): void
    {
        $str = Str::from('AB');
        $bytes = $str->bytes();

        $this->assertSame(2, $bytes->len()->toInt());
        $this->assertSame([65, 66], $bytes->map(static fn(Integer $byte) => $byte->toInt())->toArray());
    }

    public function testBytesWithUtf8(): void
    {
        $str = Str::from('é');
        $bytes = $str->bytes();

        // UTF-8 representation of é is 2 bytes (195, 169)
        $this->assertSame(2, $bytes->len()->toInt());
        $this->assertSame([195, 169], $bytes->map(static fn(Integer $byte) => $byte->toInt())->toArray());
    }

    public function testBytesOnEmptyString(): void
    {
        $str = Str::new();
        $bytes = $str->bytes();

        $this->assertTrue($bytes->isEmpty());
    }

    public function testLines(): void
    {
        $str = Str::from("Line 1\nLine 2\nLine 3");
        $lines = $str->lines();

        $this->assertSame(3, $lines->len()->toInt());
        $this->assertSame(
            ['Line 1', 'Line 2', 'Line 3'],
            $lines->map(static fn(Str $line) => $line->toString())->toArray(),
        );
    }

    public function testLinesWithEmptyString(): void
    {
        $str = Str::new();
        $lines = $str->lines();

        $this->assertSame(0, $lines->len()->toInt());
    }

    public function testMatches(): void
    {
        $str = Str::from('apple banana cherry apple');
        $matches = $str->matches("/a\w+/u");

        $this->assertEquals(3, $matches->len()->toInt());
        $this->assertSame(['apple', 'anana', 'apple'], $matches->map(static fn(Str $match) => $match->toString())->toArray());
    }

    public function testMatchesNoMatches(): void
    {
        $str = Str::from('apple banana cherry');
        $matches = $str->matches("/z\w+/u");

        $this->assertTrue($matches->isEmpty());
    }

    public function testMatchesWithRealUtf8(): void
    {
        $str = Str::from('été automne hiver été');
        $matches = $str->matches("/é\w+/u");

        $this->assertSame(2, $matches->len()->toInt());
        $this->assertSame(['été', 'été'], $matches->map(static fn(Str $match) => $match->toString())->toArray());

        $str2 = Str::from('😄 hello 😁 world 😀');
        $matches2 = $str2->matches('/😄|😁|😀/u')->map(static fn(Str $match) => $match->toString());

        $this->assertSame(3, $matches2->len()->toInt());
        $this->assertContains('😄', $matches2->toArray());
        $this->assertContains('😁', $matches2->toArray());
        $this->assertContains('😀', $matches2->toArray());

        $str3 = Str::from('Hello مرحبا world');
        $matches3 = $str3->matches("/م\w+/u")->map(static fn(Str $match) => $match->toString());

        $this->assertSame(1, $matches3->len()->toInt());
        $this->assertContains('مرحبا', $matches3->toArray());
    }

    public function testMatchIndices(): void
    {
        $str = Str::from('apple orange banana apple');
        $indices = $str->matchIndices("/a\w+e/");

        $this->assertGreaterThan(0, $indices->len());

        $firstMatch = $indices->get(0)->unwrapOr([]);
        $this->assertIsArray($firstMatch);
        $this->assertCount(2, $firstMatch);

        $this->assertSame('apple', $firstMatch[1]->toString());
    }

    public function testMatchIndicesWithComplexUtf8(): void
    {
        $str = Str::from('Hello 😄 world 😁 test 😀');
        $indices = $str->matchIndices('/😄|😁|😀/u');

        $this->assertSame(3, $indices->len()->toInt());
        $matchesWithIndices = $indices->toArray();

        $this->assertSame('😄', $matchesWithIndices[0][1]->toString());
        $this->assertSame('😁', $matchesWithIndices[1][1]->toString());
        $this->assertSame('😀', $matchesWithIndices[2][1]->toString());
        $this->assertGreaterThan($matchesWithIndices[1][0], $matchesWithIndices[2][0]);

        $str2 = Str::from('en English, zh 中文, ar العربية, ru Русский');
        $indices2 = $str2->matchIndices("/[\p{Han}\p{Arabic}\p{Cyrillic}]+/u");

        $this->assertSame(3, $indices2->len()->toInt());
        $matchesWithIndices2 = $indices2->toArray();

        $this->assertSame('中文', $matchesWithIndices2[0][1]->toString());
        $this->assertSame('العربية', $matchesWithIndices2[1][1]->toString());
        $this->assertSame('Русский', $matchesWithIndices2[2][1]->toString());
    }
}
