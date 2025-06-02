<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\FileSystem\Unit;

use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\PermissionDenied;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\FileSystem;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Permissions;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use PHPUnit\Framework\TestCase;

final class FileSystemPermissionsTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        // Create a real temporary directory for testing permissions
        $this->tempDir = \sys_get_temp_dir() . '/php-core-library-test-' . \uniqid();
        \mkdir($this->tempDir);
        \file_put_contents($this->tempDir . '/testFile.txt', 'test content');
        \mkdir($this->tempDir . '/testDir');
    }

    protected function tearDown(): void
    {
        if (\is_dir($this->tempDir)) {
            if (\file_exists($this->tempDir . '/testFile.txt')) {
                \chmod($this->tempDir . '/testFile.txt', 0777);
            }

            if (\is_dir($this->tempDir . '/testDir')) {
                \chmod($this->tempDir . '/testDir', 0777);
            }

            if (\file_exists($this->tempDir . '/testFile.txt')) {
                \unlink($this->tempDir . '/testFile.txt');
            }

            if (\is_dir($this->tempDir . '/testDir')) {
                \rmdir($this->tempDir . '/testDir');
            }
            \rmdir($this->tempDir);
        }
    }

    public function testSetPermissionsOnFile(): void
    {
        $path = Path::from($this->tempDir . '/testFile.txt');

        // Set read-only permissions (0444)
        $permissions = Permissions::create(0444);
        $result = FileSystem::setPermissions($path, $permissions);

        $this->assertTrue($result->isOk());

        $writeResult = \file_put_contents($path->toString(), 'new content');
        $this->assertFalse($writeResult);

        // Change permissions back to allow deletion in tearDown
        \chmod($path->toString(), 0777);
    }

    public function testSetPermissionsOnDirectory(): void
    {
        $path = Path::from($this->tempDir . '/testDir');

        // Set read-only permissions on directory (0555)
        $permissions = Permissions::create(0555);
        $result = FileSystem::setPermissions($path, $permissions);

        $this->assertTrue($result->isOk());

        $newFilePath = $this->tempDir . '/testDir/newFile.txt';
        $writeResult = @\file_put_contents($newFilePath, 'new content');
        $this->assertFalse($writeResult);

        // Change permissions back to allow deletion in tearDown
        \chmod($path->toString(), 0777);
    }

    public function testSetPermissionsOnNonExistentFile(): void
    {
        $path = Path::from($this->tempDir . '/nonExistentFile.txt');
        $permissions = Permissions::create(0644);
        $result = FileSystem::setPermissions($path, $permissions);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(PermissionDenied::class, $result->unwrapErr());
    }

    public function testPermissionsGetMode(): void
    {
        $mode = 0755;
        $permissions = Permissions::create($mode);

        $this->assertSame($mode, $permissions->mode()->toInt());
    }

    public function testPermissionsFromFileMetadata(): void
    {
        $path = Path::from($this->tempDir . '/testFile.txt');

        // Set specific permissions
        \chmod($path->toString(), 0644);

        $metadataResult = FileSystem::metadata($path);
        $this->assertTrue($metadataResult->isOk());

        $metadata = $metadataResult->unwrap();
        $permissions = $metadata->permissions();

        // On some systems the file might get additional bits set, so we use bitmask comparison
        $this->assertSame(0644, $permissions->mode()->toInt() & 0777);
    }
}
