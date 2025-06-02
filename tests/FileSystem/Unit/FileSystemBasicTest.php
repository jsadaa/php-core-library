<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\FileSystem\Unit;

use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\FileNotFound;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\FileSystem;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Metadata;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

final class FileSystemBasicTest extends TestCase
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
        $this->tempDir = '/private' . \sys_get_temp_dir() . '/php-core-library-test-' . \uniqid();
        \mkdir($this->tempDir);
        \file_put_contents($this->tempDir . '/testFile.txt', 'test content');
        \mkdir($this->tempDir . '/emptyDir');

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

    public function testCanonicalizeValidPath(): void
    {
        \mkdir($this->tempDir . '/nestedDir', 0777, true);

        $path = Path::from($this->tempDir . '/nestedDir/../testFile.txt');
        $result = FileSystem::canonicalize($path);

        $this->assertTrue($result->isOk(), 'Canonicalization should succeed');
        $canonicalPath = $result->unwrap();

        $this->assertStringNotContainsString('..', $canonicalPath->toString());
        $this->assertEquals($this->tempDir . '/testFile.txt', $canonicalPath->toString());
    }

    public function testMetadataForFile(): void
    {
        $path = Path::from($this->root->url() . '/testFile.txt');
        $result = FileSystem::metadata($path);

        $this->assertTrue($result->isOk());
        $metadata = $result->unwrap();

        $this->assertInstanceOf(Metadata::class, $metadata);
        $this->assertEquals(12, $metadata->len()->toInt());
        $this->assertTrue($metadata->isFile());
        $this->assertFalse($metadata->isDir());
    }

    public function testMetadataForDirectory(): void
    {
        $path = Path::from($this->root->url() . '/emptyDir');
        $result = FileSystem::metadata($path);

        $this->assertTrue($result->isOk());
        $metadata = $result->unwrap();

        $this->assertInstanceOf(Metadata::class, $metadata);
        $this->assertTrue($metadata->isDir());
        $this->assertFalse($metadata->isFile());
    }

    public function testMetadataForNonExistentPath(): void
    {
        $path = Path::from($this->root->url() . '/nonExistent.txt');
        $result = FileSystem::metadata($path);

        $this->assertTrue($result->isErr());
        $error = $result->unwrapErr();
        $this->assertInstanceOf(FileNotFound::class, $error);
        $this->assertStringContainsString('Path does not exist', $error->getMessage());
    }

    public function testSymlinkMetadata(): void
    {
        $path = Path::from($this->tempDir . '/testLink');
        $result = FileSystem::symlinkMetadata($path);

        $this->assertTrue($result->isOk());
        $metadata = $result->unwrap();

        $this->assertInstanceOf(Metadata::class, $metadata);
        $this->assertTrue($metadata->fileType()->isSymLink());
        $this->assertFalse($metadata->fileType()->isFile());
        $this->assertFalse($metadata->fileType()->isDir());
    }

    public function testExistsForExistingPath(): void
    {
        $path = Path::from($this->root->url() . '/testFile.txt');
        $this->assertTrue(FileSystem::exists($path));

        $path = Path::from($this->root->url() . '/emptyDir');
        $this->assertTrue(FileSystem::exists($path));
    }

    public function testExistsForNonExistingPath(): void
    {
        $path = Path::from($this->root->url() . '/nonExistent.txt');
        $this->assertFalse(FileSystem::exists($path));
    }
}
