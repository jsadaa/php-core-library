<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\FileSystem\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\AlreadyExists;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\CreateFailed;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\FileNotFound;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\InvalidFileType;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\File;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\FileTimes;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Metadata;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Permissions;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use Jsadaa\PhpCoreLibrary\Modules\Time\SystemTime;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use Jsadaa\PhpCoreLibrary\Primitives\Unit;
use PHPUnit\Framework\TestCase;

final class FileTest extends TestCase
{
    private string $tempDir;
    private string $tempFile;
    private string $testContent = "Line 1\nLine 2\nLine 3\n";
    private string $binaryContent;

    protected function setUp(): void
    {
        $this->binaryContent = \pack('C*', 0x00, 0x01, 0x02, 0x03, 0xFF, 0xFE);

        $this->tempDir = \sys_get_temp_dir() . '/php-core-library-test-' . \uniqid();
        \mkdir($this->tempDir);

        $this->tempFile = $this->tempDir . '/testFile.txt';
        \file_put_contents($this->tempFile, $this->testContent);

        \file_put_contents($this->tempDir . '/binaryFile.bin', $this->binaryContent);
        \file_put_contents($this->tempDir . '/emptyFile.txt', '');
        \mkdir($this->tempDir . '/emptyDir');
    }

    protected function tearDown(): void
    {
        $this->cleanupDir($this->tempDir);
    }

    // --- Factories ---

    public function testOpen(): void
    {
        $result = File::open($this->tempFile);

        $this->assertTrue($result->isOk());
        $file = $result->unwrap();
        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals($this->tempFile, $file->path()->toString());
        $file->close();
    }

    public function testOpenWithPath(): void
    {
        $path = Path::of($this->tempFile);
        $result = File::open($path);

        $this->assertTrue($result->isOk());
        $file = $result->unwrap();
        $this->assertEquals($this->tempFile, $file->path()->toString());
        $file->close();
    }

    public function testOpenNonExistentFile(): void
    {
        $result = File::open($this->tempDir . '/nonExistent.txt');

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(FileNotFound::class, $result->unwrapErr());
    }

    public function testOpenDirectory(): void
    {
        $result = File::open($this->tempDir . '/emptyDir');

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(InvalidFileType::class, $result->unwrapErr());
    }

    public function testCreate(): void
    {
        $path = $this->tempDir . '/newFile.txt';
        $result = File::create($path);

        $this->assertTrue($result->isOk());
        $file = $result->unwrap();
        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals($path, $file->path()->toString());
        $this->assertTrue(\file_exists($path));
        $file->close();
    }

    public function testCreateAlreadyExistingFile(): void
    {
        $result = File::create($this->tempFile);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(AlreadyExists::class, $result->unwrapErr());
    }

    public function testCreateInNonExistentDirectory(): void
    {
        $result = File::create($this->tempDir . '/nonExistentDir/file.txt');

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(CreateFailed::class, $result->unwrapErr());
    }

    // --- Reading ---

    public function testReadAll(): void
    {
        $file = File::open($this->tempFile)->unwrap();
        $result = $file->readAll();

        $this->assertTrue($result->isOk());
        $content = $result->unwrap();
        $this->assertInstanceOf(Str::class, $content);
        $this->assertSame($this->testContent, $content->toString());
        $file->close();
    }

    public function testReadAllEmptyFile(): void
    {
        $file = File::open($this->tempDir . '/emptyFile.txt')->unwrap();
        $result = $file->readAll();

        $this->assertTrue($result->isOk());
        $this->assertSame('', $result->unwrap()->toString());
        $file->close();
    }

    public function testReadLine(): void
    {
        $file = File::open($this->tempFile)->unwrap();

        // First line
        $result = $file->readLine();
        $this->assertTrue($result->isOk());
        $line = $result->unwrap();
        $this->assertTrue($line->isSome());
        $this->assertSame("Line 1\n", $line->unwrap()->toString());

        // Second line
        $result = $file->readLine();
        $this->assertTrue($result->isOk());
        $line = $result->unwrap();
        $this->assertTrue($line->isSome());
        $this->assertSame("Line 2\n", $line->unwrap()->toString());

        // Third line
        $result = $file->readLine();
        $this->assertTrue($result->isOk());
        $line = $result->unwrap();
        $this->assertTrue($line->isSome());
        $this->assertSame("Line 3\n", $line->unwrap()->toString());

        // EOF -> None
        $result = $file->readLine();
        $this->assertTrue($result->isOk());
        $this->assertTrue($result->unwrap()->isNone());

        $file->close();
    }

    public function testReadChunk(): void
    {
        $file = File::open($this->tempFile)->unwrap();

        // Read first 6 bytes: "Line 1"
        $result = $file->readChunk(6);
        $this->assertTrue($result->isOk());
        $this->assertSame('Line 1', $result->unwrap()->toString());

        // Read next 1 byte: "\n"
        $result = $file->readChunk(1);
        $this->assertTrue($result->isOk());
        $this->assertSame("\n", $result->unwrap()->toString());

        $file->close();
    }

