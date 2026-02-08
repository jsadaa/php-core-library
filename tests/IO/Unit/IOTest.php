<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\IO\Unit;

use Jsadaa\PhpCoreLibrary\Modules\IO\Error\ReadFailed;
use Jsadaa\PhpCoreLibrary\Modules\IO\Error\WriteFailed;
use Jsadaa\PhpCoreLibrary\Modules\IO\IO;
use Jsadaa\PhpCoreLibrary\Primitives\Unit;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the IO module.
 *
 * Note: fwrite(STDOUT) bypasses PHP's output buffering, so stdout content
 * is verified via a subprocess. Return types and formatting are tested directly.
 */
final class IOTest extends TestCase
{
    // --- println ---

    public function testPrintlnReturnsOkWithUnit(): void
    {
        $this->runInSubprocess(
            'IO::println("Hello");',
            function(string $stdout): void {
                $this->assertSame('Hello' . \PHP_EOL, $stdout);
            },
        );
    }

    public function testPrintlnWithStrMessage(): void
    {
        $this->runInSubprocess(
            'IO::println(Str::of("Hello Str"));',
            function(string $stdout): void {
                $this->assertSame('Hello Str' . \PHP_EOL, $stdout);
            },
        );
    }

    public function testPrintlnWithFormatArgs(): void
    {
        $this->runInSubprocess(
            'IO::println("Hello, {}!", "world");',
            function(string $stdout): void {
                $this->assertSame('Hello, world!' . \PHP_EOL, $stdout);
            },
        );
    }

    public function testPrintlnWithMultipleFormatArgs(): void
    {
        $this->runInSubprocess(
            'IO::println("{} + {} = {}", 1, 2, 3);',
            function(string $stdout): void {
                $this->assertSame('1 + 2 = 3' . \PHP_EOL, $stdout);
            },
        );
    }

    public function testPrintlnWithNamedArgs(): void
    {
        $this->runInSubprocess(
            'IO::println("Hello, {name}!", name: "Alice");',
            function(string $stdout): void {
                $this->assertSame('Hello, Alice!' . \PHP_EOL, $stdout);
            },
        );
    }

    public function testPrintlnEmptyString(): void
    {
        $this->runInSubprocess(
            'IO::println("");',
            function(string $stdout): void {
                $this->assertSame(\PHP_EOL, $stdout);
            },
        );
    }

    public function testPrintlnResultIsOk(): void
    {
        $result = IO::println('test');

        $this->assertTrue($result->isOk());
        $this->assertInstanceOf(Unit::class, $result->unwrap());
    }

    // --- print ---

    public function testPrintWithoutNewline(): void
    {
        $this->runInSubprocess(
            'IO::print("Hello");',
            function(string $stdout): void {
                $this->assertSame('Hello', $stdout);
            },
        );
    }

    public function testPrintWithFormatArgs(): void
    {
        $this->runInSubprocess(
            'IO::print("Count: {}", 42);',
            function(string $stdout): void {
                $this->assertSame('Count: 42', $stdout);
            },
        );
    }

    public function testPrintResultIsOk(): void
    {
        $result = IO::print('test');

        $this->assertTrue($result->isOk());
        $this->assertInstanceOf(Unit::class, $result->unwrap());
    }

    // --- eprintln ---

    public function testEprintlnOutputsToStderr(): void
    {
        $this->runInSubprocess(
            'IO::eprintln("Error occurred");',
            null,
            function(string $stderr): void {
                $this->assertSame('Error occurred' . \PHP_EOL, $stderr);
            },
        );
    }

    public function testEprintlnWithFormatArgs(): void
    {
        $this->runInSubprocess(
            'IO::eprintln("Error: {}", "not found");',
            null,
            function(string $stderr): void {
                $this->assertSame('Error: not found' . \PHP_EOL, $stderr);
            },
        );
    }

    public function testEprintlnResultIsOk(): void
    {
        $result = IO::eprintln('test');

        $this->assertTrue($result->isOk());
        $this->assertInstanceOf(Unit::class, $result->unwrap());
    }

    // --- eprint ---

    public function testEprintOutputsToStderrWithoutNewline(): void
    {
        $this->runInSubprocess(
            'IO::eprint("Warning");',
            null,
            function(string $stderr): void {
                $this->assertSame('Warning', $stderr);
            },
        );
    }

    public function testEprintWithNamedArgs(): void
    {
        $this->runInSubprocess(
            'IO::eprint("Level: {level}", level: "WARN");',
            null,
            function(string $stderr): void {
                $this->assertSame('Level: WARN', $stderr);
            },
        );
    }

    public function testEprintResultIsOk(): void
    {
        $result = IO::eprint('test');

        $this->assertTrue($result->isOk());
        $this->assertInstanceOf(Unit::class, $result->unwrap());
    }

    // --- Error classes ---

    public function testWriteFailedIsRuntimeException(): void
    {
        $error = new WriteFailed();

        $this->assertInstanceOf(\RuntimeException::class, $error);
        $this->assertSame('Failed to write to output stream', $error->getMessage());
    }

    public function testWriteFailedCustomMessage(): void
    {
        $error = new WriteFailed('Custom error');

        $this->assertSame('Custom error', $error->getMessage());
    }

    public function testReadFailedIsRuntimeException(): void
    {
        $error = new ReadFailed();

        $this->assertInstanceOf(\RuntimeException::class, $error);
        $this->assertSame('Failed to read from input stream', $error->getMessage());
    }

    public function testReadFailedCustomMessage(): void
    {
        $error = new ReadFailed('Custom read error');

        $this->assertSame('Custom read error', $error->getMessage());
    }

    // --- Helper ---

    /**
     * Run an IO expression in a subprocess and assert on captured stdout/stderr.
     *
     * @param string $expression PHP expression using IO:: (must include trailing semicolon)
     * @param (\Closure(string): void)|null $assertStdout Assertion on stdout content
     * @param (\Closure(string): void)|null $assertStderr Assertion on stderr content
     */
    private function runInSubprocess(string $expression, ?\Closure $assertStdout = null, ?\Closure $assertStderr = null): void
    {
        $autoload = \realpath(__DIR__ . '/../../../vendor/autoload.php');

        $script = \tempnam(\sys_get_temp_dir(), 'io_test_');
        $this->assertIsString($script);

        \file_put_contents($script, <<<PHP
            <?php
            require '{$autoload}';
            use Jsadaa\\PhpCoreLibrary\\Modules\\IO\\IO;
            use Jsadaa\\PhpCoreLibrary\\Primitives\\Str\\Str;
            {$expression}
            PHP);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = \proc_open(['php', $script], $descriptors, $pipes);
        $this->assertIsResource($process);

        \fclose($pipes[0]);
        $stdout = \stream_get_contents($pipes[1]);
        $stderr = \stream_get_contents($pipes[2]);
        \fclose($pipes[1]);
        \fclose($pipes[2]);

        $exitCode = \proc_close($process);
        @\unlink($script);
        $this->assertSame(0, $exitCode, "Subprocess failed with stderr: {$stderr}");

        if ($assertStdout !== null) {
            $this->assertIsString($stdout);
            $assertStdout($stdout);
        }

        if ($assertStderr !== null) {
            $this->assertIsString($stderr);
            $assertStderr($stderr);
        }
    }
}
