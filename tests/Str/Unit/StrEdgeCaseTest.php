<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Str\Unit;

use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use PHPUnit\Framework\TestCase;

final class StrEdgeCaseTest extends TestCase
{
    public function testInsertEdgeCases(): void
    {
        $str = Str::of('Hello');
        $result = $str->insertAt(5, Str::of('World'));
        $this->assertSame('HelloWorld', $result->toString());

        $result = $str->insertAt(2, Str::of(''));
        $this->assertSame('Hello', $result->toString(), 'Inserting empty string should not change original');

        $result = $str->insertAt(1000, Str::of('!'));
        $this->assertSame('Hello!', $result->toString(), 'Very large offset should append to end');

        $str = Str::of('こんにちは');
        $result = $str->insertAt(2, Str::of('☆'));
        $this->assertSame('こん☆にちは', $result->toString(), 'Should correctly insert into multibyte string');
    }

    public function testPushEdgeCases(): void
    {
        $str = Str::of('Hello')->append(Str::of(''));
        $this->assertSame('Hello', $str->toString(), 'Pushing empty string should not change original');

        $largeString = \str_repeat('a', 10000);
        $str = Str::of('Hello')->append(Str::of($largeString));
        $this->assertSame('Hello' . $largeString, $str->toString(), 'Should handle large string push');

        $str = Str::of('Hello')->append(Str::of('🌍🌎🌏'));
        $this->assertSame('Hello🌍🌎🌏', $str->toString(), 'Should handle emoji sequences');
    }

    public function testRemoveEdgeCases(): void
    {
        $str = Str::of('Hello');
        $modifiedStr = $str->removeAt(4);
        $this->assertSame('Hell', $modifiedStr->toString());

        $str = Str::of('Hello🌍');
        $modifiedStr = $str->removeAt(5);
        $this->assertSame('Hello', $modifiedStr->toString());

        $str = Str::of('Hello');
        $modifiedStr = $str->removeAt(1000);
        $this->assertSame('Hello', $modifiedStr->toString(), 'Should handle out-of-bounds index and return original string');

    }

    public function testTruncateEdgeCases(): void
    {
        $str = Str::of('Hello');
        $result = $str->truncate(1000000);
        $this->assertSame('Hello', $result->toString(), 'Large truncate length should return original string');

        $str = Str::of('こんにちは世界');
        $result = $str->truncate(3);
        $this->assertSame('こんに', $result->toString(), 'Should correctly truncate multibyte string');
    }

    public function testSplitAtEdgeCases(): void
    {
        $str = Str::of('Hello');
        $parts = $str->splitAt(1000);
        $this->assertSame(2, $parts->len()->toInt());
        $this->assertSame('Hello', $parts->get(0)->unwrap()->toString());
        $this->assertSame('', $parts->get(1)->unwrap()->toString());

        $str = Str::of('こんにちは世界');
        $parts = $str->splitAt(4);
        $this->assertSame('こんにち', $parts->get(0)->unwrap()->toString());
        $this->assertSame('は世界', $parts->get(1)->unwrap()->toString());
    }

    public function testPadStartEdgeCases(): void
    {
        $str = Str::of('Hello');
        $result = $str->padStart(10, '');
        $this->assertSame('Hello', $result->toString(), 'Empty pad string should return original');

        $str = Str::of('Hello');
        $result = $str->padStart(1000, '-');
        $this->assertSame(1000, $result->chars()->len()->toInt(), 'Should pad to exactly the specified length');
        $this->assertStringEndsWith('Hello', $result->toString());

        $str = Str::of('Hello');
        $result = $str->padStart(7, '世');
        $this->assertSame(7, $result->chars()->len()->toInt());
        $this->assertSame('世世Hello', $result->toString());
    }

    public function testPadEndEdgeCases(): void
    {
        $str = Str::of('Hello');
        $result = $str->padEnd(10, '');
        $this->assertSame('Hello', $result->toString(), 'Empty pad string should return original');

        $str = Str::of('Hello');
        $result = $str->padEnd(1000, '-');
        $this->assertSame(1000, $result->len()->toInt(), 'Should pad to exactly the specified length');
        $this->assertStringStartsWith('Hello', $result->toString());

        $str = Str::of('Hello');
        $result = $str->padEnd(7, '世');
        $this->assertSame(7, $result->chars()->len()->toInt());
        $this->assertSame('Hello世世', $result->toString());
    }

    public function testRepeatEdgeCases(): void
    {
        $str = Str::of('a');
        $result = $str->repeat(10000);
        $this->assertSame(10000, $result->len()->toInt(), 'Should repeat exactly the specified number of times');

        $str = Str::of('世');
        $result = $str->repeat(5);
        $this->assertSame(5, $result->chars()->len()->toInt());
        $this->assertSame('世世世世世', $result->toString());

        $str = Str::of('Hello');
        $result = $str->repeat(0);
        $this->assertTrue($result->isEmpty(), 'Zero repeat count should return empty string');
    }

