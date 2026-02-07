<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\FileSystem;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\AlreadyExists;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\CreateFailed;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\DirectoryNotEmpty;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\DirectoryNotFound;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\FileNotFound;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\InvalidFileType;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\InvalidMetadata;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\LinkFailed;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\PermissionDenied;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\ReadFailed;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\RemoveFailed;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\RenameFailed;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\SymlinkFailed;
use Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error\WriteFailed;
use Jsadaa\PhpCoreLibrary\Modules\Option\Option;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use Jsadaa\PhpCoreLibrary\Primitives\Unit;

/**
 * Provides static methods for file and directory operations.
 *
 * This class offers a set of static methods for interacting with the filesystem,
 * including reading/writing files, managing directories, and working with file metadata.
 * All operations return Result objects to handle errors in a type-safe way.
 *
 */
final readonly class FileSystem {
    /**
     * Get metadata for a path
     *
     * Retrieves file metadata including size, permissions, timestamps, and type.
     * Works with both files and directories.
     *
     * @param string|Path $path The path to get metadata for
     * @return Result<Metadata, FileNotFound|InvalidMetadata> A Result containing the metadata or an error
     */
    public static function metadata(string | Path $path): Result
    {
        $pathObj = \is_string($path) ? Path::of($path) : $path;

        if (!$pathObj->exists()) {
            /** @var Result<Metadata, FileNotFound|InvalidMetadata> */
            return Result::err(new FileNotFound(\sprintf('Path does not exist: %s', $pathObj->toString())));
        }

        /** @var Result<Metadata, FileNotFound|InvalidMetadata> */
        return Metadata::of($pathObj);
    }

    /**
     * Read file contents as bytes
     *
     * Reads the entire file as a Sequence of native int byte values (0-255).
     * Returns native int values for performance. For small files where Integer
     * wrappers are needed: `->map(fn(int $b) => Integer::of($b))`
     *
     * @param string|Path $path The path to the file to read
     * @return Result<Sequence<int>, FileNotFound|ReadFailed|InvalidFileType> A Result containing a Sequence of bytes or an error
     */
    public static function readBytes(string | Path $path): Result
    {
        $pathObj = \is_string($path) ? Path::of($path) : $path;
        $pathStr = $pathObj->toString();

        if (!\file_exists($pathStr)) {
            /** @var Result<Sequence<int>, FileNotFound|ReadFailed|InvalidFileType> */
            return Result::err(new FileNotFound(\sprintf('File not found: %s', $pathStr)));
        }

        if (!\is_file($pathStr)) {
            /** @var Result<Sequence<int>, FileNotFound|ReadFailed|InvalidFileType> */
            return Result::err(new InvalidFileType(\sprintf('Not a regular file: %s', $pathStr)));
        }

        $content = @\file_get_contents($pathStr);

        if ($content === false) {
            /** @var Result<Sequence<int>, FileNotFound|ReadFailed|InvalidFileType> */
            return Result::err(new ReadFailed(\sprintf('Failed to read file: %s', $pathStr)));
        }

        if ($content === '') {
            /** @var Result<Sequence<int>, FileNotFound|ReadFailed|InvalidFileType> */
            return Result::ok(Sequence::ofArray([]));
        }

        $bytes = \unpack('C*', $content);

        if ($bytes === false) {
            /** @var Result<Sequence<int>, FileNotFound|ReadFailed|InvalidFileType> */
            return Result::err(new ReadFailed(\sprintf('Failed to unpack bytes from: %s', $pathStr)));
        }

        /** @var Result<Sequence<int>, FileNotFound|ReadFailed|InvalidFileType> */
        return Result::ok(Sequence::ofArray($bytes));
    }

    /**
     * Read directory contents
     *
     * Lists all entries in a directory, returning a Sequence of Path objects.
     *
     * The directory path must be a valid directory.
     *
     * Note: The result does not include "." and ".." entries.
     *
     * @param string|Path $path The directory path to read
     * @return Result<Sequence<Path>, DirectoryNotFound|ReadFailed|InvalidFileType> A Result containing directory paths or an error
     */
    public static function readDir(string | Path $path): Result
    {
        $path = \is_string($path) ? Path::of($path) : $path;

        if (!$path->exists()) {
            /** @var Result<Sequence<Path>, DirectoryNotFound|ReadFailed|InvalidFileType> */
            return Result::err(new DirectoryNotFound(\sprintf('Path does not exist: %s', $path)));
        }

        if (!$path->isDir()) {
            /** @var Result<Sequence<Path>, DirectoryNotFound|ReadFailed|InvalidFileType> */
            return Result::err(new InvalidFileType(\sprintf('Path is not a directory: %s', $path)));
        }

        $contents = @\scandir($path->toString());

        if ($contents === false) {
            /** @var Result<Sequence<Path>, DirectoryNotFound|ReadFailed|InvalidFileType> */
            return Result::err(new ReadFailed(\sprintf('Failed to read directory: %s', $path)));
        }

        /** @var Result<Sequence<Path>, DirectoryNotFound|ReadFailed|InvalidFileType> */
        return Result::ok(
            Sequence::ofArray($contents)
                ->filter(static fn(string $item) => $item !== '.' && $item !== '..')
                ->map(static fn(string $item) => $path->join(Path::of($item))),
        );
    }

    /**
     * Read the target path of a symlink
     *
     * Returns the target path that the symlink points to.
     * This does not resolve the target path further if it is also a symlink.
     *
     * @param string|Path $path The symlink to read
     * @return Result<Path, InvalidFileType|ReadFailed> A Result containing the target path or an error
     */
    public static function readSymlink(string | Path $path): Result
    {
        $path = \is_string($path) ? Path::of($path) : $path;

        if (!$path->isSymlink()) {
            /** @var Result<Path, InvalidFileType|ReadFailed> */
            return Result::err(new InvalidFileType(\sprintf('Path is not a link: %s', $path->toString())));
        }

        $concretePath = @\readlink($path->toString());

        if ($concretePath === false) {
            /** @var Result<Path, InvalidFileType|ReadFailed> */
            return Result::err(new ReadFailed(\sprintf('Failed to read link: %s', $path->toString())));
        }

        /** @var Result<Path, InvalidFileType|ReadFailed> */
        return Result::ok(Path::of($concretePath));
    }

    /**
     * Read file contents as a Str
     *
     * Reads the entire file content and returns it as a Str object.
     *
     * @param string|Path $path The path to the file to read
     * @return Result<Str, FileNotFound|ReadFailed|InvalidFileType> A Result containing the file contents as a Str or an error
     */
    public static function read(string | Path $path): Result
    {
        $pathObj = $path instanceof Path ? $path : Path::of($path);
        $pathStr = $pathObj->toString();

        if (!\file_exists($pathStr)) {
            /** @var Result<Str, FileNotFound|ReadFailed|InvalidFileType> */
            return Result::err(new FileNotFound(\sprintf('File not found: %s', $pathStr)));
        }

        if (!\is_file($pathStr)) {
            /** @var Result<Str, FileNotFound|ReadFailed|InvalidFileType> */
            return Result::err(new InvalidFileType(\sprintf('Not a regular file: %s', $pathStr)));
        }

        $content = @\file_get_contents($pathStr);

        if ($content === false) {
            /** @var Result<Str, FileNotFound|ReadFailed|InvalidFileType> */
            return Result::err(new ReadFailed(\sprintf('Failed to read file: %s', $pathStr)));
        }

        /** @var Result<Str, FileNotFound|ReadFailed|InvalidFileType> */
        return Result::ok(Str::of($content));
    }

    /**
     * Write content to a file
     *
     * Writes a string to a file, creating the file if it doesn't exist or
     * overwriting it if it does.
     *
     * @param string|Path $path The path to the file to write
     * @param string|Str $contents The content to write to the file
     * @return Result<Unit, WriteFailed> A Result indicating success or an error
     */
    public static function write(string | Path $path, string | Str $contents): Result
    {
        $pathStr = $path instanceof Path ? $path->toString() : $path;
        $dataStr = $contents instanceof Str ? $contents->toString() : $contents;

        $result = @\file_put_contents($pathStr, $dataStr);

        if ($result === false) {
            /** @var Result<Unit, WriteFailed> */
            return Result::err(new WriteFailed(\sprintf('Failed to write to file: %s', $pathStr)));
        }

        /** @var Result<Unit, WriteFailed> */
        return Result::ok(Unit::new());
    }

    /**
     * Copy a file
     *
     * Copies a file from the source path to the destination path using PHP's
     * native copy(), which handles streaming without loading the full content
     * into memory.
     *
     * @param string|Path $source The source file path
     * @param string|Path $destination The destination file path
     * @return Result<Unit, FileNotFound|InvalidFileType|WriteFailed> A Result indicating success or an error
     */
    public static function copyFile(string | Path $source, string | Path $destination): Result
    {
        $sourcePath = \is_string($source) ? Path::of($source) : $source;
        $destPath = \is_string($destination) ? Path::of($destination) : $destination;
        $sourceStr = $sourcePath->toString();
        $destStr = $destPath->toString();

        if (!\file_exists($sourceStr)) {
            /** @var Result<Unit, FileNotFound|InvalidFileType|WriteFailed> */
            return Result::err(new FileNotFound(\sprintf('Source file does not exist: %s', $sourceStr)));
        }

        if (!\is_file($sourceStr)) {
            /** @var Result<Unit, FileNotFound|InvalidFileType|WriteFailed> */
            return Result::err(new InvalidFileType(\sprintf('Source is not a regular file: %s', $sourceStr)));
        }

        if (!@\copy($sourceStr, $destStr)) {
            /** @var Result<Unit, FileNotFound|InvalidFileType|WriteFailed> */
            return Result::err(new WriteFailed(\sprintf('Failed to copy %s to %s', $sourceStr, $destStr)));
        }

        /** @var Result<Unit, FileNotFound|InvalidFileType|WriteFailed> */
        return Result::ok(Unit::new());
    }

    /**
     * Rename a file
     *
     * Renames or moves a file from one location to another.
     * This is more efficient than copy+delete when moving files, especially large ones.
     * The source must be a regular file, not a directory.
     *
     * This will replace the destination file if it exists.
     *
     * @param string|Path $source The source path
     * @param string|Path $dest The destination path
     * @return Result<Unit, InvalidFileType|FileNotFound|RenameFailed|PermissionDenied> A Result indicating success or an error
     */
    public static function renameFile(string | Path $source, string | Path $dest): Result
    {
        $sourcePath = \is_string($source) ? Path::of($source) : $source;
        $destPath = \is_string($dest) ? Path::of($dest) : $dest;

        if (!$sourcePath->exists()) {
            /** @var Result<Unit, InvalidFileType|FileNotFound|RenameFailed|PermissionDenied> */
            return Result::err(new FileNotFound(\sprintf('Source file does not exist: %s', $sourcePath->toString())));
        }

        if (!$sourcePath->isFile()) {
            /** @var Result<Unit, InvalidFileType|FileNotFound|RenameFailed|PermissionDenied> */
            return Result::err(new InvalidFileType(\sprintf('Source file is not a file: %s', $sourcePath->toString())));
        }

        if(!Permissions::of($sourcePath)->isReadable()) {
            /** @var Result<Unit, InvalidFileType|FileNotFound|RenameFailed|PermissionDenied> */
            return Result::err(new PermissionDenied(\sprintf('Source file is not readable: %s', $sourcePath->toString())));
        }

        $result = @\rename($sourcePath->toString(), $destPath->toString());

        if ($result === false) {
            /** @var Result<Unit, InvalidFileType|FileNotFound|RenameFailed|PermissionDenied> */
            return Result::err(new RenameFailed(\sprintf('Failed to rename file: %s to %s', $sourcePath->toString(), $destPath->toString())));
        }

        /** @var Result<Unit, InvalidFileType|FileNotFound|RenameFailed|PermissionDenied> */
        return Result::ok(Unit::new());
    }

    /**
     * Rename a directory
     *
     * Renames or moves a directory from one location to another.
     * The source directory must exist and be readable, the target directory must not exist.
     *
     * PHP's rename() function is not always reliable with directories across different
     * filesystems. If it fails, this method falls back to using system commands (mv/move)
     * via proc_open() for better compatibility.
     *
     * @param Path $sourcePath The source directory path
     * @param Path $destPath The destination directory path
     * @return Result<Unit, InvalidFileType|DirectoryNotFound|RenameFailed|PermissionDenied> A Result indicating success or an error
     */
    public static function renameDir(Path $sourcePath, Path $destPath): Result
    {
        if (!$sourcePath->exists()) {
            /** @var Result<Unit, InvalidFileType|DirectoryNotFound|RenameFailed|PermissionDenied> */
            return Result::err(new DirectoryNotFound(\sprintf('Source directory does not exist: %s', $sourcePath->toString())));
        }

        if (!$sourcePath->isDir()) {
            /** @var Result<Unit, InvalidFileType|DirectoryNotFound|RenameFailed|PermissionDenied> */
            return Result::err(new InvalidFileType(\sprintf('Source directory is not a directory: %s', $sourcePath->toString())));
        }

        if ($destPath->exists()) {
            /** @var Result<Unit, InvalidFileType|DirectoryNotFound|RenameFailed|PermissionDenied> */
            return Result::err(new RenameFailed(\sprintf('Destination directory already exists: %s', $destPath->toString())));
        }

        if (!Permissions::of($sourcePath)->isReadable()) {
            /** @var Result<Unit, InvalidFileType|DirectoryNotFound|RenameFailed|PermissionDenied> */
            return Result::err(new PermissionDenied(\sprintf('Source directory is not readable: %s', $sourcePath->toString())));
        }

        $result = @\rename($sourcePath->toString(), $destPath->toString());

        if ($result === false) {
            // As rename is not really reliable with directories, we fall back to using the command line
            $cmd = \PHP_OS_FAMILY === 'Windows'
                ? ['move', $sourcePath->toString(), $destPath->toString()]
                : ['mv', $sourcePath->toString(), $destPath->toString()];

            $process = \proc_open(
                $cmd,
                [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ],
                $pipes,
                null,
                null,
                ['bypass_shell' => true],
            );

            if (!\is_resource($process)) {
                /** @var Result<Unit, InvalidFileType|DirectoryNotFound|RenameFailed|PermissionDenied> */
                return Result::err(new RenameFailed(\sprintf('Failed to rename directory: %s to %s', $sourcePath->toString(), $destPath->toString())));
            }

            \fclose($pipes[0]);
            $stderr = \stream_get_contents($pipes[2]);
            \fclose($pipes[1]);
            \fclose($pipes[2]);

            $return_code = \proc_close($process);

            if ($return_code !== 0) {
                /** @var Result<Unit, InvalidFileType|DirectoryNotFound|RenameFailed|PermissionDenied> */
                return Result::err(new RenameFailed(\sprintf('Failed to rename directory: %s to %s (%s)', $sourcePath->toString(), $destPath->toString(), \is_string($stderr) ? $stderr : 'unknown error')));
            }
        }

        /** @var Result<Unit, InvalidFileType|DirectoryNotFound|RenameFailed|PermissionDenied> */
        return Result::ok(Unit::new());
    }

    /**
     * Create a directory
     *
     * Creates a new directory at the specified path with default permissions (0777).
     * The directory must not already exist. This method does not create parent directories.
     *
     * @param string|Path $path The path for the new directory
     * @return Result<Unit, CreateFailed|AlreadyExists> A Result indicating success or an error
     */
    public static function createDir(string | Path $path): Result
    {
        $pathObj = \is_string($path) ? Path::of($path) : $path;

        if ($pathObj->isDir()) {
            /** @var Result<Unit, CreateFailed|AlreadyExists> */
            return Result::err(new AlreadyExists(\sprintf('Directory already exists: %s', $pathObj->toString())));
        }

        $result = @\mkdir($pathObj->toString(), 0777, false);

        if ($result === false) {
            /** @var Result<Unit, CreateFailed|AlreadyExists> */
            return Result::err(new CreateFailed(\sprintf('Failed to create directory: %s', $pathObj->toString())));
        }

        /** @var Result<Unit, CreateFailed|AlreadyExists> */
        return Result::ok(Unit::new());
    }

    /**
     * Recursively create a directory and all parent directories
     *
     * Creates a directory and all of its parent components if they are missing.
     * This is equivalent to `mkdir -p` on Unix systems.
     *
     * If this function returns an error, some of the parent directories
     * might have been created already, resulting in a partial directory structure.
     *
     * @param string|Path $path The path for the new directory
     * @return Result<Unit, CreateFailed> A Result indicating success or an error
     */
    public static function createDirAll(string | Path $path): Result
    {
        $path = \is_string($path) ? Path::of($path) : $path;

        $results = $path
            ->ancestors()
            ->insertAt(0, Option::some($path))
            ->reverse()
            ->filter(static fn(Option $ancestor) => $ancestor->isSome() && ($ancestor->unwrap()->isDir() === false || $ancestor->unwrap()->exists() === false))
            ->map(static fn(Option $ancestor) => self::createDir($ancestor->unwrap()));

        foreach ($results->iter() as $created) {
            if ($created->isErr()) {
                /** @var Result<Unit, CreateFailed> */
                return $created;
            }
        }

        /** @var Result<Unit, CreateFailed> */
        return Result::ok(Unit::new());
    }

    /**
     * Remove a directory
     *
     * Removes the specified directory if it exists and is empty.
     * Returns an error if the directory is not empty or cannot be removed.
     *
     * @param string|Path $path The directory to remove
     * @return Result<Unit, RemoveFailed|DirectoryNotEmpty|DirectoryNotFound|InvalidFileType> A Result indicating success or an error
     */
    public static function removeDir(string | Path $path): Result
    {
        $pathObj = \is_string($path) ? Path::of($path) : $path;

        if (!$pathObj->exists()) {
            /** @var Result<Unit, RemoveFailed|DirectoryNotEmpty|DirectoryNotFound|InvalidFileType> */
            return Result::err(new DirectoryNotFound(\sprintf('Directory does not exist: %s', $pathObj->toString())));
        }

        if (!$pathObj->isDir()) {
            /** @var Result<Unit, RemoveFailed|DirectoryNotEmpty|DirectoryNotFound|InvalidFileType> */
            return Result::err(new InvalidFileType(\sprintf('Path is not a directory: %s', $pathObj->toString())));
        }

        // Check if the directory is empty (including hidden files)
        $dirContent = \glob($pathObj->toString() . '/{*,.[!.]*,..?*}', \GLOB_BRACE | \GLOB_NOSORT);

        if ($dirContent === false) {
            /** @var Result<Unit, RemoveFailed|DirectoryNotEmpty|DirectoryNotFound|InvalidFileType> */
            return Result::err(new RemoveFailed(\sprintf('Failed to check directory contents: %s', $pathObj->toString())));
        }

        if (!empty($dirContent)) {
            /** @var Result<Unit, RemoveFailed|DirectoryNotEmpty|DirectoryNotFound|InvalidFileType> */
            return Result::err(new DirectoryNotEmpty(\sprintf('Directory is not empty: %s', $pathObj->toString())));
        }

        $result = @\rmdir($pathObj->toString());

        if ($result === false) {
            /** @var Result<Unit, RemoveFailed|DirectoryNotEmpty|DirectoryNotFound|InvalidFileType> */
            return Result::err(new RemoveFailed(\sprintf('Failed to remove directory: %s', $pathObj->toString())));
        }

        /** @var Result<Unit, RemoveFailed|DirectoryNotEmpty|DirectoryNotFound|InvalidFileType> */
        return Result::ok(Unit::new());
    }

    /**
     * Remove a directory and all its contents
     *
     * Recursively removes a directory and all its contents, including subdirectories.
     * This is equivalent to `rm -rf` on Unix systems.
     *
     * This operation is irreversible and should be used with extreme caution.
     * If this method fails during the removal process due to permissions or other issues,
     * it may result in partial removal of the directory structure.
     *
     * The target directory must exist and be a directory.
     *
     * @param string|Path $path The directory to remove
     * @return Result<Unit, DirectoryNotFound|RemoveFailed|InvalidFileType|PermissionDenied> A Result indicating success or an error
     */
    public static function removeDirAll(string | Path $path): Result
    {
        $path = \is_string($path) ? Path::of($path) : $path;

        if (!$path->exists()) {
            /** @var Result<Unit, DirectoryNotFound|RemoveFailed|InvalidFileType|PermissionDenied> */
            return Result::err(new DirectoryNotFound(\sprintf('Directory does not exist: %s', $path->toString())));
        }

        if (!$path->isDir()) {
            /** @var Result<Unit, DirectoryNotFound|RemoveFailed|InvalidFileType|PermissionDenied> */
            return Result::err(new InvalidFileType(\sprintf('Path is not a directory: %s', $path->toString())));
        }

        $dir = @\opendir($path->toString());

        if ($dir === false) {
            /** @var Result<Unit, DirectoryNotFound|RemoveFailed|InvalidFileType|PermissionDenied> */
            return Result::err(new RemoveFailed(\sprintf('Failed to open directory: %s', $path->toString())));
        }

        while (false !== ($file = \readdir($dir))) {
            if (($file !== '.') && ($file !== '..')) {
                $full = $path->toString() . '/' . $file;

                if (\is_dir($full)) {
                    // Recursively remove subdirectories
                    $subDirResult = self::removeDirAll(Path::of($full));

                    if ($subDirResult->isErr()) {
                        \closedir($dir);

                        /** @var Result<Unit, DirectoryNotFound|RemoveFailed|InvalidFileType|PermissionDenied> */
                        return $subDirResult;
                    }
                } else {
                    $result = self::removeFile($full);

                    if ($result->isErr()) {
                        \closedir($dir);

                        /** @var Result<Unit, DirectoryNotFound|RemoveFailed|InvalidFileType|PermissionDenied> */
                        return $result;
                    }
                }
            }
        }

        \closedir($dir);

        $result = @\rmdir($path->toString());

        if ($result === false) {
            /** @var Result<Unit, DirectoryNotFound|RemoveFailed|InvalidFileType|PermissionDenied> */
            return Result::err(new RemoveFailed(\sprintf('Failed to remove directory: %s', $path->toString())));
        }

        /** @var Result<Unit, DirectoryNotFound|RemoveFailed|InvalidFileType|PermissionDenied> */
        return Result::ok(Unit::new());
    }

    /**
     * Removes a file at the specified path.
     *
     * The file must exist and be a file.
     *
     * @param string|Path $path The path to the file to remove
     * @return Result<Unit, RemoveFailed|PermissionDenied|InvalidFileType|FileNotFound>
     */
    public static function removeFile(string | Path $path): Result
    {
        $path = $path instanceof Path ? $path : Path::of($path);

        if (!$path->exists()) {
            /** @var Result<Unit, RemoveFailed|PermissionDenied|InvalidFileType|FileNotFound> */
            return Result::err(new FileNotFound(\sprintf('File does not exist: %s', $path)));
        }

        if (!$path->isFile()) {
            /** @var Result<Unit, RemoveFailed|PermissionDenied|InvalidFileType|FileNotFound> */
            return Result::err(new InvalidFileType(\sprintf('Path is not a file: %s', $path)));
        }

        if (!\is_writable($path->toString())) {
            /** @var Result<Unit, RemoveFailed|PermissionDenied|InvalidFileType|FileNotFound> */
            return Result::err(new PermissionDenied(\sprintf('Permission denied to remove file: %s', $path)));
        }

        $result = @\unlink($path->toString());

        if ($result === false) {
            /** @var Result<Unit, RemoveFailed|PermissionDenied|InvalidFileType|FileNotFound> */
            return Result::err(new RemoveFailed(\sprintf('Failed to remove file: %s', $path)));
        }

        /** @var Result<Unit, RemoveFailed|PermissionDenied|InvalidFileType|FileNotFound> */
        return Result::ok(Unit::new());
    }

    /**
     * Create a hard link
     *
     * Creates a hard link at the destination pointing to the source file.
     * Hard links share the same inode as the original file and must be on the same filesystem.
     *
     * It may eventually work with directory, but on linux, hardlinking to a directory is not permitted.
     *
     * @param string|Path $source The source file path
     * @param string|Path $dest The destination link path
     * @return Result<Unit, FileNotFound|AlreadyExists|LinkFailed> A Result indicating success or an error
     */
    public static function hardLink(string | Path $source, string | Path $dest): Result
    {
        $sourcePath = $source instanceof Path ? $source : Path::of($source);
        $destPath = $dest instanceof Path ? $dest : Path::of($dest);

        if (!$sourcePath->exists()) {
            /** @var Result<Unit, FileNotFound|AlreadyExists|LinkFailed> */
            return Result::err(new FileNotFound(\sprintf('File not found: %s', $sourcePath->toString())));
        }

        if ($destPath->exists()) {
            /** @var Result<Unit, FileNotFound|AlreadyExists|LinkFailed> */
            return Result::err(new AlreadyExists(\sprintf('Destination file already exists: %s', $destPath->toString())));
        }

        $result = @\link($sourcePath->toString(), $destPath->toString());

        if ($result === false) {
            /** @var Result<Unit, FileNotFound|AlreadyExists|LinkFailed> */
            return Result::err(new LinkFailed(\sprintf('Failed to create hard link: %s to %s', $sourcePath, $destPath)));
        }

        /** @var Result<Unit, FileNotFound|AlreadyExists|LinkFailed> */
        return Result::ok(Unit::new());
    }

    /**
     * Create a symbolic link
     *
     * Creates a symbolic link (symlink) at the destination pointing to the source.
     * Unlike hard links, symbolic links can point to files or directories and can cross filesystems.
     * Symbolic links become invalid (broken) if the target is moved or deleted.
     *
     * The source path must exist and be a file, the destination path must not exist.
     *
     * @param string|Path $source The target path that the link will point to
     * @param string|Path $dest The path where the symbolic link will be created
     * @return Result<Unit, SymlinkFailed|FileNotFound|AlreadyExists> A Result indicating success or an error
     */
    public static function symLink(string | Path $source, string | Path $dest): Result
    {
        $sourcePath = \is_string($source) ? Path::of($source) : $source;
        $destPath = \is_string($dest) ? Path::of($dest) : $dest;

        if (!$sourcePath->exists()) {
            /** @var Result<Unit, SymlinkFailed|FileNotFound|AlreadyExists> */
            return Result::err(new FileNotFound(\sprintf('Source path "%s" does not exist', $sourcePath->toString())));
        }

        if ($destPath->exists()) {
            /** @var Result<Unit, SymlinkFailed|FileNotFound|AlreadyExists> */
            return Result::err(new AlreadyExists(\sprintf('Destination path "%s" already exists', $destPath->toString())));
        }

        $result = @\symlink($sourcePath->toString(), $destPath->toString());

        if ($result === false) {
            /** @var Result<Unit, SymlinkFailed|FileNotFound|AlreadyExists> */
            return Result::err(new SymlinkFailed(\sprintf('Failed to create soft link: %s to %s', $sourcePath->toString(), $destPath->toString())));
        }

        /** @var Result<Unit, SymlinkFailed|FileNotFound|AlreadyExists> */
        return Result::ok(Unit::new());
    }

    /**
     * Set permissions on a path
     *
     * Applies the specified permissions to a file or directory.
     * This can be used to change read, write, and execute permissions.
     *
     * @param string|Path $path The path to set permissions on
     * @param Permissions $permissions The permissions to apply
     * @return Result<Unit, PermissionDenied> A Result indicating success or an error
     */
    public static function setPermissions(string | Path $path, Permissions $permissions): Result
    {
        $pathObj = \is_string($path) ? Path::of($path) : $path;

        $result = $permissions->apply($pathObj);

        if ($result->isErr()) {
            /** @var Result<Unit, PermissionDenied> */
            return $result;
        }

        /** @var Result<Unit, PermissionDenied> */
        return Result::ok(Unit::new());
    }
}
