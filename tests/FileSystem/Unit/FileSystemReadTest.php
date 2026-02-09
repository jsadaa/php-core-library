<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\FileSystem\Unit;

use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\FileNotFound;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\InvalidFileType;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\FileSystem;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

final class FileSystemReadTest extends TestCase
{
    private vfsStreamDirectory $root;
    private string $testContent = 'This is test content with multiple lines.\nLine 2\nLine 3';
    private string $binaryContent;

    protected function setUp(): void
    {
        $this->binaryContent = \pack('C*', 0x00, 0x01, 0x02, 0x03, 0xFF, 0xFE);

        $this->root = vfsStream::setup('root', null, [
            'testFile.txt' => $this->testContent,
            'binaryFile.bin' => $this->binaryContent,
            'emptyFile.txt' => '',
            'emptyDir' => [],
            'nestedDir' => [
                'nestedFile.txt' => 'nested content',
            ],
        ]);

        // Create a real file the system temp for problems with symlinks in vfsStream
        $tempFile = \sys_get_temp_dir() . '/concrete.txt';
        \file_put_contents($tempFile, $this->testContent);

        $linkPath = \sys_get_temp_dir() . '/concreteLink';
        \symlink($tempFile, $linkPath);
    }

    protected function tearDown(): void
    {
        \unlink(\sys_get_temp_dir() . '/concrete.txt');
        \unlink(\sys_get_temp_dir() . '/concreteLink');
    }

    public function testReadBytesFromFile(): void
    {
        $path = Path::of($this->root->url() . '/testFile.txt');
        $result = FileSystem::readBytes($path);

        $this->assertTrue($result->isOk());
        $bytes = $result->unwrap();

        $this->assertSame(\strlen($this->testContent), $bytes->size());
        $this->assertSame(\ord('T'), $bytes->get(0)->unwrap());
    }

    public function testReadBytesFromBinaryFile(): void
    {
        $path = Path::of($this->root->url() . '/binaryFile.bin');
        $result = FileSystem::readBytes($path);

        $this->assertTrue($result->isOk());
        $bytes = $result->unwrap();

        $this->assertSame(6, $bytes->size());
        $this->assertSame(0x00, $bytes->get(0)->unwrap());
        $this->assertSame(0xFF, $bytes->get(4)->unwrap());
    }

    public function testReadBytesFromNonExistentFile(): void
    {
        $path = Path::of($this->root->url() . '/nonExistent.txt');
        $result = FileSystem::readBytes($path);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(FileNotFound::class, $result->unwrapErr());
    }

    public function testReadDir(): void
    {
        $path = Path::of($this->root->url());
        $result = FileSystem::readDir($path);

        $this->assertTrue($result->isOk());
        $entries = $result->unwrap();

        $this->assertEquals(5, $entries->size()); // 5 entries in root

        $entries->forEach(function($entry) {
            $this->assertInstanceOf(Path::class, $entry);
        });

        $entryNames = $entries->map(static fn(Path $entry) => $entry->fileName()->unwrap()->toString());

        $this->assertTrue($entryNames->contains('testFile.txt'));
        $this->assertTrue($entryNames->contains('binaryFile.bin'));
        $this->assertTrue($entryNames->contains('emptyFile.txt'));
        $this->assertTrue($entryNames->contains('emptyDir'));
        $this->assertTrue($entryNames->contains('nestedDir'));
    }

    public function testReadDirForNonExistentDirectory(): void
    {
        $path = Path::of($this->root->url() . '/nonExistentDir');
        $result = FileSystem::readDir($path);

        $this->assertTrue($result->isErr());
    }

    public function testReadLink(): void
    {
        $path = Path::of(\sys_get_temp_dir() . '/concreteLink');
        $result = FileSystem::readSymlink($path);

        $this->assertTrue($result->isOk());
        $this->assertEquals(\sys_get_temp_dir() . '/concrete.txt', $result->unwrap()->toString());
    }

    public function testReadLinkForNonSymlink(): void
    {
        $path = Path::of($this->root->url() . '/testFile.txt');
        $result = FileSystem::readSymlink($path);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(InvalidFileType::class, $result->unwrapErr());
    }

    public function testReadToString(): void
    {
        $path = Path::of($this->root->url() . '/testFile.txt');
        $result = FileSystem::read($path);

        $this->assertTrue($result->isOk());
        $content = $result->unwrap();

        $this->assertSame($this->testContent, $content->toString());
    }

    public function testReadToStringFromNonExistentFile(): void
    {
        $path = Path::of($this->root->url() . '/nonExistent.txt');
        $result = FileSystem::read($path);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(FileNotFound::class, $result->unwrapErr());
    }

    public function testReadToStr(): void
    {
        $path = Path::of($this->root->url() . '/testFile.txt');
        $result = FileSystem::read($path);

        $this->assertTrue($result->isOk());
        $content = $result->unwrap();

        $this->assertInstanceOf(Str::class, $content);
        $this->assertSame($this->testContent, $content->toString());
    }

    public function testReadToStrFromNonExistentFile(): void
    {
        $path = Path::of($this->root->url() . '/nonExistent.txt');
        $result = FileSystem::read($path);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(FileNotFound::class, $result->unwrapErr());
    }
}
