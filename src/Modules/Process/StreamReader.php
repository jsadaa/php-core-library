<?php

declare(strict_types=1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process;

use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Modules\Time\Duration;
use Jsadaa\PhpCoreLibrary\Modules\Time\SystemTime;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;

/**
 * Non-blocking stream reader with timeout support.
 *
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
    public static function from($stream, int|Integer $bufferSize = 8192): self
    {
        return new self(
            $stream,
            is_int($bufferSize) ? Integer::of($bufferSize) : $bufferSize
        );
    }

    /**
     * Reads all available data without blocking.
     *
     * @return Result<Str, string>
     */
    public function readAvailable(): Result
    {
        stream_set_blocking($this->stream, false);

        $data = fread($this->stream, $this->bufferSize->toInt());

        if ($data === false) {
            /** @var Result<Str, string> $err */
            $err = Result::err("Failed to read from stream");
            return $err;
        }

        /** @var Result<Str, string> $ok */
        $ok = Result::ok(Str::of($data));
        return $ok;
    }

    /**
     * Reads until EOF or timeout.
     *
     * @return Result<Str, string>
     */
    public function readAll(Duration $timeout): Result
    {
        $deadline = SystemTime::now()->add($timeout)->unwrap();
        $result = Str::new();

        stream_set_blocking($this->stream, false);

        while (true) {
            if (SystemTime::now()->ge($deadline)) {
                /** @var Result<Str, string> $err */
                $err = Result::err("Read timeout exceeded");
                return $err;
            }

            if (feof($this->stream)) {
                break;
            }

            $data = fread($this->stream, $this->bufferSize->toInt());

            if ($data === false) {
                /** @var Result<Str, string> $err */
                $err = Result::err("Failed to read from stream");
                return $err;
            }

            if ($data !== '') {
                $result = $result->append($data);
            } else {
                // No data available, wait a bit
                usleep(1000); // 1ms
            }
        }

        /** @var Result<Str, string> $ok */
        $ok = Result::ok($result);
        return $ok;
    }

    /**
     * Reads until a delimiter is found or timeout.
     *
     * @return Result<Str, string>
     */
    public function readUntil(string|Str $delimiter, Duration $timeout): Result
    {
        $delimiter = is_string($delimiter) ? $delimiter : $delimiter->toString();
        $deadline = SystemTime::now()->add($timeout)->unwrap();
        $result = Str::new();

        stream_set_blocking($this->stream, false);

        while (true) {
            if (SystemTime::now()->ge($deadline)) {
                /** @var Result<Str, string> $err */
                $err = Result::err("Read timeout exceeded");
                return $err;
            }

            $char = fgetc($this->stream);

            if ($char === false) {
                if (feof($this->stream)) {
                    break;
                }
                usleep(1000); // 1ms
                continue;
            }

            $result = $result->append($char);

            if (str_ends_with($result->toString(), $delimiter)) {
                break;
            }
        }

        /** @var Result<Str, string> $ok */
        $ok = Result::ok($result);
        return $ok;
    }

    /**
     * Checks if the stream has reached EOF.
     */
    public function isEof(): bool
    {
        return feof($this->stream);
    }

    /**
     * Closes the stream.
     */
    public function close(): void
    {
        // Suppress invalid property assignment because we can't update the readonly property
        // but the underlying resource state changes, which Psalm tracks.
        // We accept that the property will hold a closed resource.
        /** @psalm-suppress InvalidPropertyAssignmentValue */
        fclose($this->stream);
    }
}
