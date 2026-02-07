<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Char\Unit;

use Jsadaa\PhpCoreLibrary\Primitives\Char\Char;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use PHPUnit\Framework\TestCase;

class CharTest extends TestCase
{
    // -- Factory --

    public function testOfAscii(): void
    {
        $char = Char::of('A');
        $this->assertSame('A', $char->toString());
    }

    public function testOfMultibyte(): void
    {
        $char = Char::of('é');
        $this->assertSame('é', $char->toString());
    }

    public function testOfEmoji(): void
    {
        $char = Char::of('🎉');
        $this->assertSame('🎉', $char->toString());
    }

    public function testOfCjk(): void
    {
        $char = Char::of('中');
        $this->assertSame('中', $char->toString());
    }

    public function testOfEmptyStringThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Char::of('');
    }

    public function testOfMultiCharThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Char::of('AB');
    }

    public function testOfMultibyteMultiCharThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Char::of('éà');
    }

    // -- ofDigit --

    public function testOfDigitValid(): void
    {
        for ($i = 0; $i <= 9; $i++) {
            $char = Char::ofDigit($i);
            $this->assertSame((string) $i, $char->toString());
        }
    }

    public function testOfDigitWithInteger(): void
    {
        $char = Char::ofDigit(Integer::of(5));
        $this->assertSame('5', $char->toString());
    }

    public function testOfDigitNegativeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Char::ofDigit(-1);
    }

    public function testOfDigitAboveNineThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Char::ofDigit(10);
    }

    // -- Classification (Unicode-aware) --

    public function testIsAlphabeticAscii(): void
    {
        $this->assertTrue(Char::of('A')->isAlphabetic());
        $this->assertTrue(Char::of('z')->isAlphabetic());
        $this->assertFalse(Char::of('5')->isAlphabetic());
        $this->assertFalse(Char::of('!')->isAlphabetic());
    }

    public function testIsAlphabeticUnicode(): void
    {
        $this->assertTrue(Char::of('é')->isAlphabetic());
        $this->assertTrue(Char::of('Ö')->isAlphabetic());
        $this->assertTrue(Char::of('中')->isAlphabetic());
    }

    public function testIsDigitAscii(): void
    {
        $this->assertTrue(Char::of('5')->isDigit());
        $this->assertTrue(Char::of('0')->isDigit());
        $this->assertFalse(Char::of('A')->isDigit());
    }

    public function testIsDigitUnicode(): void
    {
        // Arabic-Indic digit three (U+0663)
        $this->assertTrue(Char::of("\u{0663}")->isDigit());
    }

    public function testIsAlphanumeric(): void
    {
        $this->assertTrue(Char::of('A')->isAlphanumeric());
        $this->assertTrue(Char::of('5')->isAlphanumeric());
        $this->assertTrue(Char::of('é')->isAlphanumeric());
        $this->assertFalse(Char::of('!')->isAlphanumeric());
    }

    public function testIsWhitespaceAscii(): void
    {
        $this->assertTrue(Char::of(' ')->isWhitespace());
        $this->assertTrue(Char::of("\t")->isWhitespace());
        $this->assertTrue(Char::of("\n")->isWhitespace());
        $this->assertFalse(Char::of('A')->isWhitespace());
    }

    public function testIsWhitespaceUnicode(): void
    {
        // Em space (U+2003) is Unicode whitespace
        $this->assertTrue(Char::of("\u{2003}")->isWhitespace());
        // Non-breaking space (U+00A0) is NOT whitespace under ICU rules
        $this->assertFalse(Char::of("\u{00A0}")->isWhitespace());
    }

    public function testIsLowercase(): void
    {
        $this->assertTrue(Char::of('a')->isLowercase());
        $this->assertTrue(Char::of('é')->isLowercase());
        $this->assertFalse(Char::of('A')->isLowercase());
        $this->assertFalse(Char::of('É')->isLowercase());
        $this->assertFalse(Char::of('5')->isLowercase());
    }

    public function testIsUppercase(): void
    {
        $this->assertTrue(Char::of('A')->isUppercase());
        $this->assertTrue(Char::of('É')->isUppercase());
        $this->assertFalse(Char::of('a')->isUppercase());
        $this->assertFalse(Char::of('é')->isUppercase());
        $this->assertFalse(Char::of('5')->isUppercase());
    }

    public function testIsPunctuation(): void
    {
        $this->assertTrue(Char::of('!')->isPunctuation());
        $this->assertTrue(Char::of('.')->isPunctuation());
        $this->assertTrue(Char::of(',')->isPunctuation());
        $this->assertFalse(Char::of('A')->isPunctuation());
    }

    public function testIsControl(): void
    {
        $this->assertTrue(Char::of("\x00")->isControl());
        $this->assertTrue(Char::of("\x1F")->isControl());
        $this->assertFalse(Char::of('A')->isControl());
    }

    public function testIsPrintable(): void
    {
        $this->assertTrue(Char::of('A')->isPrintable());
        $this->assertTrue(Char::of('é')->isPrintable());
        $this->assertTrue(Char::of(' ')->isPrintable());
        $this->assertFalse(Char::of("\x00")->isPrintable());
    }

    public function testIsHexadecimal(): void
    {
        $this->assertTrue(Char::of('0')->isHexadecimal());
        $this->assertTrue(Char::of('9')->isHexadecimal());
        $this->assertTrue(Char::of('a')->isHexadecimal());
        $this->assertTrue(Char::of('F')->isHexadecimal());
        $this->assertFalse(Char::of('G')->isHexadecimal());
        $this->assertFalse(Char::of('z')->isHexadecimal());
    }

    public function testIsAscii(): void
    {
        $this->assertTrue(Char::of('A')->isAscii());
        $this->assertTrue(Char::of('0')->isAscii());
        $this->assertTrue(Char::of(' ')->isAscii());
        $this->assertFalse(Char::of('é')->isAscii());
        $this->assertFalse(Char::of('中')->isAscii());
        $this->assertFalse(Char::of('🎉')->isAscii());
    }

    // -- Conversion --

    public function testToUppercase(): void
    {
        $this->assertSame('A', Char::of('a')->toUppercase()->toString());
        $this->assertSame('É', Char::of('é')->toUppercase()->toString());
        $this->assertSame('Ö', Char::of('ö')->toUppercase()->toString());
    }

    public function testToLowercase(): void
    {
        $this->assertSame('a', Char::of('A')->toLowercase()->toString());
        $this->assertSame('é', Char::of('É')->toLowercase()->toString());
        $this->assertSame('ö', Char::of('Ö')->toLowercase()->toString());
    }

    // -- toString / __toString --

    public function testToStringMethod(): void
    {
        $char = Char::of('X');
        $this->assertSame('X', $char->toString());
        $this->assertSame('X', (string) $char);
    }

    public function testToStringMultibyte(): void
    {
        $char = Char::of('é');
        $this->assertSame('é', $char->toString());
        $this->assertSame('é', (string) $char);
    }
}
