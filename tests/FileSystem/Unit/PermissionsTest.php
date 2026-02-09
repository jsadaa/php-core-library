<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\FileSystem\Unit;

use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\PermissionDenied;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Permissions;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

final class PermissionsTest extends TestCase
{
    private vfsStreamDirectory $root;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup('root', 0777, [
            'testFile.txt' => 'test content',
            'readOnlyFile.txt' => 'read only content',
            'executableFile.sh' => '#!/bin/bash\necho "hello"',
            'emptyDir' => [],
        ]);

        $this->root->getChild('readOnlyFile.txt')->chmod(0444);
        $this->root->getChild('executableFile.sh')->chmod(0755);

        $this->tempDir = \sys_get_temp_dir() . '/php-core-library-test-' . \uniqid();
        \mkdir($this->tempDir);
        \file_put_contents($this->tempDir . '/testFile.txt', 'test content');
        \file_put_contents($this->tempDir . '/readOnlyFile.txt', 'read only content');
        \file_put_contents($this->tempDir . '/executableFile.sh', '#!/bin/bash\necho "hello"');

        \chmod($this->tempDir . '/readOnlyFile.txt', 0444);
        \chmod($this->tempDir . '/executableFile.sh', 0755);
    }

    protected function tearDown(): void
    {
        if (\is_dir($this->tempDir)) {
            foreach (\glob($this->tempDir . '/*') as $file) {
                if (\is_file($file)) {
                    \chmod($file, 0666); // Ensure we can delete it
                    \unlink($file);
                }
            }
            \rmdir($this->tempDir);
        }
    }

    public function testCreateWithDefaultMode(): void
    {
        $permissions = Permissions::create();
        $this->assertEquals(0644, $permissions->mode());
    }

    public function testCreateWithSpecificMode(): void
    {
        $permissions = Permissions::create(0755);
        $this->assertEquals(0755, $permissions->mode());
    }

    public function testFromPath(): void
    {
        $path = Path::of($this->tempDir . '/testFile.txt');
        $permissions = Permissions::of($path);

        $this->assertTrue($permissions->isReadable());
        $this->assertTrue($permissions->isWritable());

        $readOnlyPath = Path::of($this->tempDir . '/readOnlyFile.txt');
        $readOnlyPermissions = Permissions::of($readOnlyPath);

        $this->assertTrue($readOnlyPermissions->isReadable());
        $this->assertFalse($readOnlyPermissions->isWritable());
    }

    public function testFromStringPath(): void
    {
        $pathString = $this->tempDir . '/testFile.txt';
        $permissions = Permissions::of($pathString);

        $this->assertTrue($permissions->isReadable());
        $this->assertTrue($permissions->isWritable());
    }

    public function testFromNonExistentPath(): void
    {
        $path = Path::of($this->tempDir . '/nonExistent.txt');
        $permissions = Permissions::of($path);

        $this->assertEquals(0, $permissions->mode());
    }

    public function testIsReadable(): void
    {
        // Test readable permissions
        $readablePerms = Permissions::create(0644);
        $this->assertTrue($readablePerms->isReadable());

        $readablePerms2 = Permissions::create(0444);
        $this->assertTrue($readablePerms2->isReadable());

        $nonReadablePerms = Permissions::create(0000);
        $this->assertFalse($nonReadablePerms->isReadable());

        $nonReadablePerms2 = Permissions::create(0200);
        $this->assertFalse($nonReadablePerms2->isReadable());
    }

    public function testIsWritable(): void
    {
        $writablePerms = Permissions::create(0644);
        $this->assertTrue($writablePerms->isWritable());

        $writablePerms2 = Permissions::create(0200);
        $this->assertTrue($writablePerms2->isWritable());

        $nonWritablePerms = Permissions::create(0444);
        $this->assertFalse($nonWritablePerms->isWritable());

        $nonWritablePerms2 = Permissions::create(0000);
        $this->assertFalse($nonWritablePerms2->isWritable());
    }

    public function testIsExecutable(): void
    {
        $executablePerms = Permissions::create(0755);
        $this->assertTrue($executablePerms->isExecutable());

        $executablePerms2 = Permissions::create(0111);
        $this->assertTrue($executablePerms2->isExecutable());

        $nonExecutablePerms = Permissions::create(0644);
        $this->assertFalse($nonExecutablePerms->isExecutable());

        $nonExecutablePerms2 = Permissions::create(0000);
        $this->assertFalse($nonExecutablePerms2->isExecutable());
    }

    public function testSetMode(): void
    {
        $permissions = Permissions::create(0644);
        $this->assertEquals(0644, $permissions->mode());

        $newPermissions = $permissions->setMode(0755);
        $this->assertEquals(0755, $newPermissions->mode());

        $readOnlyPermissions = $permissions->setMode(0444);
        $this->assertEquals(0444, $readOnlyPermissions->mode());
    }

    public function testSetModeAffectsPermissionChecks(): void
    {
        $permissions = Permissions::create(0644);
        $this->assertTrue($permissions->isReadable());
        $this->assertTrue($permissions->isWritable());
        $this->assertFalse($permissions->isExecutable());

        $executablePermissions = $permissions->setMode(0755);
        $this->assertTrue($executablePermissions->isReadable());
        $this->assertTrue($executablePermissions->isWritable());
        $this->assertTrue($executablePermissions->isExecutable());

        $readOnlyPermissions = $permissions->setMode(0444);
        $this->assertTrue($readOnlyPermissions->isReadable());
        $this->assertFalse($readOnlyPermissions->isWritable());
        $this->assertFalse($readOnlyPermissions->isExecutable());
    }

    public function testApplyToExistingFile(): void
    {
        $path = Path::of($this->tempDir . '/testFile.txt');
        $permissions = Permissions::create(0600);

        $result = $permissions->apply($path);
        $this->assertTrue($result->isOk());

        $actualPerms = \fileperms($this->tempDir . '/testFile.txt') & 0777;
        $this->assertEquals(0600, $actualPerms);
    }

    public function testApplyToNonExistentFile(): void
    {
        $path = Path::of($this->tempDir . '/nonExistent.txt');
        $permissions = Permissions::create(0644);

        $result = $permissions->apply($path);
        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(PermissionDenied::class, $result->unwrapErr());
    }

    public function testApplyWithStringPath(): void
    {
        $pathString = $this->tempDir . '/testFile.txt';
        $permissions = Permissions::create(0700);

        $result = $permissions->apply($pathString);
        $this->assertTrue($result->isOk());

        $actualPerms = \fileperms($pathString) & 0777;
        $this->assertEquals(0700, $actualPerms);
    }

    public function testApplyReturnsCorrectPath(): void
    {
        $path = Path::of($this->tempDir . '/testFile.txt');
        $permissions = Permissions::create(0644);

        $result = $permissions->apply($path);
        $this->assertTrue($result->isOk());
    }

    public function testPermissionBitMasks(): void
    {
        $permissions = Permissions::create(0764);

        // Owner: read(4) + write(2) + execute(1) = 7
        $this->assertTrue($permissions->isReadable());
        $this->assertTrue($permissions->isWritable());
        $this->assertTrue($permissions->isExecutable());

        $permissions = Permissions::create(0640);

        // Owner: read(4) + write(2) = 6, Group: read(4) = 4, Others: none = 0
        $this->assertTrue($permissions->isReadable());
        $this->assertTrue($permissions->isWritable());
        $this->assertFalse($permissions->isExecutable());

        $permissions = Permissions::create(0555);

        // All: read(4) + execute(1) = 5
        $this->assertTrue($permissions->isReadable());
        $this->assertFalse($permissions->isWritable());
        $this->assertTrue($permissions->isExecutable());
    }

    public function testEdgeCasePermissions(): void
    {
        // Test no permissions
        $noPerms = Permissions::create(0000);
        $this->assertFalse($noPerms->isReadable());
        $this->assertFalse($noPerms->isWritable());
        $this->assertFalse($noPerms->isExecutable());

        // Test all permissions
        $allPerms = Permissions::create(0777);
        $this->assertTrue($allPerms->isReadable());
        $this->assertTrue($allPerms->isWritable());
        $this->assertTrue($allPerms->isExecutable());

        // Test only execute
        $execOnly = Permissions::create(0111);
        $this->assertFalse($execOnly->isReadable());
        $this->assertFalse($execOnly->isWritable());
        $this->assertTrue($execOnly->isExecutable());

        // Test only write
        $writeOnly = Permissions::create(0222);
        $this->assertFalse($writeOnly->isReadable());
        $this->assertTrue($writeOnly->isWritable());
        $this->assertFalse($writeOnly->isExecutable());

        // Test only read
        $readOnly = Permissions::create(0444);
        $this->assertTrue($readOnly->isReadable());
        $this->assertFalse($readOnly->isWritable());
        $this->assertFalse($readOnly->isExecutable());
    }
}