    public function testReadChunkWithInteger(): void
    {
        $file = File::open($this->tempFile)->unwrap();

        $result = $file->readChunk(Integer::of(4));
        $this->assertTrue($result->isOk());
        $this->assertSame('Line', $result->unwrap()->toString());

        $file->close();
    }

    public function testBytes(): void
    {
        $file = File::open($this->tempDir . '/binaryFile.bin')->unwrap();
        $result = $file->bytes();

        $this->assertTrue($result->isOk());
        $bytes = $result->unwrap();

        $this->assertInstanceOf(Sequence::class, $bytes);
        $this->assertEquals(6, $bytes->size()->toInt());
        $this->assertEquals(0x00, $bytes->get(0)->unwrap());
        $this->assertEquals(0x01, $bytes->get(1)->unwrap());
        $this->assertEquals(0xFF, $bytes->get(4)->unwrap());
        $this->assertEquals(0xFE, $bytes->get(5)->unwrap());

        $file->close();
    }

    public function testBytesEmptyFile(): void
    {
        $file = File::open($this->tempDir . '/emptyFile.txt')->unwrap();
        $result = $file->bytes();

        $this->assertTrue($result->isOk());
        $this->assertEquals(0, $result->unwrap()->size()->toInt());

        $file->close();
    }

    // --- Writing ---

    public function testWrite(): void
    {
        $path = $this->tempDir . '/writeTest.txt';
        $file = File::create($path)->unwrap();

        $result = $file->write('Hello, world!');

        $this->assertTrue($result->isOk());
        $bytesWritten = $result->unwrap();
        $this->assertInstanceOf(Integer::class, $bytesWritten);
        $this->assertEquals(13, $bytesWritten->toInt());

        // Verify content via readAll
        $content = $file->readAll()->unwrap();
        $this->assertSame('Hello, world!', $content->toString());

        $file->close();
    }

    public function testWriteWithStr(): void
    {
        $path = $this->tempDir . '/writeStrTest.txt';
        $file = File::create($path)->unwrap();

        $result = $file->write(Str::of('typed content'));

        $this->assertTrue($result->isOk());
        $this->assertEquals(13, $result->unwrap()->toInt());

        $file->close();
    }

    public function testAppend(): void
    {
        $file = File::open($this->tempFile)->unwrap();

        $result = $file->append('Appended line');

        $this->assertTrue($result->isOk());
        $bytesWritten = $result->unwrap();
        $this->assertInstanceOf(Integer::class, $bytesWritten);
        $this->assertEquals(13, $bytesWritten->toInt());

        // Verify content
        $content = $file->readAll()->unwrap();
        $this->assertSame($this->testContent . 'Appended line', $content->toString());

        $file->close();
    }

    public function testWriteAtomic(): void
    {
        $path = $this->tempDir . '/atomicTest.txt';
        $file = File::create($path)->unwrap();

        $content = 'Atomic write content';
        $result = $file->writeAtomic($content);

        $this->assertTrue($result->isOk());
        $this->assertInstanceOf(Unit::class, $result->unwrap());

        // Verify content via readAll (handle has been reopened)
        $readContent = $file->readAll()->unwrap();
        $this->assertSame($content, $readContent->toString());

        $file->close();
    }

    public function testWriteAtomicWithSync(): void
    {
        $path = $this->tempDir . '/atomicSyncTest.txt';
        $file = File::create($path)->unwrap();

        $content = 'Atomic write with sync';
        $result = $file->writeAtomic($content, true);

        $this->assertTrue($result->isOk());

        $readContent = $file->readAll()->unwrap();
        $this->assertSame($content, $readContent->toString());

        $file->close();
    }

    public function testFlush(): void
    {
        $path = $this->tempDir . '/flushTest.txt';
        $file = File::create($path)->unwrap();

        $file->write('data to flush')->unwrap();
        $result = $file->flush();

        $this->assertTrue($result->isOk());
        $this->assertInstanceOf(Unit::class, $result->unwrap());

        $file->close();
    }

    // --- Navigation ---

    public function testSeek(): void
    {
        $file = File::open($this->tempFile)->unwrap();

        // Seek to offset 7 ("Line 2\n...")
        $result = $file->seek(7);
        $this->assertTrue($result->isOk());

        // Read a chunk from that position
        $chunk = $file->readChunk(6)->unwrap();
        $this->assertSame('Line 2', $chunk->toString());

        $file->close();
    }

    public function testSeekWithInteger(): void
    {
        $file = File::open($this->tempFile)->unwrap();

        $result = $file->seek(Integer::of(7));
        $this->assertTrue($result->isOk());

        $chunk = $file->readChunk(6)->unwrap();
        $this->assertSame('Line 2', $chunk->toString());

        $file->close();
    }

    public function testRewind(): void
    {
        $file = File::open($this->tempFile)->unwrap();

        // Read some data to advance position
        $file->readChunk(10)->unwrap();

        // Rewind
        $result = $file->rewind();
        $this->assertTrue($result->isOk());

        // Read from beginning
        $chunk = $file->readChunk(6)->unwrap();
        $this->assertSame('Line 1', $chunk->toString());

        $file->close();
    }

    // --- Metadata ---

