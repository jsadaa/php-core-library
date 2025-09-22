<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Str\Unit;

use Jsadaa\PhpCoreLibrary\Primitives\Str\Error\InvalidNormalizationForm;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use PHPUnit\Framework\TestCase;

final class StrTransformationTest extends TestCase
{
    public function testToLowercase(): void
    {
        $str = Str::of('Hello WORLD');
        $lower = $str->toLowercase();

        $this->assertSame('Hello WORLD', $str->toString(), 'Original string should remain unchanged');
        $this->assertSame('hello world', $lower->toString());
    }

    public function testToLowercaseWithEmptyString(): void
    {
        $str = Str::new();
        $lower = $str->toLowercase();

        $this->assertTrue($lower->isEmpty());
    }

    public function testToUppercase(): void
    {
        $str = Str::of('Hello world');
        $upper = $str->toUppercase();

        $this->assertSame('Hello world', $str->toString(), 'Original string should remain unchanged');
        $this->assertSame('HELLO WORLD', $upper->toString());
    }

    public function testToLowercaseWithVariousUtf8(): void
    {
        $str = Str::of('HÉLLÖ WÖRLD');
        $lower = $str->toLowercase();
        $this->assertSame('héllö wörld', $lower->toString());

        $str2 = Str::of('РУССКИЙ ЯЗЫК'); // RUSSKIY YAZYK
        $lower2 = $str2->toLowercase();
        $this->assertSame('русский язык', $lower2->toString());

        $str3 = Str::of('ΕΛΛΗΝΙΚΆ');
        $lower3 = $str3->toLowercase();
        $this->assertSame('ελληνικά', $lower3->toString());

        $strTurkish = Str::of('İSTANBUL');
        $lowerTurkish = $strTurkish->toLowercase();
        $expectedLower = \mb_strtolower($strTurkish->toString(), 'UTF-8');
        $this->assertSame($expectedLower, $lowerTurkish->toString());
    }

    public function testToUppercaseWithVariousUtf8(): void
    {
        $str = Str::of('héllö wörld');
        $upper = $str->toUppercase();
        $this->assertSame('HÉLLÖ WÖRLD', $upper->toString());

        $str2 = Str::of('русский язык'); // russkiy yazyk
        $upper2 = $str2->toUppercase();
        $this->assertSame('РУССКИЙ ЯЗЫК', $upper2->toString());

        $str3 = Str::of('ελληνικά');
        $upper3 = $str3->toUppercase();
        $this->assertSame('ΕΛΛΗΝΙΚΆ', $upper3->toString());
    }

    public function testNormalizeWithComplexUtf8(): void
    {
        if (!\function_exists('normalizer_normalize')) {
            $this->markTestSkipped('intl extension is not available');
        }

        // Test with composed vs precomposed characters
        // é can be represented as a single character (U+00E9) or as e + accent (U+0065 U+0301)
        $composed = Str::of('Café'); // with precomposed é
        $decomposed = Str::of("Cafe\xCC\x81"); // with e + combining accent

        // The two strings may be different internally depending on the implementation
        // but they should be visually equivalent

        // After normalization to NFC, they should be identical
        $normalizedComposed = $composed->normalize('NFC');
        $normalizedDecomposed = $decomposed->normalize('NFC');
        $this->assertSame($normalizedComposed->unwrap()->toString(), $normalizedDecomposed->unwrap()->toString());

        $nfcString = $composed->normalize('NFC')->unwrap()->toString();
        $nfdString = $composed->normalize('NFD')->unwrap()->toString();

        $this->assertTrue($nfcString !== $nfdString);
    }

    public function testTrim(): void
    {
        $str = Str::of('  Hello World  ');
        $trimmed = $str->trim();

        $this->assertSame('  Hello World  ', $str->toString(), 'Original string should remain unchanged');
        $this->assertSame('Hello World', $trimmed->toString());
    }

    public function testTrimStart(): void
    {
        $str = Str::of('  Hello World  ');
        $trimmed = $str->trimStart();

        $this->assertSame('Hello World  ', $trimmed->toString());
    }

    public function testTrimEnd(): void
    {
        $str = Str::of('  Hello World  ');
        $trimmed = $str->trimEnd();

        $this->assertSame('  Hello World', $trimmed->toString());
    }

    public function testTrimWithUtf8Whitespace(): void
    {
        // String with non-breaking spaces and other Unicode whitespace
        // Use actual Unicode characters instead of escape sequences
        $str = Str::of(" \u{00A0} \u{2002} Hello World \u{2003} \u{2009} ");
        $trimmed = $str->trim();

        $this->assertEquals('Hello World', $trimmed->toString());
    }