    public function testReplaceRangeEdgeCases(): void
    {
        $str = Str::of('Hello');
        $result = $str->replaceRange(10, 5, Str::of('World'));
        $this->assertEquals('HelloWorld', $result->toString());

        $str = Str::of('Hello World');
        $result = $str->replaceRange(6, 10000, Str::of('Person'));
        $this->assertSame('Hello Person', $result->toString());

        $str = Str::of('Hello World');
        $result = $str->replaceRange(6, 5, Str::of('Beautiful Person'));
        $this->assertSame('Hello Beautiful Person', $result->toString());

        $str = Str::of('こんにちは世界');
        $result = $str->replaceRange(3, 2, Str::of('ABC'));
        $this->assertSame('こんにABC世界', $result->toString());
    }

    public function testWrapEdgeCases(): void
    {
        $str = Str::of('HelloWorld');
        $result = $str->wrap(1);
        $this->assertSame(
            "H\ne\nl\nl\no\nW\no\nr\nl\nd",
            $result->toString(),
            'Should wrap each character for width 1',
        );

        $str = Str::of('Hello');
        $result = $str->wrap(100);
        $this->assertSame('Hello', $result->toString(), 'Width larger than string should return original');

        $str = Str::of('The quick brown fox jumps over the lazy dog');
        $result = $str->wrap(10, "\r\n");
        $this->assertStringContainsString("\r\n", $result->toString(), 'Should use custom line break');
        $this->assertSame("The quick\r\nbrown fox\r\njumps over\r\nthe lazy\r\ndog", $result->toString());

        $str = Str::of('こんにちは世界こんにちは世界');
        $result = $str->wrap(5);
        $parts = \explode("\n", $result->toString());
        $this->assertSame(
            "こんにちは\n世界こんに\nちは世界",
            $result->toString(),
        );

        foreach ($parts as $part) {
            $this->assertLessThanOrEqual(5, \mb_strlen($part, 'UTF-8'), 'Each part should have at most 5 characters');
        }
    }

    public function testNormalizeEdgeCases(): void
    {
        $composed = Str::of("a\u{030A}");
        $result = $composed->normalize('NFC');
        $this->assertTrue($result->isOk());
        $this->assertSame('å', $result->unwrap()->toString());
        $this->assertCount(1, $result->unwrap()->chars()->toArray());

        $result = $composed->normalize('NFD');
        $this->assertTrue($result->isOk());
        $this->assertCount(2, $result->unwrap()->chars()->toArray());

        $str = Str::of('Café');
        $nfc = $str->normalize('NFC')->unwrap();
        $nfd = $str->normalize('NFD')->unwrap();
        $nfkc = $str->normalize('NFKC')->unwrap();
        $nfkd = $str->normalize('NFKD')->unwrap();

        // All of these should be visually equivalent but might have different internal representations
        // With no automatic normalization, we should check they're equivalent when displayed, not identical internally
        // NFD and NFC can have different character counts but should represent the same text
        $this->assertEquals(4, $nfc->chars()->len()->toInt(), 'NFC string should have length 4'); // "é" in composed form
        $this->assertEquals(5, $nfd->chars()->len()->toInt(), 'NFD string should have length 5'); // "é" in decomposed form
        $this->assertTrue($nfc->isValidUtf8(), 'NFC should produce valid UTF-8');
        $this->assertTrue($nfd->isValidUtf8(), 'NFD should produce valid UTF-8');

        $this->assertEquals(4, $nfkc->chars()->len()->toInt(), 'NFKC string should have length 4'); // "é" in composed form
        $this->assertEquals(5, $nfkd->chars()->len()->toInt(), 'NFKD string should have length 5'); // "é" in decomposed form
        $this->assertTrue($nfkc->isValidUtf8(), 'NFKC should produce valid UTF-8');
        $this->assertTrue($nfkd->isValidUtf8(), 'NFKD should produce valid UTF-8');

        // Convert to hexadecimal representation to compare more accurately
        $nfcHex = \bin2hex($nfc->toString());
        $nfdHex = \bin2hex($nfd->toString());
        $nfkcHex = \bin2hex($nfkc->toString());
        $nfkdHex = \bin2hex($nfkd->toString());

        $this->assertEquals($nfcHex, $nfkcHex, 'NFC and NFKC should have the same hexadecimal representation');
        $this->assertEquals($nfdHex, $nfkdHex, 'NFD and NFKD should have the same hexadecimal representation');
        $this->assertNotEquals($nfcHex, $nfdHex, 'NFC and NFD should have different hexadecimal representations');
        $this->assertNotEquals($nfcHex, $nfkdHex, 'NFC and NFKD should have different hexadecimal representations');

        // Test with compatibility characters
        // For example, ℕ (U+2115 DOUBLE-STRUCK CAPITAL N) vs. N
        $str = Str::of('ℕ'); // Mathematical double-struck N
        $nfkc = $str->normalize('NFKC');
        // NFKC/NFKD may convert compatibility characters to their regular equivalents
        $this->assertTrue($nfkc->isOk());
        $this->assertSame('N', $nfkc->unwrap()->toString());
    }
}
