<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\FileSystem\Unit;

use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\FileNotFound;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\WriteFailed;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\FileSystem;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

final class FileSystemWriteTest extends TestCase
{
    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup('root', 0777, [
            'testFile.txt' => 'initial content',
            'emptyDir' => [],
            'readOnlyFile.txt' => 'read only content',
            'readOnlyDir' => [],
        ]);

        \chmod($this->root->url() . '/readOnlyFile.txt', 0444);
        \chmod($this->root->url() . '/readOnlyDir', 0555);
    }

    public function testWriteToNewFile(): void
    {
        $path = Path::of($this->root->url() . '/newFile.txt');
        $content = 'This is new content';
        $result = FileSystem::write($path, $content);

        $this->assertTrue($result->isOk());
        $this->assertTrue(\file_exists($path->toString()));
        $this->assertSame($content, \file_get_contents($path->toString()));
    }

    public function testWriteToExistingFile(): void
    {
        $path = Path::of($this->root->url() . '/testFile.txt');
        $newContent = 'This is updated content';
        $result = FileSystem::write($path, $newContent);

        $this->assertTrue($result->isOk());
        $this->assertSame($newContent, \file_get_contents($path->toString()));
    }

    public function testWriteWithByteArray(): void
    {
        // Convert byte array to string because FileSystem::write only accepts string
        $path = Path::of($this->root->url() . '/binaryFile.bin');
        $bytes = [0x00, 0x01, 0x02, 0x03, 0xFF, 0xFE];
        $binaryString = \pack('C*', ...$bytes);
        $result = FileSystem::write($path, $binaryString);

        $this->assertTrue($result->isOk());
        $this->assertTrue(\file_exists($path->toString()));

        $rawContent = \file_get_contents($path->toString());
        $this->assertSame(6, \strlen($rawContent));
        $this->assertSame($binaryString, $rawContent);
    }

    public function testWriteToReadOnlyFile(): void
    {
        $path = Path::of($this->root->url() . '/readOnlyFile.txt');
        $newContent = 'Attempt to write to read-only file';
        $result = FileSystem::write($path, $newContent);

        $this->assertTrue($result->isErr());
        $error = $result->unwrapErr();
        $this->assertInstanceOf(WriteFailed::class, $error);

        $this->assertSame('read only content', \file_get_contents($path->toString()));
    }

    public function testWriteToInvalidPath(): void
    {
        $path = Path::of($this->root->url() . '/nonExistentDir/file.txt');
        $content = 'Content for invalid path';
        $result = FileSystem::write($path, $content);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(WriteFailed::class, $result->unwrapErr());
        $this->assertFalse(\file_exists($path->toString()));
    }

    public function testCopy(): void
    {
        $sourcePath = Path::of($this->root->url() . '/testFile.txt');
        $destPath = Path::of($this->root->url() . '/copiedFile.txt');

        $result = FileSystem::copyFile($sourcePath, $destPath);

        $this->assertTrue($result->isOk());
        $this->assertTrue(\file_exists($destPath->toString()));
        $this->assertSame(
            \file_get_contents($sourcePath->toString()),
            \file_get_contents($destPath->toString()),
        );
    }

    public function testCopyOverwritesExistingDestination(): void
    {
        $sourcePath = Path::of($this->root->url() . '/testFile.txt');
        $destPath = Path::of($this->root->url() . '/readOnlyFile.txt');

        // Make destination writable so copy can succeed
        \chmod($destPath->toString(), 0666);

        $result = FileSystem::copyFile($sourcePath, $destPath);

        $this->assertTrue($result->isOk());
        $this->assertSame(
            \file_get_contents($sourcePath->toString()),
            \file_get_contents($destPath->toString()),
        );
    }

    public function testCopyFromNonExistentSource(): void
    {
        $sourcePath = Path::of($this->root->url() . '/nonExistentFile.txt');
        $destPath = Path::of($this->root->url() . '/destination.txt');

        $result = FileSystem::copyFile($sourcePath, $destPath);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(FileNotFound::class, $result->unwrapErr());
        $this->assertFalse(\file_exists($destPath->toString()));
    }

    public function testRename(): void
    {
        $oldPath = Path::of($this->root->url() . '/testFile.txt');
        $newPath = Path::of($this->root->url() . '/renamedFile.txt');
        $initialContent = \file_get_contents($oldPath->toString());

        $result = FileSystem::renameFile($oldPath, $newPath);

        $this->assertTrue($result->isOk());
        $this->assertFalse(\file_exists($oldPath->toString()));
        $this->assertTrue(\file_exists($newPath->toString()));
        $this->assertSame($initialContent, \file_get_contents($newPath->toString()));
    }
}
