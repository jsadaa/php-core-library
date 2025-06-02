<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\FileSystem\Unit;

use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\AlreadyExists;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\FileNotFound;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\FileSystem;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use PHPUnit\Framework\TestCase;

final class FileSystemLinkTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        // Create a real temporary directory for testing actual symlinks
        // vfsStream doesn't fully support symlinks for testing
        $this->tempDir = \sys_get_temp_dir() . '/php-core-library-test-' . \uniqid();
        \mkdir($this->tempDir);
        \file_put_contents($this->tempDir . '/sourceFile.txt', 'source content');
        \mkdir($this->tempDir . '/targetDir');
    }

    protected function tearDown(): void
    {
        // Clean up the temporary directory
        if (\is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    public function testHardLink(): void
    {
        $sourcePath = Path::from($this->tempDir . '/sourceFile.txt');
        $linkPath = Path::from($this->tempDir . '/hardlink.txt');

        $result = FileSystem::hardLink($sourcePath, $linkPath);

        $this->assertTrue($result->isOk());
        $this->assertTrue(\file_exists($linkPath->toString()));
        $this->assertSame('source content', \file_get_contents($linkPath->toString()));

        \file_put_contents($sourcePath->toString(), 'updated content');
        $this->assertSame('updated content', \file_get_contents($linkPath->toString()));
    }

    public function testHardLinkToNonExistentFile(): void
    {
        $sourcePath = Path::from($this->tempDir . '/nonExistentFile.txt');
        $linkPath = Path::from($this->tempDir . '/hardlink.txt');

        $result = FileSystem::hardLink($sourcePath, $linkPath);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(FileNotFound::class, $result->unwrapErr());
    }

    public function testSoftLink(): void
    {
        $sourcePath = Path::from($this->tempDir . '/sourceFile.txt');
        $linkPath = Path::from($this->tempDir . '/softlink.txt');

        $result = FileSystem::symLink($sourcePath, $linkPath);

        $this->assertTrue($result->isOk());
        $this->assertTrue(\is_link($linkPath->toString()));
        $this->assertTrue(\file_exists($linkPath->toString()));
        $this->assertSame('source content', \file_get_contents($linkPath->toString()));

        \file_put_contents($sourcePath->toString(), 'updated through symlink');
        $this->assertSame('updated through symlink', \file_get_contents($linkPath->toString()));
    }

    public function testSoftLinkToNonExistentFile(): void
    {
        $sourcePath = Path::from($this->tempDir . '/nonExistentFile.txt');
        $linkPath = Path::from($this->tempDir . '/softlink.txt');

        $result = FileSystem::symLink($sourcePath, $linkPath);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(FileNotFound::class, $result->unwrapErr());
    }

    public function testSoftLinkWithExistingTarget(): void
    {
        $sourcePath = Path::from($this->tempDir . '/sourceFile.txt');
        $linkPath = Path::from($this->tempDir . '/targetDir/softlink.txt');

        $result = FileSystem::symLink($sourcePath, $linkPath);
        $this->assertTrue($result->isOk());

        // Try to create the same symlink again
        $result = FileSystem::symLink($sourcePath, $linkPath);
        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(AlreadyExists::class, $result->unwrapErr());
    }

    private function removeDirectory(string $dir): void
    {
        if (\is_dir($dir)) {
            $objects = \scandir($dir);

            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    $path = $dir . '/' . $object;

                    if (\is_dir($path)) {
                        $this->removeDirectory($path);
                    } else {
                        \unlink($path);
                    }
                }
            }
            \rmdir($dir);
        }
    }
}
