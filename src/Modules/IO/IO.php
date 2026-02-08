<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\IO;

use Jsadaa\PhpCoreLibrary\Modules\IO\Error\ReadFailed;
use Jsadaa\PhpCoreLibrary\Modules\IO\Error\WriteFailed;
use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use Jsadaa\PhpCoreLibrary\Primitives\Unit;

/**
 * Provides static methods for standard I/O operations on the current process streams.
 *
 * This module handles writing to stdout/stderr and reading from stdin.
 * All operations return Result types for type-safe error handling.
 * When format arguments are provided, the message is formatted via Str::format().
 */
final readonly class IO
{
    /**
     * Write a message to stdout followed by a newline.
     *
     * When $args are provided, the message is formatted using Str::format()
     * before output.
     *
     * @param Str|string $message The message or format template
     * @param mixed ...$args Optional format arguments (positional and/or named)
     * @return Result<Unit, WriteFailed>
     */
    public static function println(Str|string $message, mixed ...$args): Result
    {
        return self::writeToStream(\STDOUT, $message, true, ...$args);
    }

    /**
     * Write a message to stdout without trailing newline.
     *
     * @param Str|string $message The message or format template
     * @param mixed ...$args Optional format arguments (positional and/or named)
     * @return Result<Unit, WriteFailed>
     */
    public static function print(Str|string $message, mixed ...$args): Result
    {
        return self::writeToStream(\STDOUT, $message, false, ...$args);
    }

    /**
     * Write a message to stderr followed by a newline.
     *
     * @param Str|string $message The message or format template
     * @param mixed ...$args Optional format arguments (positional and/or named)
     * @return Result<Unit, WriteFailed>
     */
    public static function eprintln(Str|string $message, mixed ...$args): Result
    {
        return self::writeToStream(\STDERR, $message, true, ...$args);
    }

    /**
     * Write a message to stderr without trailing newline.
     *
     * @param Str|string $message The message or format template
     * @param mixed ...$args Optional format arguments (positional and/or named)
     * @return Result<Unit, WriteFailed>
     */
    public static function eprint(Str|string $message, mixed ...$args): Result
    {
        return self::writeToStream(\STDERR, $message, false, ...$args);
    }

    /**
     * Read a line from stdin, with an optional prompt.
     *
     * Uses readline when available, falls back to fgets(STDIN).
     * The trailing newline is stripped from the input.
     *
     * @param Str|null $prompt Optional prompt displayed before reading
     * @return Result<Str, ReadFailed>
     */
    public static function readLine(?Str $prompt = null): Result
    {
        $promptStr = $prompt?->toString();

        if (\function_exists('readline')) {
            /** @psalm-suppress ImpureFunctionCall */
            $input = \readline($promptStr ?? '');
        } else {
            if ($promptStr !== null) {
                @\fwrite(\STDOUT, $promptStr);
            }

            /** @psalm-suppress ImpureFunctionCall */
            $input = \fgets(\STDIN);
        }

        if ($input === false) {
            /** @var Result<Str, ReadFailed> */
            return Result::err(new ReadFailed());
        }

        // fgets includes trailing newline, readline does not
        if (!\function_exists('readline')) {
            $input = \rtrim($input, "\n\r");
        }

        /** @var Result<Str, ReadFailed> */
        return Result::ok(Str::of($input));
    }

    /**
     * Write formatted content to a given stream.
     *
     * @param resource $stream The output stream (STDOUT or STDERR)
     * @param Str|string $message The message or format template
     * @param bool $newline Whether to append a newline
     * @param mixed ...$args Format arguments
     * @return Result<Unit, WriteFailed>
     */
    private static function writeToStream(mixed $stream, Str|string $message, bool $newline, mixed ...$args): Result
    {
        $output = $message instanceof Str ? $message->toString() : $message;

        if (\count($args) > 0) {
            $output = Str::format($output, ...$args)->toString();
        }

        $text = $newline ? $output . \PHP_EOL : $output;

        /** @psalm-suppress ImpureFunctionCall */
        $written = @\fwrite($stream, $text);

        if ($written === false) {
            /** @var Result<Unit, WriteFailed> */
            return Result::err(new WriteFailed());
        }

        /** @var Result<Unit, WriteFailed> */
        return Result::ok(Unit::new());
    }
}
