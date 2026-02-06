<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Process\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use Jsadaa\PhpCoreLibrary\Modules\Process\StreamDescriptor;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use PHPUnit\Framework\TestCase;

class StreamDescriptorTest extends TestCase
{
    public function testPipe(): void
    {
        $desc = StreamDescriptor::pipe('w');
        $this->assertTrue($desc->isPipe());
        $this->assertFalse($desc->isFile());
        $this->assertFalse($desc->isResource());
        $this->assertFalse($desc->isInherit());
        $this->assertFalse($desc->isNull());
    }

    public function testPipeDefaultMode(): void
    {
        $desc = StreamDescriptor::pipe();
        $this->assertTrue($desc->isPipe());

        $result = $desc->toDescriptor();
        $this->assertIsArray($result);
        $this->assertEquals(['pipe', 'r'], $result);
    }

    public function testPipeWithStrMode(): void
    {
        $desc = StreamDescriptor::pipe(Str::of('w'));
        $result = $desc->toDescriptor();
        $this->assertEquals(['pipe', 'w'], $result);
    }

    public function testFile(): void
    {
        $desc = StreamDescriptor::file('/tmp/test.txt', 'w');
        $this->assertTrue($desc->isFile());

        $result = $desc->toDescriptor();
        $this->assertIsArray($result);
        $this->assertEquals(['file', '/tmp/test.txt', 'w'], $result);
    }

    public function testFileWithPathAndStr(): void
    {
        $desc = StreamDescriptor::file(Path::of('/tmp/out.txt'), Str::of('a'));
        $result = $desc->toDescriptor();
        $this->assertEquals(['file', '/tmp/out.txt', 'a'], $result);
    }

    public function testResource(): void
    {
        $stream = \fopen('php://memory', 'r');
        $this->assertNotFalse($stream);

        $desc = StreamDescriptor::resource($stream);
        $this->assertTrue($desc->isResource());

        $result = $desc->toDescriptor();
        $this->assertIsResource($result);

        \fclose($stream);
    }

    public function testInherit(): void
    {
        $desc = StreamDescriptor::inherit();
        $this->assertTrue($desc->isInherit());
    }

    public function testNull(): void
    {
        $desc = StreamDescriptor::null();
        $this->assertTrue($desc->isNull());

        $result = $desc->toDescriptor();
        $this->assertIsArray($result);

        if (\PHP_OS_FAMILY === 'Windows') {
            $this->assertEquals(['file', 'NUL', 'r'], $result);
        } else {
            $this->assertEquals(['file', '/dev/null', 'r'], $result);
        }
    }
}
