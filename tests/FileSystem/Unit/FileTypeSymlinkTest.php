<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\FileSystem\Unit;

use Jsadaa\PhpCoreLibrary\Modules\FileSystem\FileType;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

final class FileTypeSymlinkTest extends TestCase
{
    private vfsStreamDirectory $root;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup('root', null, [
            'testFile.txt' => 'test content',
            'emptyDir' => [],
        ]);

        // vfsStream doesn't properly support symlinks
        $this->tempDir = \sys_get_temp_dir() . '/php-core-library-test-' . \uniqid();
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
        // Clean up the temporary directory
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

    public function testDetectFileType(): void
    {
        $filePath = Path::from($this->root->url() . '/testFile.txt');
        $fileType = FileType::from($filePath);

        $this->assertTrue($fileType->isFile());
        $this->assertFalse($fileType->isDir());
        $this->assertFalse($fileType->isSymLink());
    }

    public function testDetectDirectoryType(): void
    {
        $dirPath = Path::from($this->root->url() . '/emptyDir');
        $fileType = FileType::from($dirPath);

        $this->assertTrue($fileType->isDir());
        $this->assertFalse($fileType->isFile());
        $this->assertFalse($fileType->isSymLink());
    }

    public function testDetectSymlinkType(): void
    {
        $linkPath = Path::from($this->tempDir . '/testLink');
        $fileType = FileType::from($linkPath);

        $this->assertTrue($fileType->isSymLink());
        $this->assertFalse($fileType->isFile());
        $this->assertFalse($fileType->isDir());
    }

    public function testFileTypeFactoryMethods(): void
    {
        $filePath = Path::from($this->root->url() . '/testFile.txt');
        $this->assertTrue(FileType::from($filePath)->isFile());

        $dirPath = Path::from($this->root->url() . '/emptyDir');
        $this->assertTrue(FileType::from($dirPath)->isDir());

        $linkPath = Path::from($this->tempDir . '/testLink');
        $this->assertTrue(FileType::from($linkPath)->isSymLink());
    }
}
