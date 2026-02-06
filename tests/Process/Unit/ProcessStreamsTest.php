<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Process\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Process\FileDescriptor;
use Jsadaa\PhpCoreLibrary\Modules\Process\ProcessStreams;
use Jsadaa\PhpCoreLibrary\Modules\Process\StreamDescriptor;
use PHPUnit\Framework\TestCase;

class ProcessStreamsTest extends TestCase
{
    public function testDefaults(): void
    {
        $streams = ProcessStreams::defaults();

        $stdin = $streams->get(FileDescriptor::stdin());
        $stdout = $streams->get(FileDescriptor::stdout());
        $stderr = $streams->get(FileDescriptor::stderr());

        $this->assertTrue($stdin->isSome());
        $this->assertTrue($stdout->isSome());
        $this->assertTrue($stderr->isSome());

        $this->assertTrue($stdin->unwrap()->isPipe());
        $this->assertTrue($stdout->unwrap()->isPipe());
        $this->assertTrue($stderr->unwrap()->isPipe());
    }

    public function testNull(): void
    {
        $streams = ProcessStreams::null();

        $stdin = $streams->get(FileDescriptor::stdin());
        $this->assertTrue($stdin->isSome());
        $this->assertTrue($stdin->unwrap()->isNull());
    }

    public function testInherit(): void
    {
        $streams = ProcessStreams::inherit();

        $stdin = $streams->get(FileDescriptor::stdin());
        $this->assertTrue($stdin->isSome());
        $this->assertTrue($stdin->unwrap()->isInherit());
    }

    public function testWithStdin(): void
    {
        $streams = ProcessStreams::defaults()
            ->withStdin(StreamDescriptor::null());

        $stdin = $streams->get(FileDescriptor::stdin());
        $this->assertTrue($stdin->isSome());
        $this->assertTrue($stdin->unwrap()->isNull());

        // stdout/stderr remain pipes
        $stdout = $streams->get(FileDescriptor::stdout());
        $this->assertTrue($stdout->unwrap()->isPipe());
    }

    public function testWithStdout(): void
    {
        $streams = ProcessStreams::defaults()
            ->withStdout(StreamDescriptor::file('/tmp/out.txt', 'w'));

        $stdout = $streams->get(FileDescriptor::stdout());
        $this->assertTrue($stdout->isSome());
        $this->assertTrue($stdout->unwrap()->isFile());
    }

    public function testWithStderr(): void
    {
        $streams = ProcessStreams::defaults()
            ->withStderr(StreamDescriptor::null());

        $stderr = $streams->get(FileDescriptor::stderr());
        $this->assertTrue($stderr->isSome());
        $this->assertTrue($stderr->unwrap()->isNull());
    }

    public function testWithCustomDescriptor(): void
    {
        $streams = ProcessStreams::defaults()
            ->withDescriptor(FileDescriptor::custom(3), StreamDescriptor::pipe('w'));

        $custom = $streams->get(FileDescriptor::custom(3));
        $this->assertTrue($custom->isSome());
        $this->assertTrue($custom->unwrap()->isPipe());
    }

    public function testToDescriptorArray(): void
    {
        $streams = ProcessStreams::defaults();
        $array = $streams->toDescriptorArray();

        $this->assertArrayHasKey(0, $array);
        $this->assertArrayHasKey(1, $array);
        $this->assertArrayHasKey(2, $array);
    }

    public function testImmutability(): void
    {
        $original = ProcessStreams::defaults();
        $modified = $original->withStdin(StreamDescriptor::null());

        $this->assertTrue($original->get(FileDescriptor::stdin())->unwrap()->isPipe());
        $this->assertTrue($modified->get(FileDescriptor::stdin())->unwrap()->isNull());
    }
}