    public function testTrimWithEmptyString(): void
    {
        $str = Str::new();
        $trimmed = $str->trim();

        $this->assertTrue($trimmed->isEmpty());
    }

    public function testNormalize(): void
    {
        if (!\function_exists('normalizer_normalize')) {
            $this->markTestSkipped('intl extension is not available');
        }

        $str = Str::of('Hello');

        // Normalize to composed form (NFC)
        $normalized = $str->normalize('NFC');

        // Test that normalization doesn't change the visual representation
        $this->assertTrue($normalized->unwrap()->isValidUtf8());
    }

    public function testNormalizeWithInvalidForm(): void
    {
        $str = Str::of('Hello');

        $str = $str->normalize('INVALID');
        $this->assertEquals(
            new InvalidNormalizationForm('Invalid normalization form: INVALID. Valid options are: NFC, NFD, NFKC, NFKD'),
            $str->unwrapErr(),
            'Normalization with invalid form should not change the string',
        );
    }

    public function testEscapeUnicode(): void
    {
        $str = Str::of('Hello é');
        $escaped = $str->escapeUnicode();

        $this->assertSame('Hello \u00e9', $escaped->toString());

        $this->assertGreaterThan($str->size(), $escaped->size());
    }

    public function testNormalizeExtended(): void
    {
        if (!\function_exists('normalizer_normalize')) {
            $this->markTestSkipped('intl extension is not available');
        }

        // 1. Test with decomposed and composed characters (accented letters)
        $composed = Str::of('café'); // é as a single character
        $decomposed = Str::of("cafe\u{0301}"); // e + combining accent

        // Verify that NFC composes the decomposed characters
        $nfcDecomposed = $decomposed->normalize('NFC')->unwrap();
        $nfcComposed = $composed->normalize('NFC')->unwrap();
        $this->assertSame($nfcComposed->toString(), $nfcDecomposed->toString(),
            'NFC should compose decomposed characters');

        // Verify that NFD decomposes composed characters
        $nfdDecomposed = $decomposed->normalize('NFD')->unwrap();
        $nfdComposed = $composed->normalize('NFD')->unwrap();
        $this->assertSame($nfdDecomposed->toString(), $nfdComposed->toString(),
            'NFD should decompose composed characters');

        // 2. Test with ligatures and their equivalents (NFKC/NFKD)
        $ligature = Str::of('ﬁ'); // U+FB01: LATIN SMALL LIGATURE FI
        $separate = Str::of('fi'); // 'f' + 'i' separated

        // NFKC should convert the ligature to separate characters
        $nfkcLigature = $ligature->normalize('NFKC')->unwrap();
        $this->assertSame($separate->toString(), $nfkcLigature->toString(),
            'NFKC should convert ligatures to separate characters');

        // 3. Test with different symbols that have compatible equivalents
        $fraction = Str::of('½'); // U+00BD: VULGAR FRACTION ONE HALF
        $nfkcFraction = $fraction->normalize('NFKC')->unwrap();
        // Not exactly equal because implementations may vary, but should be different
        $this->assertNotSame($fraction->toString(), $nfkcFraction->toString(),
            'NFKC should transform fractions to compatible form');

        // 4. Test with special mathematical characters
        $math = Str::of('ℕ'); // U+2115: DOUBLE-STRUCK CAPITAL N
        $nfkcMath = $math->normalize('NFKC')->unwrap();
        $this->assertSame('N', $nfkcMath->toString(),
            'NFKC should convert mathematical characters to their ASCII equivalents');

        // 5. Test with hangul characters (Korean)
        $hangulComposed = Str::of('한');  // U+D55C: Korean syllable "han"
        $hangulNfd = $hangulComposed->normalize('NFD')->unwrap();
        $hangulNfc = $hangulNfd->normalize('NFC')->unwrap();
        $this->assertSame($hangulComposed->toString(), $hangulNfc->toString(),
            'NFC should correctly recompose decomposed hangul characters');

        // 6. Test with emojis
        $emoji = Str::of('👨‍👩‍👧'); // family emoji
        $emojiNfc = $emoji->normalize('NFC')->unwrap();
        $this->assertTrue($emojiNfc->isValidUtf8(),
            'Normalization of emojis must produce valid UTF-8');

        // 7. Test with empty string
        $empty = Str::new();
        $emptyNfc = $empty->normalize('NFC')->unwrap();
        $this->assertTrue($emptyNfc->isEmpty(),
            'Normalization of an empty string should produce an empty string');

        // 8. Test with pure ASCII string (should be invariant)
        $ascii = Str::of('Hello, world!');
        $asciiNfc = $ascii->normalize('NFC')->unwrap();
        $this->assertSame($ascii->toString(), $asciiNfc->toString(),
            'Normalization of a pure ASCII string should be invariant');

        // 9. Test with mixed multilingual strings
        $mixed = Str::of('Café ñ Київ 东京 مرحبا');
        $mixedNfc = $mixed->normalize('NFC')->unwrap();
        $this->assertTrue($mixedNfc->isValidUtf8(),
            'Normalization of a mixed multilingual string should produce valid UTF-8');
    }

