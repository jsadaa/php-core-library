<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Process\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Process\FileDescriptor;
use PHPUnit\Framework\TestCase;

class FileDescriptorTest extends TestCase
{
    public function testStdin(): void
    {
        $fd = FileDescriptor::stdin();
        $this->assertTrue($fd->isStdin());
        $this->assertFalse($fd->isStdout());
        $this->assertFalse($fd->isStderr());
        $this->assertEquals(0, $fd->toInt());
    }

    public function testStdout(): void
    {
        $fd = FileDescriptor::stdout();
        $this->assertFalse($fd->isStdin());
        $this->assertTrue($fd->isStdout());
        $this->assertFalse($fd->isStderr());
        $this->assertEquals(1, $fd->toInt());
    }

    public function testStderr(): void
    {
        $fd = FileDescriptor::stderr();
        $this->assertFalse($fd->isStdin());
        $this->assertFalse($fd->isStdout());
        $this->assertTrue($fd->isStderr());
        $this->assertEquals(2, $fd->toInt());
    }

    public function testCustom(): void
    {
        $fd = FileDescriptor::custom(5);
        $this->assertFalse($fd->isStdin());
        $this->assertFalse($fd->isStdout());
        $this->assertFalse($fd->isStderr());
        $this->assertEquals(5, $fd->toInt());
    }

    public function testCustomWithNativeInt(): void
    {
        $fd = FileDescriptor::custom(3);
        $this->assertEquals(3, $fd->toInt());
    }

    public function testEquality(): void
    {
        $a = FileDescriptor::stdin();
        $b = FileDescriptor::stdin();
        $c = FileDescriptor::stdout();

        $this->assertTrue($a->eq($b));
        $this->assertFalse($a->eq($c));
    }

    public function testNumber(): void
    {
        $fd = FileDescriptor::custom(7);
        $this->assertEquals(7, $fd->number());
    }
}
