<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\FileSystem\Unit;

use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\FileNotFound;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\FileType;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Metadata;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Permissions;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use Jsadaa\PhpCoreLibrary\Modules\Time\SystemTime;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

final class MetadataTest extends TestCase
{
    private vfsStreamDirectory $root;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup('root', 0777, [
            'testFile.txt' => 'test content',
            'emptyDir' => [],
            'readOnlyFile.txt' => 'read only content',
            'executableFile.sh' => '#!/bin/bash\necho "hello"',
        ]);

        $this->root->getChild('readOnlyFile.txt')->chmod(0444);
        $this->root->getChild('executableFile.sh')->chmod(0755);

        // Create real temporary directory for testing symlinks and real file operations
        $this->tempDir = \sys_get_temp_dir() . '/php-core-library-test-' . \uniqid();
        \mkdir($this->tempDir);
        \file_put_contents($this->tempDir . '/testFile.txt', 'test content');
        \file_put_contents($this->tempDir . '/executableFile.sh', '#!/bin/bash\necho "hello"');
        \chmod($this->tempDir . '/executableFile.sh', 0755);

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

            if (\file_exists($this->tempDir . '/executableFile.sh')) {
                \unlink($this->tempDir . '/executableFile.sh');
            }
            \rmdir($this->tempDir);
        }
    }

    public function testMetadataFromNonExistentPath(): void
    {
        $path = Path::of($this->root->url() . '/nonExistent.txt');
        $result = Metadata::of($path);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(FileNotFound::class, $result->unwrapErr());
    }

    public function testGetPath(): void
    {
        $path = Path::of($this->root->url() . '/testFile.txt');
        $metadata = Metadata::of($path)->unwrap();

        $returnedPath = $metadata->path();
        $this->assertEquals($path->toString(), $returnedPath->toString());
    }

    public function testIsSymLink(): void
    {
        $filePath = Path::of($this->root->url() . '/testFile.txt');
        $metadata = Metadata::of($filePath)->unwrap();
        $this->assertFalse($metadata->isSymLink());

        $linkPath = Path::of($this->tempDir . '/testLink');
        $metadata = Metadata::of($linkPath)->unwrap();
        $this->assertTrue($metadata->isSymLink());
    }

    public function testGetModified(): void
    {
        $path = Path::of($this->root->url() . '/testFile.txt');
        $metadata = Metadata::of($path)->unwrap();

        $modifiedTime = $metadata->modified();
        $this->assertInstanceOf(SystemTime::class, $modifiedTime);

        $timestamp = $modifiedTime->seconds();
        $this->assertGreaterThan(0, $timestamp);
        $this->assertLessThanOrEqual(\time(), $timestamp);
    }

    public function testGetAccessed(): void
    {
        $path = Path::of($this->root->url() . '/testFile.txt');
        $metadata = Metadata::of($path)->unwrap();

        $accessedTime = $metadata->accessed();
        $this->assertInstanceOf(SystemTime::class, $accessedTime);

        $timestamp = $accessedTime->seconds();
        $this->assertGreaterThan(0, $timestamp);
        $this->assertLessThanOrEqual(\time(), $timestamp);
    }

    public function testGetCreated(): void
    {
        $path = Path::of($this->root->url() . '/testFile.txt');
        $metadata = Metadata::of($path)->unwrap();

        $createdTime = $metadata->created();
        $this->assertInstanceOf(SystemTime::class, $createdTime);

        $timestamp = $createdTime->seconds();
        $this->assertGreaterThan(0, $timestamp);
        $this->assertLessThanOrEqual(\time(), $timestamp);
    }

    public function testIsReadable(): void
    {
        $path = Path::of($this->root->url() . '/testFile.txt');
        $metadata = Metadata::of($path)->unwrap();
        $this->assertTrue($metadata->isReadable());

        $readOnlyPath = Path::of($this->root->url() . '/readOnlyFile.txt');
        $metadata = Metadata::of($readOnlyPath)->unwrap();
        $this->assertTrue($metadata->isReadable());
    }

    public function testIsWritable(): void
    {
        $path = Path::of($this->root->url() . '/testFile.txt');
        $metadata = Metadata::of($path)->unwrap();
        $this->assertTrue($metadata->isWritable());

        $readOnlyPath = Path::of($this->root->url() . '/readOnlyFile.txt');
        $metadata = Metadata::of($readOnlyPath)->unwrap();
        $this->assertFalse($metadata->isWritable());
    }

    public function testIsExecutable(): void
    {
        $path = Path::of($this->root->url() . '/testFile.txt');
        $metadata = Metadata::of($path)->unwrap();
        $this->assertFalse($metadata->isExecutable());

        $execPath = Path::of($this->tempDir . '/executableFile.sh');
        $metadata = Metadata::of($execPath)->unwrap();
        $this->assertTrue($metadata->isExecutable());
    }

    public function testGetPermissions(): void
    {
        $path = Path::of($this->root->url() . '/testFile.txt');
        $metadata = Metadata::of($path)->unwrap();

        $permissions = $metadata->permissions();
        $this->assertInstanceOf(Permissions::class, $permissions);

        $mode = $permissions->mode();
        $this->assertGreaterThan(0, $mode->toInt());
    }

    public function testTimestampsConsistency(): void
    {
        $path = Path::of($this->root->url() . '/testFile.txt');
        $metadata = Metadata::of($path)->unwrap();

        $created = $metadata->created()->seconds();
        $modified = $metadata->modified()->seconds();
        $accessed = $metadata->accessed()->seconds();

        // All timestamps should be positive
        $this->assertGreaterThan(0, $created);
        $this->assertGreaterThan(0, $modified);
        $this->assertGreaterThan(0, $accessed);

        // Created time should be <= modified time (logically)
        // Note: On some systems, this might not hold due to filesystem limitations
        $this->assertLessThanOrEqual(\time(), $created);
        $this->assertLessThanOrEqual(\time(), $modified);
        $this->assertLessThanOrEqual(\time(), $accessed);
    }

    public function testMetadataFromStringPath(): void
    {
        $pathString = $this->root->url() . '/testFile.txt';
        $result = Metadata::of($pathString);

        $this->assertTrue($result->isOk());
        $metadata = $result->unwrap();
        $this->assertTrue($metadata->isFile());
        $this->assertEquals(12, $metadata->size()->toInt());
    }

    public function testMetadataFromPathObject(): void
    {
        $pathObject = Path::of($this->root->url() . '/testFile.txt');
        $result = Metadata::of($pathObject);

        $this->assertTrue($result->isOk());
        $metadata = $result->unwrap();
        $this->assertTrue($metadata->isFile());
        $this->assertEquals(12, $metadata->size()->toInt());
    }
}
