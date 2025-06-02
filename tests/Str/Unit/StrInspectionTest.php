<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Str\Unit;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use PHPUnit\Framework\TestCase;

final class StrInspectionTest extends TestCase
{
    public function testCharCountWithAscii(): void
    {
        $str = Str::from('Hello');

        $this->assertSame(5, $str->chars()->len()->toInt());
    }

    public function testCharCountWithAccents(): void
    {
        $str = Str::from('Héllö');
        $this->assertSame(5, $str->chars()->len()->toInt(), 'Length should match the number of visible characters');

        $str2 = Str::from('café à la crème');
        $this->assertSame(15, $str2->chars()->len()->toInt(), 'Length should count each accented character as a single character');
    }

    public function testCharCountWithEmojis(): void
    {
        $str = Str::from('Hello 😀😁😊');
        $this->assertSame(9, $str->chars()->len()->toInt(), 'Length matches the expected behavior');

        $str2 = Str::from('👨‍👩‍👧‍👦👨‍💻');
        // For complex emojis with ZWJ
        $this->assertEquals(10, $str2->chars()->len()->toInt());

        $str3 = Str::from('⚠️🚀✅❤️⭐');
        $this->assertEquals(7, $str3->chars()->len()->toInt());
    }

    public function testIsEmptyWithEmptyString(): void
    {
        $str = Str::from('');

        $this->assertTrue($str->isEmpty());
    }

    public function testIsEmptyWithNonEmptyString(): void
    {
        $str = Str::from('Hello');

        $this->assertFalse($str->isEmpty());
    }

    public function testIsEmptyAfterClear(): void
    {
        $str = Str::from('Hello');
        $emptyStr = $str->clear();

        $this->assertFalse(
            $str->isEmpty(),
            'Original string should remain unchanged',
        );
        $this->assertTrue($emptyStr->isEmpty());
    }

    public function testContainsAsciiSubstring(): void
    {
        $str = Str::from('Hello world');

        $this->assertTrue($str->contains('world'));
        $this->assertTrue($str->contains('Hello'));
        $this->assertTrue($str->contains('o w'));
        $this->assertFalse($str->contains('goodbye'));
    }

    public function testContainsUtf8Substring(): void
    {
        $str = Str::from('Héllö wörld');

        $this->assertTrue($str->contains('ö'));
        $this->assertTrue($str->contains('Héll'));
        $this->assertFalse($str->contains('Hello'));

        $str2 = Str::from('Hello 😀 World 😄');
        $this->assertTrue($str2->contains('😀'));
        $this->assertTrue($str2->contains('😄'));
        $this->assertFalse($str2->contains('😎'));

        $str3 = Str::from('English 中文 العربية Русский');
        $this->assertTrue($str3->contains('中文')); // Chinese
        $this->assertTrue($str3->contains('العربية')); // Arabic
        $this->assertTrue($str3->contains('Русский')); // Russian
    }

    public function testContainsEmptyString(): void
    {
        $str = Str::from('Hello');

        $this->assertFalse($str->contains(''));
    }

    public function testContainsOnEmptyStr(): void
    {
        $str = Str::new();

        $this->assertFalse($str->contains('anything'));
    }

    public function testStartsWith(): void
    {
        $str = Str::from('Hello world');

        $this->assertTrue($str->startsWith('Hello'));
        $this->assertTrue($str->startsWith('H'));
        $this->assertFalse($str->startsWith('hello'));
        $this->assertFalse($str->startsWith('world'));
    }

    public function testStartsWithUtf8(): void
    {
        $str = Str::from('Héllö wörld');

        $this->assertTrue($str->startsWith('Hé'));
        $this->assertFalse($str->startsWith('He'));

        $str2 = Str::from('👍Hello world');
        $this->assertTrue($str2->startsWith('👍'));

        $str3 = Str::from('中文 is Chinese');
        $this->assertTrue($str3->startsWith('中文'));
        $this->assertFalse($str3->startsWith('Chinese'));
    }

    public function testStartsWithOnEmptyStr(): void
    {
        $str = Str::new();

        $this->assertFalse($str->startsWith('Hello'));
    }

    public function testEndsWith(): void
    {
        $str = Str::from('Hello world');

        $this->assertTrue($str->endsWith('world'));
        $this->assertTrue($str->endsWith('d'));
        $this->assertFalse($str->endsWith('World'));
        $this->assertFalse($str->endsWith('Hello'));
    }

    public function testEndsWithUtf8(): void
    {
        $str = Str::from('Héllö wörld');

        $this->assertTrue($str->endsWith('wörld'));
        $this->assertFalse($str->endsWith('world'));

        $str2 = Str::from('Hello world 😀');
        $this->assertTrue($str2->endsWith('😀'));

        $str3 = Str::from('Hello in Arabic is مرحبا');
        $this->assertTrue($str3->endsWith('مرحبا'));
        $this->assertFalse($str3->endsWith('hello'));

        $str4 = Str::from('Multiple scripts: Русский 中文 العربية');
        $this->assertTrue($str4->endsWith('العربية'));
    }

    public function testEndsWithOnEmptyStr(): void
    {
        $str = Str::new();

        $this->assertFalse($str->endsWith('Hello'));
    }

    public function testIsValidUtf8WithValidString(): void
    {
        $str = Str::from('Hello 😀 world');
        $this->assertTrue($str->isValidUtf8());

        $mixedStr = Str::from(
            'English, العربية (Arabic), 中文 (Chinese), ' .
            'Русский (Russian), हिन्दी (Hindi), ' .
            '😄😊🍕 (Emoji)',
        );
        $this->assertTrue($mixedStr->isValidUtf8(), 'Devrait accepter tous les caractères UTF-8 valides');
    }

    public function testIsAsciiWithAsciiString(): void
    {
        $str = Str::from('Hello world 123');

        $this->assertTrue($str->isAscii());
    }

    public function testIsAsciiWithNonAsciiString(): void
    {
        $nonAscii = \pack('C*', 72, 195, 169, 108, 108, 111);
        $str = Str::from($nonAscii);

        $this->assertFalse($str->isAscii());
    }

    public function testIsAsciiWithEmojis(): void
    {
        $withEmoji = \pack('C*', 72, 101, 108, 108, 111, 240, 159, 152, 128); // 'Hello😀' in binary
        $str = Str::from($withEmoji);

        $this->assertFalse($str->isAscii());
        $this->assertSame('Hello😀', $str->toString());
    }
}