    public function testNormalizeErrorCases(): void
    {
        if (!\function_exists('normalizer_normalize')) {
            $this->markTestSkipped('intl extension is not available');
        }

        $str = Str::of('Hello');
        $result = $str->normalize('INVALID_FORM');
        $this->assertTrue($result->isErr(),
            'Normalize should return an error for an invalid form');
        $this->assertInstanceOf(\InvalidArgumentException::class, $result->unwrapErr(),
            'The error should be an InvalidArgumentException');

        $emptyStr = Str::new();
        $normalized = $emptyStr->normalize('NFC');
        $this->assertTrue($normalized->isOk(),
            'Normalization of an empty string should succeed');
        $this->assertTrue($normalized->unwrap()->isEmpty(),
            'The result of normalizing an empty string should be an empty string');
    }

    public function testNormalizeComparison(): void
    {
        if (!\function_exists('normalizer_normalize')) {
            $this->markTestSkipped('intl extension is not available');
        }

        $str = Str::of('café résumé');

        $nfc = $str->normalize('NFC')->unwrap();
        $nfd = $str->normalize('NFD')->unwrap();
        $nfkc = $str->normalize('NFKC')->unwrap();
        $nfkd = $str->normalize('NFKD')->unwrap();

        $this->assertTrue($nfc->isValidUtf8(), 'NFC should be valid in UTF-8');
        $this->assertTrue($nfd->isValidUtf8(), 'NFD should be valid in UTF-8');
        $this->assertTrue($nfkc->isValidUtf8(), 'NFKC should be valid in UTF-8');
        $this->assertTrue($nfkd->isValidUtf8(), 'NFKD should be valid in UTF-8');

        $nfcAgain = $nfc->normalize('NFC')->unwrap();
        $this->assertEquals($nfc->toString(), $nfcAgain->toString(),
            'Normalizing again to NFC should not change a string already in NFC');

        // Check that normalized strings are visually equivalent
        // even if they may have different binary representations
        // With no automatic normalization, NFD and NFC may have different number of code points
        // but they should be visually equivalent
        $nfcLen = $nfc->chars()->size()->toInt();
        $nfdLen = $nfd->chars()->size()->toInt();

        // Instead of expecting same length, compare their visual representation
        // This approach acknowledges that without automatic normalization,
        // forms like 'é' (NFC) and 'e + ́' (NFD) will have different code point counts
        $this->assertEquals(11, $nfcLen, 'NFC string should have 11 code points'); // the 3 "é" in composed form, so it's the same count as visual representation
        $this->assertEquals(14, $nfdLen, 'NFD string should have 14 code points'); // the 3 "é" in decomposed form (e + combining accent), so you have 3 more characters

        $composedE = Str::of('é'); // precomposed character
        $decomposedE = Str::of("e\u{0301}"); // e + combining accent

        $composedNFC = $composedE->normalize('NFC')->unwrap();
        $decomposedNFC = $decomposedE->normalize('NFC')->unwrap();
        $this->assertSame($composedNFC->toString(), $decomposedNFC->toString(),
            'Different representations should be identical after NFC normalization');
    }

    public function testNormalizeForComparisons(): void
    {
        if (!\function_exists('normalizer_normalize')) {
            $this->markTestSkipped('intl extension is not available');
        }

        $str1 = Str::of('café'); // with precomposed character
        $str2 = Str::of("cafe\u{0301}"); // with decomposed character

        // Verify they are different at the binary level
        $this->assertNotEquals($str1->bytes()->toArray(), $str2->bytes()->toArray(),
            'Binary representations should be different');

        $nfc1 = $str1->normalize('NFC')->unwrap();
        $nfc2 = $str2->normalize('NFC')->unwrap();
        $this->assertEquals($nfc1->bytes()->toArray(), $nfc2->bytes()->toArray(),
            'After NFC normalization, binary representations should be identical');

        $nfd1 = $str1->normalize('NFD')->unwrap();
        $nfd2 = $str2->normalize('NFD')->unwrap();
        $this->assertEquals($nfd1->bytes()->toArray(), $nfd2->bytes()->toArray(),
            'After NFD normalization, binary representations should be identical');
    }

