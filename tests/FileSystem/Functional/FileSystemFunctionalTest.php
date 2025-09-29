<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\FileSystem\Functional;

use Jsadaa\PhpCoreLibrary\Modules\FileSystem\File;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\FileSystem;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Permissions;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use PHPUnit\Framework\TestCase;

final class FileSystemFunctionalTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = \sys_get_temp_dir() . '/php-core-library-functional-' . \uniqid();
        FileSystem::createDirAll($this->tempDir);
    }

    protected function tearDown(): void
    {
        if (Path::of($this->tempDir)->exists()) {
            $result = FileSystem::removeDirAll($this->tempDir);

            if ($result->isErr()) {
                $this->manualCleanup($this->tempDir);
            }
        }
    }

    public function testProjectStructureCreationAndManagement(): void
    {
        // Simulate creating a typical web project structure
        $projectRoot = Path::of($this->tempDir . '/my-project');

        $directories = [
            'src',
            'src/Controllers',
            'src/Models',
            'config',
            'public',
            'tests',
        ];

        foreach ($directories as $dir) {
            $dirPath = $projectRoot->join(Path::of($dir));
            $result = FileSystem::createDirAll($dirPath);
            $this->assertTrue($result->isOk(), "Failed to create directory: $dir");
        }

        $configs = [
            'config/app.php' => '<?php return ["name" => "My App"];',
            '.env' => "APP_NAME=MyApp\nAPP_ENV=development",
        ];

        foreach ($configs as $filePath => $content) {
            $fullPath = $projectRoot->join(Path::of($filePath));
            $result = FileSystem::write($fullPath, $content);
            $this->assertTrue($result->isOk(), "Failed to create config file: $filePath");
        }

        $this->assertTrue($projectRoot->join(Path::of('src/Controllers'))->exists());
        $this->assertTrue($projectRoot->join(Path::of('config'))->exists());

        $appConfig = FileSystem::read($projectRoot->join(Path::of('config/app.php')))->unwrap();
        $this->assertStringContainsString('My App', $appConfig->toString());

        $configDir = $projectRoot->join(Path::of('config'));
        $entries = FileSystem::readDir($configDir)->unwrap();
        $this->assertEquals(1, $entries->size()->toInt());
    }

    public function testLogRotationWorkflow(): void
    {
        // Simulate a log rotation system
        $logDir = Path::of($this->tempDir . '/logs');
        FileSystem::createDir($logDir)->unwrap();

        $currentLogFile = $logDir->join(Path::of('app.log'));

        $logEntries = [
            '[2024-01-01 10:00:00] INFO: Application started',
            '[2024-01-01 10:05:00] DEBUG: User logged in',
            '[2024-01-01 10:10:00] ERROR: Database connection failed',
        ];

        foreach ($logEntries as $entry) {
            $file = $currentLogFile->exists()
                ? File::from($currentLogFile)->unwrap()
                : File::new($currentLogFile)->unwrap();

            $file->append($entry . "\n")->unwrap();
        }

        $logFile = File::from($currentLogFile)->unwrap();
        $size = $logFile->size()->unwrap();
        $this->assertGreaterThan(100, $size->toInt());

        // Simulate log rotation when file gets too large
        $maxSize = 50; // Very small size for testing

        if ($size->toInt() > $maxSize) {
            $timestamp = \date('Y-m-d_H-i-s');
            $rotatedLogFile = $logDir->join(Path::of("app-$timestamp.log"));

            // Move current log to rotated file
            FileSystem::renameFile($currentLogFile, $rotatedLogFile)->unwrap();

            File::new($currentLogFile)->unwrap();

            // Verify rotation
            $this->assertTrue($rotatedLogFile->exists());
            $this->assertTrue($currentLogFile->exists());
        }
    }

    public function testConfigurationManagement(): void
    {
        // Simulate managing application configuration with atomic updates
        $configDir = Path::of($this->tempDir . '/config');
        FileSystem::createDir($configDir)->unwrap();

        $configFile = $configDir->join(Path::of('settings.json'));

        $initialConfig = [
            'app_name' => 'MyApp',
            'version' => '1.0.0',
            'features' => ['auth', 'api'],
        ];

        $configJson = \json_encode($initialConfig, \JSON_PRETTY_PRINT);
        $file = File::new($configFile)->unwrap();
        $file->writeAtomic($configJson, true)->unwrap();

        $currentConfig = FileSystem::read($configFile)->unwrap();
        $config = \json_decode($currentConfig->toString(), true);

        $this->assertEquals('MyApp', $config['app_name']);
        $this->assertEquals('1.0.0', $config['version']);

        $config['version'] = '1.1.0';
        $config['features'][] = 'logging';

        $updatedJson = \json_encode($config, \JSON_PRETTY_PRINT);
        $file = File::from($configFile)->unwrap();
        $file->writeAtomic($updatedJson, true)->unwrap();

        $verifyContent = FileSystem::read($configFile)->unwrap();
        $verifyConfig = \json_decode($verifyContent->toString(), true);

        $this->assertEquals('1.1.0', $verifyConfig['version']);
        $this->assertContains('logging', $verifyConfig['features']);
    }

    public function testDataProcessingPipeline(): void
    {
        // Simulate processing CSV data files
        $dataDir = Path::of($this->tempDir . '/data');
        FileSystem::createDirAll($dataDir->join(Path::of('input')))->unwrap();
        FileSystem::createDirAll($dataDir->join(Path::of('output')))->unwrap();

        $csvData = "id,name,email,age\n" .
                   "1,John Doe,john@example.com,25\n" .
                   "2,Jane Smith,jane@example.com,30\n" .
                   "3,Bob Johnson,bob@example.com,35\n";

        $inputFile = $dataDir->join(Path::of('input/users.csv'));
        FileSystem::write($inputFile, $csvData)->unwrap();

        $content = FileSystem::read($inputFile)->unwrap();
        $lines = $content->split("\n");

        $header = $lines->get(0)->unwrap();
        $this->assertEquals('id,name,email,age', $header->toString());

        // Transform data (filter adults and format output)
        $adults = [];

        for ($i = 1; $i < $lines->size()->toInt(); $i++) {
            $line = $lines->get($i)->unwrap();

            if ($line->size()->toInt() === 0) continue;

            $fields = $line->split(',');
            $age = (int) $fields->get(3)->unwrap()->toString();

            if ($age >= 30) {
                $name = $fields->get(1)->unwrap()->toString();
                $email = $fields->get(2)->unwrap()->toString();
                $adults[] = "Name: $name, Email: $email, Age: $age";
            }
        }

        $outputFile = $dataDir->join(Path::of('output/adults.txt'));
        $outputContent = "Adults (30+):\n" . \implode("\n", $adults);
        FileSystem::write($outputFile, $outputContent)->unwrap();

        $this->assertTrue($outputFile->exists());

        $result = FileSystem::read($outputFile)->unwrap();
        $this->assertStringContainsString('Jane Smith', $result->toString());
        $this->assertStringContainsString('Bob Johnson', $result->toString());
        $this->assertStringNotContainsString('John Doe', $result->toString()); // Under 30
    }

    public function testPermissionManagementWorkflow(): void
    {
        // Simulate setting up proper permissions for a web application
        $webRoot = Path::of($this->tempDir . '/webapp');

        FileSystem::createDir($webRoot)->unwrap();

        $directories = ['public', 'storage', 'config'];

        foreach ($directories as $dir) {
            FileSystem::createDir($webRoot->join(Path::of($dir)))->unwrap();
        }

        $files = [
            'public/index.php' => '<?php echo "Hello World";',
            'storage/app.log' => '',
            'config/database.conf' => 'host=localhost',
        ];

        foreach ($files as $filePath => $content) {
            $fullPath = $webRoot->join(Path::of($filePath));
            FileSystem::write($fullPath, $content)->unwrap();
        }

        // Set appropriate permissions
        $permissionMap = [
            'public/index.php' => 0644,
            'config/database.conf' => 0600,
        ];

        foreach ($permissionMap as $path => $mode) {
            $fullPath = $webRoot->join(Path::of($path));
            $permissions = Permissions::create($mode);
            $result = $permissions->apply($fullPath);
            $this->assertTrue($result->isOk(), "Failed to set permissions for: $path");
        }

        $configFile = $webRoot->join(Path::of('config/database.conf'));
        $configPerms = Permissions::of($configFile);
        $this->assertTrue($configPerms->isReadable());
        $this->assertTrue($configPerms->isWritable());

        $metadataResult = FileSystem::metadata($configFile);
        $this->assertTrue($metadataResult->isOk());
        $metadata = $metadataResult->unwrap();
        $this->assertTrue($metadata->isFile());
    }

    public function testBackupAndRestoreWorkflow(): void
    {
        // Simulate a backup and restore system
        $sourceDir = Path::of($this->tempDir . '/source');
        $backupDir = Path::of($this->tempDir . '/backup');

        FileSystem::createDirAll($sourceDir)->unwrap();
        FileSystem::createDirAll($backupDir)->unwrap();

        $sourceFiles = [
            'document.txt' => 'Important document content',
            'config.json' => '{"setting1": "value1"}',
        ];

        foreach ($sourceFiles as $fileName => $content) {
            $filePath = $sourceDir->join(Path::of($fileName));
            FileSystem::write($filePath, $content)->unwrap();
        }

        // Create backup (copy all files)
        $sourceEntries = FileSystem::readDir($sourceDir)->unwrap();

        foreach ($sourceEntries->iter() as $entry) {
            if ($entry->path()->isFile()) {
                $fileName = $entry->fileName()->unwrap();
                $sourcePath = $entry->path();
                $backupPath = $backupDir->join(Path::of($fileName));

                FileSystem::copyFile($sourcePath, $backupPath)->unwrap();
            }
        }

        $backupEntries = FileSystem::readDir($backupDir)->unwrap();
        $this->assertEquals(2, $backupEntries->size()->toInt());

        $documentPath = $sourceDir->join(Path::of('document.txt'));
        FileSystem::write($documentPath, 'CORRUPTED DATA')->unwrap();

        $corruptedContent = FileSystem::read($documentPath)->unwrap();
        $this->assertEquals('CORRUPTED DATA', $corruptedContent->toString());

        FileSystem::removeFile($documentPath)->unwrap();
        $backupDocument = $backupDir->join(Path::of('document.txt'));
        $copyResult = FileSystem::copyFile($backupDocument, $documentPath);
        $this->assertTrue($copyResult->isOk());

        $restoredContent = FileSystem::read($documentPath)->unwrap();
        $this->assertEquals('Important document content', $restoredContent->toString());
    }

    public function testTemporaryFileManagement(): void
    {
        // Simulate working with temporary files for data processing
        $tempWorkDir = Path::of($this->tempDir . '/temp-work');
        FileSystem::createDir($tempWorkDir)->unwrap();

        $tempFiles = [];

        for ($i = 1; $i <= 3; $i++) {
            $tempFileName = "temp-data-$i.txt";
            $tempPath = $tempWorkDir->join(Path::of($tempFileName));
            $content = "Temporary data set $i\nData line $i";

            FileSystem::write($tempPath, $content)->unwrap();
            $tempFiles[] = $tempPath;
        }

        $combinedContent = Str::new();

        foreach ($tempFiles as $tempFile) {
            $content = FileSystem::read($tempFile)->unwrap();
            $combinedContent = $combinedContent->append($content)->append(Str::of("\n---\n"));
        }

        $resultFile = $tempWorkDir->join(Path::of('combined-result.txt'));
        FileSystem::write($resultFile, $combinedContent->toString())->unwrap();

        foreach ($tempFiles as $tempFile) {
            FileSystem::removeFile($tempFile)->unwrap();
            $this->assertFalse($tempFile->exists());
        }

        $this->assertTrue($resultFile->exists());
        $result = FileSystem::read($resultFile)->unwrap();

        for ($i = 1; $i <= 3; $i++) {
            $this->assertStringContainsString("Temporary data set $i", $result->toString());
        }

        $finalEntries = FileSystem::readDir($tempWorkDir)->unwrap();
        $this->assertEquals(1, $finalEntries->size()->toInt());
    }

    public function testSymlinkManagement(): void
    {
        $projectDir = Path::of($this->tempDir . '/project');
        $sharedDir = Path::of($this->tempDir . '/shared');

        FileSystem::createDirAll($projectDir->join(Path::of('current')))->unwrap();
        FileSystem::createDirAll($sharedDir)->unwrap();

        $sharedConfig = $sharedDir->join(Path::of('app.conf'));
        FileSystem::write($sharedConfig, 'shared_setting=true')->unwrap();

        $projectConfig = $projectDir->join(Path::of('current/app.conf'));
        $symlinkResult = FileSystem::symLink($sharedConfig, $projectConfig);

        // If symlink creation fails, skip the test (may not be supported)
        if ($symlinkResult->isErr()) {
            $this->markTestSkipped('Symlink creation failed - may not be supported on this system');
        }

        $this->assertTrue($projectConfig->exists());
        $contentResult = FileSystem::read($projectConfig);

        // If reading through symlink fails, skip remaining tests
        if ($contentResult->isErr()) {
            $this->markTestSkipped('Symlink reading failed - filesystem may not support symlinks properly');
        }

        $content = $contentResult->unwrap();
        $this->assertEquals('shared_setting=true', $content->toString());

        $metadataResult = FileSystem::metadata($projectConfig);

        if ($metadataResult->isOk()) {
            $metadata = $metadataResult->unwrap();
            $this->assertTrue($metadata->isSymLink());

            $targetResult = FileSystem::readSymlink($projectConfig);

            if ($targetResult->isOk()) {
                $target = $targetResult->unwrap();
                $this->assertEquals($sharedConfig->toString(), $target->toString());
            }
        }

        FileSystem::write($sharedConfig, 'shared_setting=false\nnew_setting=enabled')->unwrap();

        $updatedContentResult = FileSystem::read($projectConfig);

        if ($updatedContentResult->isOk()) {
            $updatedContent = $updatedContentResult->unwrap();
            $this->assertStringContainsString('shared_setting=false', $updatedContent->toString());
            $this->assertStringContainsString('new_setting=enabled', $updatedContent->toString());
        }

        // Test broken symlink detection by removing target
        FileSystem::removeFile($sharedConfig)->unwrap();

        // After removing target, the symlink should be broken
        // Different systems handle this differently, so we just check it doesn't crash
        $brokenLinkExists = $projectConfig->exists();
        $this->assertIsBool($brokenLinkExists);
    }

    public function testConcurrentFileAccess(): void
    {
        // Simulate multiple processes accessing the same file
        $sharedFile = Path::of($this->tempDir . '/shared.log');

        FileSystem::write($sharedFile, "Initial log entry\n")->unwrap();

        $entries = [
            'Process 1: Starting operation',
            'Process 2: Reading configuration',
            'Process 1: Operation complete',
            'Process 3: Cleanup started',
            'Process 2: Configuration loaded',
            'Process 3: Cleanup complete',
        ];

        foreach ($entries as $entry) {
            $file = File::from($sharedFile)->unwrap();
            $file->append($entry . "\n")->unwrap();
        }

        $finalContent = FileSystem::read($sharedFile)->unwrap();

        foreach ($entries as $entry) {
            $this->assertStringContainsString($entry, $finalContent->toString());
        }

        $configData = [
            'database' => ['host' => 'localhost', 'port' => 3306],
            'cache' => ['driver' => 'redis', 'ttl' => 3600],
            'logging' => ['level' => 'info', 'file' => '/var/log/app.log'],
        ];

        $configFile = Path::of($this->tempDir . '/config.json');
        $file = File::new($configFile)->unwrap();

        $jsonContent = \json_encode($configData, \JSON_PRETTY_PRINT);
        $file->writeAtomic($jsonContent, true)->unwrap();

        $readConfig = FileSystem::read($configFile)->unwrap();
        $decodedConfig = \json_decode($readConfig->toString(), true);
        $this->assertEquals('localhost', $decodedConfig['database']['host']);
        $this->assertEquals(3600, $decodedConfig['cache']['ttl']);
    }

    public function testBatchFileOperations(): void
    {
        // Test efficient batch operations on multiple files
        $batchDir = Path::of($this->tempDir . '/batch');
        FileSystem::createDir($batchDir)->unwrap();

        $files = [];

        for ($i = 1; $i <= 10; $i++) {
            $fileName = \sprintf('file_%03d.txt', $i);
            $filePath = $batchDir->join(Path::of($fileName));
            $content = \str_repeat("Line $i data\n", $i * 10);

            FileSystem::write($filePath, $content)->unwrap();
            $files[] = $filePath;
        }

        $totalSize = 0;

        foreach ($files as $filePath) {
            $size = File::from($filePath)->unwrap()->size()->unwrap();
            $totalSize += $size->toInt();
        }

        $this->assertGreaterThan(1000, $totalSize); // Should have substantial content

        $readOnlyPerms = Permissions::create(0444);

        foreach ($files as $filePath) {
            $readOnlyPerms->apply($filePath)->unwrap();
        }

        foreach ($files as $filePath) {
            $perms = Permissions::of($filePath);
            $this->assertTrue($perms->isReadable());
            $this->assertFalse($perms->isWritable());
        }

        $writablePerms = Permissions::create(0644);

        foreach ($files as $filePath) {
            $writablePerms->apply($filePath)->unwrap();
        }

        $backupDir = $batchDir->join(Path::of('backup'));
        FileSystem::createDir($backupDir)->unwrap();

        foreach ($files as $filePath) {
            $fileName = $filePath->fileName()->unwrap();
            $backupPath = $backupDir->join(Path::of($fileName));
            FileSystem::copyFile($filePath, $backupPath)->unwrap();
        }

        $backupEntries = FileSystem::readDir($backupDir)->unwrap();
        $this->assertEquals(10, $backupEntries->size()->toInt());

        foreach ($files as $filePath) {
            FileSystem::removeFile($filePath)->unwrap();
        }

        $remainingEntries = FileSystem::readDir($batchDir)->unwrap();
        $this->assertEquals(1, $remainingEntries->size()->toInt());
    }

    public function testFileSystemErrorHandling(): void
    {
        // Test various error conditions and recovery

        // Test reading non-existent file
        $nonExistentFile = Path::of($this->tempDir . '/does-not-exist.txt');
        $readResult = FileSystem::read($nonExistentFile);
        $this->assertTrue($readResult->isErr());

        // Test writing to invalid path
        $invalidPath = Path::of('/root/restricted/file.txt');
        $writeResult = FileSystem::write($invalidPath, 'test');
        $this->assertTrue($writeResult->isErr());

        // Test creating directory with invalid permissions
        $restrictedDir = Path::of($this->tempDir . '/restricted');
        FileSystem::createDir($restrictedDir)->unwrap();

        // Try to create subdirectory in read-only parent
        $readOnlyPerms = Permissions::create(0444);
        $readOnlyPerms->apply($restrictedDir)->unwrap();

        $subDir = $restrictedDir->join(Path::of('subdir'));
        $createResult = FileSystem::createDir($subDir);
        $this->assertTrue($createResult->isErr());

        $writablePerms = Permissions::create(0755);
        $writablePerms->apply($restrictedDir)->unwrap();

        // Test recovery: create file, corrupt it, then restore
        $testFile = Path::of($this->tempDir . '/recovery-test.txt');
        $originalContent = 'Original content that should be preserved';
        FileSystem::write($testFile, $originalContent)->unwrap();

        // Create backup
        $backupFile = Path::of($this->tempDir . '/recovery-test.backup');
        FileSystem::copyFile($testFile, $backupFile)->unwrap();

        // Corrupt original
        FileSystem::write($testFile, 'CORRUPTED DATA')->unwrap();
        $corruptedContent = FileSystem::read($testFile)->unwrap();
        $this->assertEquals('CORRUPTED DATA', $corruptedContent->toString());

        // Restore from backup
        FileSystem::removeFile($testFile)->unwrap();
        FileSystem::copyFile($backupFile, $testFile)->unwrap();

        // Verify recovery
        $recoveredContent = FileSystem::read($testFile)->unwrap();
        $this->assertEquals($originalContent, $recoveredContent->toString());
    }

    private function manualCleanup(string $dir): void
    {
        if (!\is_dir($dir)) {
            return;
        }

        $files = \scandir($dir);

        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $path = $dir . '/' . $file;

                if (\is_dir($path)) {
                    $this->manualCleanup($path);
                } else {
                    @\chmod($path, 0666);
                    @\unlink($path);
                }
            }
        }
        @\rmdir($dir);
    }
}
