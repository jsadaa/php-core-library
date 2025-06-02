<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\FileSystem\Unit;

use Jsadaa\PhpCoreLibrary\Modules\FileSystem\DirectoryEntry;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

final class DirectoryEntryTest extends TestCase
{
    private vfsStreamDirectory $root;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup('root', null, [
            'testFile.txt' => 'test content',
            'emptyDir' => [],
            'nestedDir' => [
                'nestedFile.txt' => 'nested content',
            ],
        ]);

        // Create a real temporary directory for testing symlinks
        $this->tempDir = \sys_get_temp_dir() . '/php-core-library-test-' . \uniqid();
        \mkdir($this->tempDir);
        \file_put_contents($this->tempDir . '/testFile.txt', 'test content');
        \mkdir($this->tempDir . '/emptyDir');
        \mkdir($this->tempDir . '/emptyDir/');

        // Create a symlink in the real filesystem
        $linkPath = $this->tempDir . '/testLink';
        $targetPath = $this->tempDir . '/testFile.txt';
        \symlink($targetPath, $linkPath);
    }

    protected function tearDown(): void
    {
        if (\is_dir($this->tempDir)) {
            if (\file_exists($this->tempDir . '/testLink')) {
                \unlink($this->tempDir . '/testLink');
            }

            if (\file_exists($this->tempDir . '/testFile.txt')) {
                \unlink($this->tempDir . '/testFile.txt');
            }

            if (\is_dir($this->tempDir . '/emptyDir')) {
                \rmdir($this->tempDir . '/emptyDir');
            }
            \rmdir($this->tempDir);
        }
    }

    public function testGetPathFromDirectoryEntry(): void
    {
        $path = Path::from($this->root->url() . '/testFile.txt');
        $entry = DirectoryEntry::from($path);
        $returnedPath = $entry->path();

        $this->assertSame($path->toString(), $returnedPath->toString());
    }

    public function testGetFileNameFromDirectoryEntry(): void
    {
        $path = Path::from($this->root->url() . '/testFile.txt');
        $entry = DirectoryEntry::from($path);
        $fileName = $entry->fileName();

        $this->assertTrue($fileName->isSome());
        $this->assertSame('testFile.txt', $fileName->unwrap()->toString());

        // Test with a directory without trailing slash
        $path = Path::from($this->root->url() . '/emptyDir');
        $entry = DirectoryEntry::from($path);
        $fileName = $entry->fileName();

        $this->assertTrue($fileName->isSome());
        $this->assertSame('emptyDir', $fileName->unwrap()->toString());
    }

    public function testGetFileTypeFromDirectoryEntry(): void
    {
        $path = Path::from($this->root->url() . '/testFile.txt');
        $entry = DirectoryEntry::from($path);
        $fileType = $entry->fileType();

        $this->assertTrue($fileType->isFile());
        $this->assertFalse($fileType->isDir());
        $this->assertFalse($fileType->isSymLink());

        $path = Path::from($this->root->url() . '/emptyDir');
        $entry = DirectoryEntry::from($path);
        $fileType = $entry->fileType();

        $this->assertTrue($fileType->isDir());
        $this->assertFalse($fileType->isFile());
        $this->assertFalse($fileType->isSymLink());

        $path = Path::from($this->tempDir . '/testLink');
        $entry = DirectoryEntry::from($path);
        $fileType = $entry->fileType();

        $this->assertTrue($fileType->isSymLink());
        $this->assertFalse($fileType->isFile());
        $this->assertFalse($fileType->isDir());
    }

    public function testGetMetadataFromDirectoryEntry(): void
    {
        $path = Path::from($this->root->url() . '/testFile.txt');
        $entry = DirectoryEntry::from($path);
        $result = $entry->metadata();

        $this->assertTrue($result->isOk());
        $metadata = $result->unwrap();

        $this->assertTrue($metadata->isFile());
        $this->assertFalse($metadata->isDir());
        $this->assertEquals(12, $metadata->len()->toInt());

        $path = Path::from($this->root->url() . '/emptyDir');
        $entry = DirectoryEntry::from($path);
        $result = $entry->metadata();

        $this->assertTrue($result->isOk());
        $metadata = $result->unwrap();

        $this->assertTrue($metadata->isDir());
        $this->assertFalse($metadata->isFile());

        $path = Path::from($this->tempDir . '/testLink');
        $entry = DirectoryEntry::from($path);
        $result = $entry->metadata();

        $this->assertTrue($result->isOk());
        $metadata = $result->unwrap();

        $this->assertTrue($metadata->isSymLink());
        $this->assertFalse($metadata->isFile());
        $this->assertFalse($metadata->isDir());
    }
}
