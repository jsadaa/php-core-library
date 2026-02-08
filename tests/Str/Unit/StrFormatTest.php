<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Str\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Str::format() method.
 */
final class StrFormatTest extends TestCase
{
    public function testFormatPositionalSingleArg(): void
    {
        $result = Str::format('Hello, {}!', 'world');

        $this->assertInstanceOf(Str::class, $result);
        $this->assertSame('Hello, world!', $result->toString());
    }

    public function testFormatPositionalMultipleArgs(): void
    {
        $result = Str::format('{} + {} = {}', 1, 2, 3);

        $this->assertSame('1 + 2 = 3', $result->toString());
    }

    public function testFormatNamedArgs(): void
    {
        $result = Str::format('Hello, {name}!', name: 'Alice');

        $this->assertSame('Hello, Alice!', $result->toString());
    }

    public function testFormatMultipleNamedArgs(): void
    {
        $result = Str::format('{host}:{port}', host: 'localhost', port: 8080);

        $this->assertSame('localhost:8080', $result->toString());
    }

    public function testFormatMixedPositionalAndNamed(): void
    {
        $result = Str::format('{} at {location}', 'Event', location: 'Paris');

        $this->assertSame('Event at Paris', $result->toString());
    }

    public function testFormatWithStringableStr(): void
    {
        $str = Str::of('world');
        $result = Str::format('Hello, {}!', $str);

        $this->assertSame('Hello, world!', $result->toString());
    }

    public function testFormatWithStringablePath(): void
    {
        $path = Path::of('/var/www');
        $result = Str::format('Path: {}', $path);

        $this->assertSame('Path: /var/www', $result->toString());
    }

    public function testFormatEscapedBraces(): void
    {
        $result = Str::format('{{not a placeholder}}');

        $this->assertSame('{not a placeholder}', $result->toString());
    }

    public function testFormatEscapedBracesWithArgs(): void
    {
        $result = Str::format('{{literal}} and {}', 'value');

        $this->assertSame('{literal} and value', $result->toString());
    }

    public function testFormatBoolTrue(): void
    {
        $result = Str::format('Value: {}', true);

        $this->assertSame('Value: true', $result->toString());
    }

    public function testFormatBoolFalse(): void
    {
        $result = Str::format('Value: {}', false);

        $this->assertSame('Value: false', $result->toString());
    }

    public function testFormatNullValue(): void
    {
        $result = Str::format('Value: {}', null);

        $this->assertSame('Value: null', $result->toString());
    }

    public function testFormatFloatValue(): void
    {
        $result = Str::format('Pi: {}', 3.14);

        $this->assertSame('Pi: 3.14', $result->toString());
    }

    public function testFormatIntValue(): void
    {
        $result = Str::format('Count: {}', 42);

        $this->assertSame('Count: 42', $result->toString());
    }

    public function testFormatNoArgsReturnsTemplate(): void
    {
        $result = Str::format('No args here');

        $this->assertSame('No args here', $result->toString());
    }

    public function testFormatEmptyTemplate(): void
    {
        $result = Str::format('');

        $this->assertSame('', $result->toString());
    }

    public function testFormatUtf8Template(): void
    {
        $result = Str::format('Bonjour {} !', 'café');

        $this->assertSame('Bonjour café !', $result->toString());
    }

    public function testFormatReturnsStrInstance(): void
    {
        $result = Str::format('test');

        $this->assertInstanceOf(Str::class, $result);
    }

    public function testFormatTooFewPositionalArgsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Not enough positional arguments');

        Str::format('{} and {}', 'only one');
    }

    public function testFormatNonStringableObjectThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot format value of type');

        Str::format('Value: {}', new \stdClass());
    }

    public function testFormatNamedWithStringable(): void
    {
        $result = Str::format('File: {path}', path: Path::of('/tmp/test.txt'));

        $this->assertSame('File: /tmp/test.txt', $result->toString());
    }

    public function testFormatOnlyEscapedBracesNoArgs(): void
    {
        $result = Str::format('Use {{}} for placeholders');

        $this->assertSame('Use {} for placeholders', $result->toString());
    }

    public function testFormatStringArg(): void
    {
        $result = Str::format('Hello {}', 'world');

        $this->assertSame('Hello world', $result->toString());
    }
}
