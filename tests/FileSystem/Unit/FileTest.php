<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\FileSystem\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\AlreadyExists;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\CreateFailed;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\FileNotFound;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\InvalidFileType;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\PermissionDenied;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\ReadFailed;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\File;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\FileTimes;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Metadata;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Permissions;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use Jsadaa\PhpCoreLibrary\Modules\Time\SystemTime;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

final class FileTest extends TestCase
{
    private vfsStreamDirectory $root;
    private string $testContent = 'This is test content with multiple lines.\nLine 2\nLine 3';
    private string $binaryContent;
    private string $tempDir;
    private string $tempFile;

    protected function setUp(): void
    {
        $this->binaryContent = \pack('C*', 0x00, 0x01, 0x02, 0x03, 0xFF, 0xFE);

        $this->root = vfsStream::setup('root', 0777, [
            'testFile.txt' => $this->testContent,
            'binaryFile.bin' => $this->binaryContent,
            'emptyFile.txt' => '',
            'readOnlyFile.txt' => 'read only content',
            'emptyDir' => [],
            'nestedDir' => [
                'nestedFile.txt' => 'nested content',
            ],
        ]);

        // Make read-only file actually read-only
        $this->root->getChild('readOnlyFile.txt')->chmod(0444);

        // Create real temporary directory and file for operations not supported by vfsStream
        $this->tempDir = \sys_get_temp_dir() . '/php-core-library-test-' . \uniqid();
        \mkdir($this->tempDir);
        $this->tempFile = $this->tempDir . '/testFile.txt';
        \file_put_contents($this->tempFile, $this->testContent);
    }

    protected function tearDown(): void
    {
        if (\file_exists($this->tempFile)) {
            @\chmod($this->tempFile, 0666); // Ensure we can delete it
            \unlink($this->tempFile);
        }

        if (\is_dir($this->tempDir)) {
            foreach (\glob($this->tempDir . '/*') as $file) {
                if (\is_file($file)) {
                    @\chmod($file, 0666);
                    \unlink($file);
                }
            }
            \rmdir($this->tempDir);
        }
    }

    public function testOpen(): void
    {
        $path = Path::from($this->root->url() . '/testFile.txt');
        $result = File::from($path->toString());

        $this->assertTrue($result->isOk());
        $file = $result->unwrap();

        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals($path->toString(), $file->path()->toString());
    }

    public function testOpenNonExistentFile(): void
    {
        $path = Path::from($this->root->url() . '/nonExistent.txt');
        $result = File::from($path->toString());

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(FileNotFound::class, $result->unwrapErr());
    }

    public function testOpenDirectory(): void
    {
        $path = Path::from($this->root->url() . '/emptyDir');
        $result = File::from($path->toString());

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(InvalidFileType::class, $result->unwrapErr());
    }

    public function testCreate(): void
    {
        $path = Path::from($this->root->url() . '/newFile.txt');
        $result = File::new($path->toString());

        $this->assertTrue($result->isOk());
        $file = $result->unwrap();

        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals($path->toString(), $file->path()->toString());
        $this->assertTrue(\file_exists($path->toString()));
    }

    public function testCreateFileInNonExistentDirectory(): void
    {
        $path = Path::from($this->root->url() . '/nonExistentDir/file.txt');
        $result = File::new($path->toString());

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(CreateFailed::class, $result->unwrapErr());
    }

    public function testCreateAlreadyExistingFile(): void
    {
        $path = Path::from($this->root->url() . '/readOnlyFile.txt');
        $result = File::new($path->toString());

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(AlreadyExists::class, $result->unwrapErr());
    }

    public function testCreateNew(): void
    {
        // Use a real file for this test since vfsStream may not fully support r+ mode
        $path = Path::from($this->tempDir . '/newFileRW.txt');
        $result = File::new($path->toString());

        $this->assertTrue($result->isOk());
        $file = $result->unwrap();

        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals($path->toString(), $file->path()->toString());
        $this->assertTrue(\file_exists($path->toString()));

        // Test read+write capability
        $writeResult = $file->write('test data');
        $this->assertTrue($writeResult->isOk());

        $readResult = $file->read();
        $this->assertTrue($readResult->isOk());
        $this->assertEquals('test data', $readResult->unwrap());
    }

    public function testCreateNewInNonExistentDirectory(): void
    {
        $path = Path::from($this->root->url() . '/nonExistentDir/file.txt');
        $result = File::new($path->toString());

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(CreateFailed::class, $result->unwrapErr());
    }

    public function testRead(): void
    {
        $path = Path::from($this->root->url() . '/testFile.txt');
        $file = File::from($path->toString())->unwrap();
        $result = $file->read();

        $this->assertTrue($result->isOk());
        $content = $result->unwrap();
        $this->assertSame($this->testContent, $content->toString());
    }

