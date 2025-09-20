<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\FileSystem\Unit;

use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\AlreadyExists;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\CreateFailed;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\DirectoryNotFound;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\FileNotFound;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\InvalidFileType;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\PermissionDenied;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\RemoveFailed;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\RenameFailed;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\FileSystem;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\TestCase;

final class FileSystemDirectoryTest extends TestCase
{
    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        vfsStreamWrapper::register();
        vfsStream::umask(0000); // Ensure no umask interferes

        $this->root = vfsStream::setup('root', 0777, [
            'emptyDir' => [],
            'nonEmptyDir' => [
                'file1.txt' => 'content1',
                'file2.txt' => 'content2',
                'subDir' => [
                    'subFile.txt' => 'subcontent',
                ],
            ],
            'readOnlyDir' => [],
        ]);

        $this->root->getChild('readOnlyDir')->chmod(0555);
    }

    public function testCreateDir(): void
    {
        $path = Path::of($this->root->url() . '/newDir');
        $result = FileSystem::createDir($path);

        $this->assertTrue($result->isOk());
        $this->assertTrue(\is_dir($path->toString()));
    }

    public function testCreateDirWhenAlreadyExists(): void
    {
        $path = Path::of($this->root->url() . '/emptyDir');
        $result = FileSystem::createDir($path);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(AlreadyExists::class, $result->unwrapErr());
    }

    public function testCreateDirWithInvalidPermissions(): void
    {
        $path = Path::of($this->root->url() . '/readOnlyDir/subDir');
        $result = FileSystem::createDir($path);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(CreateFailed::class, $result->unwrapErr());
    }

    public function testCreateDirAll(): void
    {
        $path = Path::of($this->root->url() . '/level1/level2/level3');
        $result = FileSystem::createDirAll($path);

        $this->assertTrue($result->isOk());
        $this->assertTrue(\is_dir($this->root->url() . '/level1'));
        $this->assertTrue(\is_dir($this->root->url() . '/level1/level2'));
        $this->assertTrue(\is_dir($this->root->url() . '/level1/level2/level3'));
    }

    public function testCreateDirAllWithExistingPath(): void
    {
        $path = Path::of($this->root->url() . '/emptyDir');
        $result = FileSystem::createDirAll($path);

        $this->assertTrue($result->isOk());
    }

    public function testCreateDirAllWithPartiallyExistingPath(): void
    {
        $level1Path = Path::of($this->root->url() . '/parent');
        \mkdir($level1Path->toString());

        $fullPath = Path::of($this->root->url() . '/parent/child1/child2');
        $result = FileSystem::createDirAll($fullPath);

        $this->assertTrue($result->isOk());
        $this->assertTrue(\is_dir($fullPath->toString()));
    }

    public function testRemoveDir(): void
    {
        $path = Path::of($this->root->url() . '/emptyDir');
        $result = FileSystem::removeDir($path);

        $this->assertTrue($result->isOk());
        $this->assertFalse(\is_dir($path->toString()));
    }

    public function testRemoveDirWithNonEmptyDir(): void
    {
        $path = Path::of($this->root->url() . '/nonEmptyDir');
        $result = FileSystem::removeDir($path);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(RemoveFailed::class, $result->unwrapErr());
        $this->assertTrue(\is_dir($path->toString())); // Directory should still exist
    }

    public function testRemoveDirWithNonExistentDir(): void
    {
        $path = Path::of($this->root->url() . '/nonExistentDir');
        $result = FileSystem::removeDir($path);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(DirectoryNotFound::class, $result->unwrapErr());
    }

    public function testRemoveDirAll(): void
    {
        $path = Path::of($this->root->url() . '/nonEmptyDir');
        $result = FileSystem::removeDirAll($path);

        $this->assertTrue($result->isOk());
        $this->assertFalse(\is_dir($path->toString()));
        $this->assertFalse(\file_exists($this->root->url() . '/nonEmptyDir/file1.txt'));
        $this->assertFalse(\is_dir($this->root->url() . '/nonEmptyDir/subDir'));
    }

    public function testRemoveDirAllWithNonExistentDir(): void
    {
        $path = Path::of($this->root->url() . '/nonExistentDir');
        $result = FileSystem::removeDirAll($path);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(DirectoryNotFound::class, $result->unwrapErr());
    }

    public function testRemoveDirAllWithPermissionDenied(): void
    {
        // Créer la structure de répertoire
        $testDir = vfsStream::newDirectory('testDirWithReadOnly', 0777)->at($this->root);
        $readOnlyFile = vfsStream::newFile('readOnlyFile.txt', 0444)
            ->at($testDir)
            ->setContent('content');

        // Vérifier que le fichier est bien en lecture seule
        $this->assertFalse($readOnlyFile->isWritable(vfsStream::OWNER_USER_1, vfsStream::GROUP_USER_1),
            'Le fichier devrait être en lecture seule');

        $path = Path::of($this->root->url() . '/testDirWithReadOnly');

        $result = FileSystem::removeDirAll($path);

        $this->markTestSkipped("I didn't find a clean way to simulate a user with no permission to delete files, so here the result is Ok, or it should be Err");
    }

    public function testRenameDir(): void
    {
        // Create a directory with some content
        $sourceDir = vfsStream::newDirectory('sourceDir', 0777)->at($this->root);
        vfsStream::newFile('file1.txt', 0644)->at($sourceDir)->setContent('content1');
        vfsStream::newDirectory('subDir', 0777)->at($sourceDir);

        $sourcePath = Path::of($this->root->url() . '/sourceDir');
        $destPath = Path::of($this->root->url() . '/renamedDir');

        $fileSystem = new FileSystem();
        $result = $fileSystem->renameDir($sourcePath, $destPath);

        $this->assertTrue($result->isOk());
        $this->assertFalse(\is_dir($sourcePath->toString()));
        $this->assertTrue(\is_dir($destPath->toString()));
        $this->assertTrue(\file_exists($destPath->toString() . '/file1.txt'));
        $this->assertTrue(\is_dir($destPath->toString() . '/subDir'));
    }

    public function testRenameDirWithNonExistentSource(): void
    {
        $sourcePath = Path::of($this->root->url() . '/nonExistentDir');
        $destPath = Path::of($this->root->url() . '/renamedDir');

        $fileSystem = new FileSystem();
        $result = $fileSystem->renameDir($sourcePath, $destPath);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(DirectoryNotFound::class, $result->unwrapErr());
    }

    public function testRenameDirWithExistingDestination(): void
    {
        $sourcePath = Path::of($this->root->url() . '/emptyDir');
        $destPath = Path::of($this->root->url() . '/nonEmptyDir');

        $fileSystem = new FileSystem();
        $result = $fileSystem->renameDir($sourcePath, $destPath);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(RenameFailed::class, $result->unwrapErr());
    }

    public function testRemoveFile(): void
    {
        // Create a test file
        $_ = vfsStream::newFile('fileToRemove.txt', 0644)->at($this->root)->setContent('content');
        $filePath = Path::of($this->root->url() . '/fileToRemove.txt');

        $this->assertTrue(\file_exists($filePath->toString()));

        $result = FileSystem::removeFile($filePath);

        $this->assertTrue($result->isOk());
        $this->assertFalse(\file_exists($filePath->toString()));
    }

    public function testRemoveFileWithNonExistentFile(): void
    {
        $filePath = Path::of($this->root->url() . '/nonExistentFile.txt');

        $result = FileSystem::removeFile($filePath);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(FileNotFound::class, $result->unwrapErr());
    }

    public function testRemoveFileWithDirectory(): void
    {
        $dirPath = Path::of($this->root->url() . '/emptyDir');

        $result = FileSystem::removeFile($dirPath);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(InvalidFileType::class, $result->unwrapErr());
    }

    public function testRemoveFileWithReadOnlyFile(): void
    {
        // Create a read-only file
        $_ = vfsStream::newFile('readOnlyFileToRemove.txt', 0444)->at($this->root)->setContent('content');
        $filePath = Path::of($this->root->url() . '/readOnlyFileToRemove.txt');

        $result = FileSystem::removeFile($filePath);

        // In vfsStream, read-only files can still be deleted by the owner
        // This is a limitation of vfsStream for testing purposes
        // In a real filesystem, this might fail with PermissionDenied
        if ($result->isOk()) {
            $this->assertFalse(\file_exists($filePath->toString()));
        } else {
            // If it fails, it should be a permission error
            $this->assertInstanceOf(PermissionDenied::class, $result->unwrapErr());
        }
    }
}