    public function testPath(): void
    {
        $file = File::open($this->tempFile)->unwrap();

        $this->assertEquals($this->tempFile, $file->path()->toString());

        $file->close();
    }

    public function testMetadata(): void
    {
        $file = File::open($this->tempFile)->unwrap();
        $result = $file->metadata();

        $this->assertTrue($result->isOk());
        $metadata = $result->unwrap();
        $this->assertInstanceOf(Metadata::class, $metadata);
        $this->assertTrue($metadata->isFile());
        $this->assertEquals(\strlen($this->testContent), $metadata->size()->toInt());

        $file->close();
    }

    public function testSize(): void
    {
        $file = File::open($this->tempFile)->unwrap();
        $result = $file->size();

        $this->assertTrue($result->isOk());
        $this->assertEquals(\strlen($this->testContent), $result->unwrap()->toInt());

        $file->close();
    }

    public function testSizeEmptyFile(): void
    {
        $file = File::open($this->tempDir . '/emptyFile.txt')->unwrap();
        $result = $file->size();

        $this->assertTrue($result->isOk());
        $this->assertEquals(0, $result->unwrap()->toInt());

        $file->close();
    }

    public function testSizeBinaryFile(): void
    {
        $file = File::open($this->tempDir . '/binaryFile.bin')->unwrap();
        $result = $file->size();

        $this->assertTrue($result->isOk());
        $this->assertEquals(6, $result->unwrap()->toInt());

        $file->close();
    }

    public function testSetPermissions(): void
    {
        $file = File::open($this->tempFile)->unwrap();

        $permissions = Permissions::create(0644);
        $result = $file->setPermissions($permissions);

        $this->assertTrue($result->isOk());

        $filePerms = \fileperms($this->tempFile) & 0777;
        $this->assertEquals(0644, $filePerms);

        $file->close();
    }

    public function testSetModified(): void
    {
        $file = File::open($this->tempFile)->unwrap();

        $timestamp = \mktime(0, 0, 0, 1, 1, 2000);
        $time = SystemTime::fromDateTimeImmutable(
            (new \DateTimeImmutable())->setTimestamp($timestamp),
        )->unwrap();

        $result = $file->setModified($time);
        $this->assertTrue($result->isOk());

        \clearstatcache(true, $this->tempFile);
        $modTime = \filemtime($this->tempFile);
        $this->assertEquals($timestamp, $modTime);

        $file->close();
    }

    public function testSetTimes(): void
    {
        $file = File::open($this->tempFile)->unwrap();

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

        \clearstatcache(true, $this->tempFile);
        $this->assertEquals($modTimestamp, \filemtime($this->tempFile));
        $this->assertEquals($accessTimestamp, \fileatime($this->tempFile));

        $file->close();
    }

    // --- Lifecycle ---

    public function testClose(): void
    {
        $file = File::open($this->tempFile)->unwrap();
        $file->close();

        // Calling close again should not throw
        $file->close();
        $this->assertTrue(true);
    }

    public function testDestructClosesHandle(): void
    {
        $path = $this->tempDir . '/destructTest.txt';
        \file_put_contents($path, 'test');

        $file = File::open($path)->unwrap();
        unset($file);

        // File should still be accessible (handle was closed, not deleted)
        $this->assertTrue(\file_exists($path));
        $this->assertSame('test', \file_get_contents($path));
    }

    // --- Scoped pattern ---

    public function testWithOpen(): void
    {
        $result = File::withOpen($this->tempFile, static function(File $file): string {
            return $file->readAll()->unwrap()->toString();
        });

        $this->assertTrue($result->isOk());
        $this->assertSame($this->testContent, $result->unwrap());
    }

    public function testWithOpenNonExistentFile(): void
    {
        $result = File::withOpen($this->tempDir . '/nonExistent.txt', static function(File $file): string {
            return $file->readAll()->unwrap()->toString();
        });

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(FileNotFound::class, $result->unwrapErr());
    }

    public function testWithOpenWriteAndRead(): void
    {
        $path = $this->tempDir . '/withOpenWrite.txt';
        \file_put_contents($path, '');

        $result = File::withOpen($path, static function(File $file): string {
            $file->write('written via withOpen')->unwrap();

            return $file->readAll()->unwrap()->toString();
        });

        $this->assertTrue($result->isOk());
        $this->assertSame('written via withOpen', $result->unwrap());
    }

    // --- Integration: create + write + read ---

    public function testCreateWriteRead(): void
    {
        $path = $this->tempDir . '/createWriteRead.txt';
        $file = File::create($path)->unwrap();

        $file->write('test data')->unwrap();
        $content = $file->readAll()->unwrap();

        $this->assertSame('test data', $content->toString());

        $file->close();
    }

    private function cleanupDir(string $dir): void
    {
        if (!\is_dir($dir)) {
            return;
        }

        $files = \scandir($dir);

        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $path = $dir . '/' . $file;

                if (\is_dir($path)) {
                    $this->cleanupDir($path);
                } else {
                    @\chmod($path, 0666);
                    @\unlink($path);
                }
            }
        }

        @\rmdir($dir);
    }
}