    public function testReadToStr(): void
    {
        $path = Path::from($this->root->url() . '/testFile.txt');
        $file = File::from($path->toString())->unwrap();
        $result = $file->read();

        $this->assertTrue($result->isOk());
        $content = $result->unwrap();

        $this->assertInstanceOf(Str::class, $content);
        $this->assertSame($this->testContent, $content->toString());
    }

    public function testTake(): void
    {
        $path = Path::from($this->root->url() . '/testFile.txt');
        $file = File::from($path->toString())->unwrap();

        // Take first 4 bytes
        $result = $file->readRange(0, 4);

        $this->assertTrue($result->isOk());
        $content = $result->unwrap();
        $this->assertSame('This', $content->toString());

        // Take next 3 bytes
        $result = $file->readRange(4, 3);
        $this->assertTrue($result->isOk());
        $content = $result->unwrap();
        $this->assertSame(' is', $content->toString());
    }

    public function testBytes(): void
    {
        $path = Path::from($this->root->url() . '/binaryFile.bin');
        $file = File::from($path->toString())->unwrap();
        $result = $file->bytes();

        $this->assertTrue($result->isOk());
        $bytes = $result->unwrap();

        $this->assertInstanceOf(Sequence::class, $bytes);
        $this->assertEquals(6, $bytes->len()->toInt());
        $this->assertEquals(0x00, $bytes->get(0)->unwrap()->toInt());
        $this->assertEquals(0xFF, $bytes->get(4)->unwrap()->toInt());
    }

    public function testSetLen(): void
    {
        // Use real file since vfsStream may not support ftruncate properly
        $path = Path::from($this->tempDir . '/setLenTest.txt');
        $this->assertNotFalse(\file_put_contents($path->toString(), $this->testContent));
        $file = File::from($path->toString())->unwrap(); // open in read write mode

        // Truncate file to 10 bytes
        $result = $file->setLen(10);

        $this->assertTrue($result->isOk());

        $file = $result->unwrap();
        $content = $file->read()->unwrap();
        $this->assertEquals(10, \strlen($content->toString()));
        $this->assertEquals(\substr($this->testContent, 0, 10), $content);
    }

    public function testReadExact(): void
    {
        $path = Path::from($this->root->url() . '/testFile.txt');
        $file = File::from($path->toString())->unwrap();

        // Read exactly 4 bytes
        $result = $file->readExact(0, 4);

        $this->assertTrue($result->isOk());
        $this->assertEquals('This', $result->unwrap());

        // Try to read more bytes than available at current position
        $result = $file->readExact(10, 100);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(ReadFailed::class, $result->unwrapErr());
    }

    public function testReadToEnd(): void
    {
        $path = Path::from($this->root->url() . '/testFile.txt');
        $file = File::from($path->toString())->unwrap();

        $result = $file->readFrom(5);

        $this->assertTrue($result->isOk());
        $this->assertEquals(\substr($this->testContent, 5), $result->unwrap());
    }

    public function testWrite(): void
    {
        $path = Path::from($this->tempDir . '/writeTest.txt');
        $file = File::new($path->toString())->unwrap();

        $writeContent = 'Hello, world!';
        $result = $file->write($writeContent);

        $this->assertTrue($result->isOk());

        $file = $result->unwrap();
        $content = $file->read()->unwrap();
        $this->assertEquals($writeContent, $content);
    }

    public function testWriteToReadOnlyFile(): void
    {
        $path = $this->tempDir . '/readonlyTest.txt';
        \file_put_contents($path, 'initial content');
        \chmod($path, 0444); // Make read-only

        $file = File::from($path)->unwrap();
        $result = $file->write('new content');

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(PermissionDenied::class, $result->unwrapErr());
    }

    public function testMetadata(): void
    {
        $path = Path::from($this->root->url() . '/testFile.txt');
        $file = File::from($path->toString())->unwrap();

        $result = $file->metadata();
        $this->assertTrue($result->isOk());

        $metadata = $result->unwrap();
        $this->assertInstanceOf(Metadata::class, $metadata);
        $this->assertTrue($metadata->isFile());
        $this->assertEquals(\strlen($this->testContent), $metadata->len()->toInt());
    }

    public function testSetPermissions(): void
    {
        $path = Path::from($this->tempFile);
        $file = File::from($path->toString())->unwrap();

        $permissions = Permissions::create(0644);
        $result = $file->setPermissions($permissions);

        $this->assertTrue($result->isOk());

        $filePerms = \fileperms($this->tempFile) & 0777;
        $this->assertEquals(0644, $filePerms);
    }

