<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Str\Unit;

use Jsadaa\PhpCoreLibrary\Primitives\Str\Error\ParseError;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use PHPUnit\Framework\TestCase;

final class StrConversionTest extends TestCase
{
    public function testParseIntWithValidInput(): void
    {
        $str = Str::of('123');
        $result = $str->parseInteger();

        $this->assertTrue($result->isOk());
        $this->assertSame(123, $result->unwrap()->toInt());
    }

    public function testParseIntWithNegativeNumber(): void
    {
        $str = Str::of('-123');
        $result = $str->parseInteger();

        $this->assertTrue($result->isOk());
        $this->assertSame(-123, $result->unwrap()->toInt());
    }

    public function testParseIntWithInvalidInput(): void
    {
        $str = Str::of('123abc');
        $result = $str->parseInteger();

        $this->assertTrue($result->isErr());
    }

    public function testParseIntWithEmptyString(): void
    {
        $str = Str::new();
        $result = $str->parseInteger();

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(ParseError::class, $result->unwrapErr());
    }

    public function testParseIntWithFloatString(): void
    {
        $str = Str::of('123.45');
        $result = $str->parseInteger();

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(ParseError::class, $result->unwrapErr());
    }

    public function testParseFloatWithValidInput(): void
    {
        $str = Str::of('123.45');
        $result = $str->parseDouble();

        $this->assertTrue($result->isOk());
        $this->assertSame(123.45, $result->unwrap()->toFloat());
    }

    public function testParseFloatWithNegativeNumber(): void
    {
        $str = Str::of('-123.45');
        $result = $str->parseDouble();

        $this->assertTrue($result->isOk());
        $this->assertSame(-123.45, $result->unwrap()->toFloat());
    }

    public function testParseFloatWithIntegerString(): void
    {
        $str = Str::of('123');
        $result = $str->parseDouble();

        $this->assertTrue($result->isOk());
        $this->assertSame(123.0, $result->unwrap()->toFloat());
    }

    public function testParseFloatWithInvalidInput(): void
    {
        $str = Str::of('123.45abc');
        $result = $str->parseDouble();

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(ParseError::class, $result->unwrapErr());
    }

    public function testParseFloatWithEmptyString(): void
    {
        $str = Str::new();
        $result = $str->parseDouble();

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(ParseError::class, $result->unwrapErr());
    }

    public function testParseBoolWithTrueString(): void
    {
        $str = Str::of('true');
        $result = $str->parseBool();

        $this->assertTrue($result->isOk());
        $this->assertTrue($result->unwrap());
    }

    public function testParseBoolWithFalseString(): void
    {
        $str = Str::of('false');
        $result = $str->parseBool();

        $this->assertTrue($result->isOk());
        $this->assertFalse($result->unwrap());
    }

    public function testParseBoolWithOneString(): void
    {
        $str = Str::of('1');
        $result = $str->parseBool();

        $this->assertTrue($result->isOk());
        $this->assertTrue($result->unwrap());
    }

    public function testParseBoolWithZeroString(): void
    {
        $str = Str::of('0');
        $result = $str->parseBool();

        $this->assertTrue($result->isOk());
        $this->assertFalse($result->unwrap());
    }

    public function testParseBoolWithCaseInsensitiveInput(): void
    {
        $str = Str::of('TRUE');
        $result = $str->parseBool();

        $this->assertTrue($result->isOk());
        $this->assertTrue($result->unwrap());
    }

    public function testParseBoolWithInvalidInput(): void
    {
        $str = Str::of('maybe');
        $result = $str->parseBool();

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(ParseError::class, $result->unwrapErr());
    }

    public function testParseBoolWithEmptyString(): void
    {
        $str = Str::new();
        $result = $str->parseBool();

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(ParseError::class, $result->unwrapErr());
    }
}