    public function testPadStart(): void
    {
        $str = Str::of('Hello');
        $padded = $str->padStart(10, '-');

        $this->assertSame('Hello', $str->toString(), 'Original string should remain unchanged');
        $this->assertSame('-----Hello', $padded->toString());
    }

    public function testPadStartWithSmallerTarget(): void
    {
        $str = Str::of('Hello');
        $padded = $str->padStart(3, '-');

        $this->assertSame('Hello', $padded->toString());
    }

    public function testPadStartWithMultiCharPad(): void
    {
        $str = Str::of('Hello');
        $padded = $str->padStart(10, 'ab');

        $this->assertSame('ababaHello', $padded->toString());
    }

    public function testPadStartWithUtf8(): void
    {
        $str = Str::of('Hello');
        $padded = $str->padStart(10, '😀');

        $this->assertStringStartsWith('😀', $padded->toString());
        $this->assertStringEndsWith('Hello', $padded->toString());
        $this->assertGreaterThan($str->chars()->size(), $padded->chars()->size());
        $this->assertSame(10, $padded->chars()->size()->toInt());
    }

    public function testPadEnd(): void
    {
        $str = Str::of('Hello');
        $padded = $str->padEnd(10, '-');

        $this->assertSame('Hello', $str->toString(), 'Original string should remain unchanged');
        $this->assertSame('Hello-----', $padded->toString());
    }

    public function testPadEndWithSmallerTarget(): void
    {
        $str = Str::of('Hello');
        $padded = $str->padEnd(3, '-');

        $this->assertSame('Hello', $padded->toString());
    }

    public function testPadEndWithMultiCharPad(): void
    {
        $str = Str::of('Hello');
        $padded = $str->padEnd(10, 'ab');

        $this->assertSame('Helloababa', $padded->toString());
    }

    public function testRepeat(): void
    {
        $str = Str::of('abc');
        $repeated = $str->repeat(3);

        $this->assertSame('abc', $str->toString(), 'Original string should remain unchanged');
        $this->assertSame('abcabcabc', $repeated->toString());
    }

    public function testRepeatWithZeroCount(): void
    {
        $str = Str::of('abc');
        $repeated = $str->repeat(0);

        $this->assertTrue($repeated->isEmpty());
    }

    public function testWrap(): void
    {
        $str = Str::of('The quick brown fox jumps over the lazy dog');
        $wrapped = $str->wrap(10);

        $lines = \preg_split('/\r?\n/', $wrapped->toString());
        $this->assertCount(5, $lines);
        $this->assertSame('The quick', $lines[0]);
        $this->assertStringContainsString('brown fox', $lines[1]);
        $this->assertStringContainsString('jumps over', $lines[2]);
        $this->assertStringContainsString('the lazy', $lines[3]);
        $this->assertStringContainsString('dog', $lines[4]);
    }

    public function testWrapWithCustomBreak(): void
    {
        $str = Str::of('The quick brown fox jumps over the lazy dog');
        $wrapped = $str->wrap(10, '<br>');

        $this->assertSame('The quick<br>brown fox<br>jumps over<br>the lazy<br>dog', $wrapped->toString());
    }

    public function testStripPrefixWithMatch(): void
    {
        $str = Str::of('HelloWorld');
        $stripped = $str->stripPrefix('Hello');

        $this->assertSame('HelloWorld', $str->toString(), 'Original string should remain unchanged');
        $this->assertSame('World', $stripped->toString());
    }

    public function testStripPrefixWithoutMatch(): void
    {
        $str = Str::of('HelloWorld');
        $stripped = $str->stripPrefix('Hi');

        $this->assertSame('HelloWorld', $stripped->toString());
    }

    public function testStripPrefixWithUtf8(): void
    {
        $str = Str::of('😀étudiant');
        $stripped = $str->stripPrefix('😀');

        $this->assertSame('étudiant', $stripped->toString());
    }

    public function testStripSuffixWithMatch(): void
    {
        $str = Str::of('HelloWorld');
        $stripped = $str->stripSuffix('World');

        $this->assertSame('HelloWorld', $str->toString(), 'Original string should remain unchanged');
        $this->assertSame('Hello', $stripped->toString());
    }

    public function testStripSuffixWithoutMatch(): void
    {
        $str = Str::of('HelloWorld');
        $stripped = $str->stripSuffix('Universe');

        $this->assertSame('HelloWorld', $stripped->toString());
    }

    public function testStripSuffixWithUtf8(): void
    {
        $str = Str::of('maison😀');
        $stripped = $str->stripSuffix('😀');

        $this->assertSame('maison', $stripped->toString());
    }
}