    public function testSetModified(): void
    {
        // Use real file for timestamp testing
        $path = Path::from($this->tempFile);
        $file = File::from($path->toString())->unwrap();

        // Set modified time to a specific time (2000-01-01)
        $timestamp = \mktime(0, 0, 0, 1, 1, 2000);
        $time = SystemTime::fromDateTimeImmutable(
            (new \DateTimeImmutable())->setTimestamp($timestamp),
        )->unwrap();

        $result = $file->setModified($time);
        $this->assertTrue($result->isOk());

        $modTime = \filemtime($this->tempFile);
        $this->assertEquals($timestamp, $modTime);
    }

    public function testSetTimes(): void
    {
        // Use real file for timestamp testing
        $path = Path::from($this->tempFile);
        $file = File::from($path->toString())->unwrap();

        $modTimestamp = \mktime(0, 0, 0, 1, 1, 2000);
        $accessTimestamp = \mktime(0, 0, 0, 1, 2, 2000);

        $modTime = SystemTime::fromDateTimeImmutable(
            (new \DateTimeImmutable())->setTimestamp($modTimestamp),
        )->unwrap();
        $accessTime = SystemTime::fromDateTimeImmutable(
            (new \DateTimeImmutable())->setTimestamp($accessTimestamp),
        )->unwrap();

        $times = FileTimes::new()
            ->setModified($modTime)
            ->setAccessed($accessTime);

        $result = $file->setTimes($times);

        $this->assertTrue($result->isOk());

        $actualModTime = \filemtime($this->tempFile);
        $actualAccessTime = \fileatime($this->tempFile);

        $this->assertEquals($modTimestamp, $actualModTime);
        $this->assertEquals($accessTimestamp, $actualAccessTime);
    }

    public function testAppend(): void
    {
        $path = Path::from($this->tempDir . '/appendTest.txt');
        $file = File::new($path->toString())->unwrap();

        $initialContent = 'Initial content';
        $file->write($initialContent)->unwrap();

        $appendedContent = '\nAppended content';
        $result = $file->append($appendedContent);

        $this->assertTrue($result->isOk());

        $file = $result->unwrap();
        $finalContent = $file->read()->unwrap();
        $this->assertEquals($initialContent . $appendedContent, $finalContent->toString());
    }

    public function testAppendToReadOnlyFile(): void
    {
        $path = $this->tempDir . '/readonlyAppendTest.txt';
        \file_put_contents($path, 'initial content');
        \chmod($path, 0444); // Make read-only

        $file = File::from($path)->unwrap();
        $result = $file->append('new content');

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(PermissionDenied::class, $result->unwrapErr());
    }

    public function testWriteAtomic(): void
    {
        $path = Path::from($this->tempDir . '/atomicTest.txt');
        $file = File::new($path->toString())->unwrap();

        $content = 'Atomic write content';
        $result = $file->writeAtomic($content);

        $this->assertTrue($result->isOk());

        $file = $result->unwrap();
        $readContent = $file->read()->unwrap();
        $this->assertEquals($content, $readContent->toString());
    }

    public function testWriteAtomicWithSync(): void
    {
        $path = Path::from($this->tempDir . '/atomicSyncTest.txt');
        $file = File::new($path->toString())->unwrap();

        $content = 'Atomic write with sync content';
        $result = $file->writeAtomic($content, true);

        $this->assertTrue($result->isOk());

        $file = $result->unwrap();
        $readContent = $file->read()->unwrap();
        $this->assertEquals($content, $readContent->toString());
    }

    public function testWriteAtomicToReadOnlyFile(): void
    {
        $path = $this->tempDir . '/readonlyAtomicTest.txt';
        \file_put_contents($path, 'initial content');
        \chmod($path, 0444); // Make read-only

        $file = File::from($path)->unwrap();
        $result = $file->writeAtomic('new content');

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(PermissionDenied::class, $result->unwrapErr());
    }

    public function testSize(): void
    {
        $path = Path::from($this->root->url() . '/testFile.txt');
        $file = File::from($path->toString())->unwrap();

        $result = $file->size();

        $this->assertTrue($result->isOk());
        $size = $result->unwrap();
        $this->assertEquals(\strlen($this->testContent), $size->toInt());
    }

    public function testSizeEmptyFile(): void
    {
        $path = Path::from($this->root->url() . '/emptyFile.txt');
        $file = File::from($path->toString())->unwrap();

        $result = $file->size();

        $this->assertTrue($result->isOk());
        $size = $result->unwrap();
        $this->assertEquals(0, $size->toInt());
    }

    public function testSizeBinaryFile(): void
    {
        $path = Path::from($this->root->url() . '/binaryFile.bin');
        $file = File::from($path->toString())->unwrap();

        $result = $file->size();

        $this->assertTrue($result->isOk());
        $size = $result->unwrap();
        $this->assertEquals(6, $size->toInt()); // Binary content length
    }
}
