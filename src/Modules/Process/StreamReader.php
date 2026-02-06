<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process;

use Jsadaa\PhpCoreLibrary\Modules\Process\Error\ProcessTimeout;
use Jsadaa\PhpCoreLibrary\Modules\Process\Error\StreamReadFailed;
use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Modules\Time\Duration;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\TimeOverflow;
use Jsadaa\PhpCoreLibrary\Modules\Time\SystemTime;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;

/**
 * Non-blocking stream reader with timeout support.
 */
final class StreamReader
{
    /** @var resource */
    private $stream;
    private Integer $bufferSize;

    /**
     * @param resource $stream
     */
    private function __construct($stream, Integer $bufferSize)
    {
        $this->stream = $stream;
        $this->bufferSize = $bufferSize;
    }

    /**
     * @param resource $stream
     */
    public static function from($stream, int | Integer $bufferSize = 8192): self
    {
        return new self(
            $stream,
            \is_int($bufferSize) ? Integer::of($bufferSize) : $bufferSize,
        );
    }

    /**
     * Reads all available data without blocking.
     *
     * @return Result<Str, StreamReadFailed>
     */
    public function readAvailable(): Result
    {
        \stream_set_blocking($this->stream, false);

        $data = \fread($this->stream, $this->bufferSize->toInt());

        if ($data === false) {
            /** @var Result<Str, StreamReadFailed> */
            return Result::err(new StreamReadFailed());
        }

        /** @var Result<Str, StreamReadFailed> */
        return Result::ok(Str::of($data));
    }

    /**
     * Reads until EOF or timeout using stream_select for efficiency.
     *
     * @return Result<Str, StreamReadFailed|ProcessTimeout|TimeOverflow>
     */
    public function readAll(Duration $timeout): Result
    {
        $deadline = SystemTime::now()->add($timeout);

        if ($deadline->isErr()) {
            /** @var Result<Str, StreamReadFailed|ProcessTimeout|TimeOverflow> */
            return $deadline;
        }

        $result = '';

        \stream_set_blocking($this->stream, false);

        while (true) {
            if (SystemTime::now()->ge($deadline->unwrap())) {
                /** @var Result<Str, StreamReadFailed|ProcessTimeout|TimeOverflow> */
                return Result::err(new ProcessTimeout('Read timeout exceeded'));
            }

            if (\feof($this->stream)) {
                break;
            }

            $read = [$this->stream];
            $write = null;
            $except = null;
            $changed = @\stream_select($read, $write, $except, 0, 50000); // 50ms

            if ($changed !== false && $changed > 0) {
                $data = \fread($this->stream, $this->bufferSize->toInt());

                if ($data === false) {
                    /** @var Result<Str, StreamReadFailed|ProcessTimeout|TimeOverflow> */
                    return Result::err(new StreamReadFailed());
                }

                $result .= $data;
            }
        }

        /** @var Result<Str, StreamReadFailed|ProcessTimeout|TimeOverflow> */
        return Result::ok(Str::of($result));
    }

    /**
     * Reads until a delimiter is found or timeout.
     *
     * @return Result<Str, StreamReadFailed|ProcessTimeout|TimeOverflow>
     */
    public function readUntil(string | Str $delimiter, Duration $timeout): Result
    {
        $delimiter = \is_string($delimiter) ? $delimiter : $delimiter->toString();
        $deadline = SystemTime::now()->add($timeout);

        if ($deadline->isErr()) {
            /** @var Result<Str, StreamReadFailed|ProcessTimeout|TimeOverflow> */
            return $deadline;
        }

        $result = '';

        \stream_set_blocking($this->stream, false);

        while (true) {
            if (SystemTime::now()->ge($deadline->unwrap())) {
                /** @var Result<Str, StreamReadFailed|ProcessTimeout|TimeOverflow> */
                return Result::err(new ProcessTimeout('Read timeout exceeded'));
            }

            $read = [$this->stream];
            $write = null;
            $except = null;
            $changed = @\stream_select($read, $write, $except, 0, 50000); // 50ms

            if ($changed !== false && $changed > 0) {
                $char = \fgetc($this->stream);

                if ($char === false) {
                    if (\feof($this->stream)) {
                        break;
                    }

                    continue;
                }

                $result .= $char;

                if (\str_ends_with($result, $delimiter)) {
                    break;
                }
            }
        }

        /** @var Result<Str, StreamReadFailed|ProcessTimeout|TimeOverflow> */
        return Result::ok(Str::of($result));
    }

    /**
     * Checks if the stream has reached EOF.
     */
    public function isEof(): bool
    {
        return \feof($this->stream);
    }

    /**
     * Closes the stream.
     */
    public function close(): void
    {
        /**
         * @psalm-suppress RedundantConditionGivenDocblockType
         * @psalm-suppress InvalidPropertyAssignmentValue
         */
        if (\is_resource($this->stream)) {
            \fclose($this->stream);
        }
    }
}
