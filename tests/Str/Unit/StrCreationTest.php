<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Str\Unit;

use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use PHPUnit\Framework\TestCase;

final class StrCreationTest extends TestCase
{
    public function testCreateEmptyStr(): void
    {
        $str = Str::new();

        $this->assertTrue($str->isEmpty());
        $this->assertSame('', $str->toString());
    }

    public function testCreateStrFromAscii(): void
    {
        $str = Str::from('Hello world');

        $this->assertFalse($str->isEmpty());
        $this->assertSame('Hello world', $str->toString());
        $this->assertTrue($str->isAscii());
    }

    public function testCreateStrFromUtf8WithAccents(): void
    {
        $str = Str::from('Héllö wörld');

        $this->assertFalse($str->isEmpty());
        $this->assertSame('Héllö wörld', $str->toString());
        $this->assertFalse($str->isAscii());
    }

    public function testCreateStrFromUtf8WithEmojis(): void
    {
        $str = Str::from('Hello 🌍 world 😊');

        $this->assertFalse($str->isEmpty());
        $this->assertSame('Hello 🌍 world 😊', $str->toString());
        $this->assertFalse($str->isAscii());
    }

    public function testCreateStrFromUtf8WithChinese(): void
    {
        $str = Str::from('你好世界');

        $this->assertFalse($str->isEmpty());
        $this->assertSame('你好世界', $str->toString());
        $this->assertFalse($str->isAscii());
    }

    public function testCreateStrFromUtf8WithArabic(): void
    {
        $str = Str::from('مرحبا بالعالم');

        $this->assertFalse($str->isEmpty());
        $this->assertSame('مرحبا بالعالم', $str->toString());
        $this->assertFalse($str->isAscii());
    }

    public function testCreateStrWithExplicitEncoding(): void
    {
        $iso8859String = \mb_convert_encoding('Héllö wörld', 'ISO-8859-1', 'UTF-8');

        $str = Str::from($iso8859String)->forceUtf8('ISO-8859-1')->unwrap();

        $this->assertSame('Héllö wörld', $str->toString());
    }

    public function testCreateStrFromUtf8WithBom(): void
    {
        $bomString = "\xEF\xBB\xBFHello world";

        $str = Str::from($bomString);

        $this->assertStringEndsWith('Hello world', $str->toString());
    }

    public function testStringConversion(): void
    {
        $str = Str::from('Hello world');

        $this->assertSame('Hello world', (string)$str);
    }

    public function testCreateStrFromEmptyString(): void
    {
        $str = Str::from('');

        $this->assertTrue($str->isEmpty());
        $this->assertSame('', $str->toString());
    }
}
