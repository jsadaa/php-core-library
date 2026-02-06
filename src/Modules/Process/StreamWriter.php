<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use Jsadaa\PhpCoreLibrary\Modules\Process\Error\StreamFlushFailed;
use Jsadaa\PhpCoreLibrary\Modules\Process\Error\StreamWriteFailed;
use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;

/**
 * Stream writer with buffering and flush control.
 */
final class StreamWriter
{
    /** @var resource */
    private $stream;
    private Integer $bufferSize;
    private bool $autoFlush;
    private Str $lineEnding;

    /**
     * @param resource $stream
     */
    private function __construct(
        $stream,
        Integer $bufferSize,
        bool $autoFlush,
        Str $lineEnding,
    ) {
        $this->stream = $stream;
        $this->bufferSize = $bufferSize;
        $this->autoFlush = $autoFlush;
        $this->lineEnding = $lineEnding;
    }

    /**
     * @param resource $stream
     */
    public static function from($stream): self
    {
        return new self(
            $stream,
            Integer::of(8192),
            false,
            Str::of(\PHP_EOL),
        );
    }

    /**
     * Creates a writer with automatic flushing after each write.
     *
     * @param resource $stream
     */
    public static function createAutoFlushing($stream): self
    {
        return new self(
            $stream,
            Integer::of(8192),
            true,
            Str::of(\PHP_EOL),
        );
    }

    public function withBufferSize(int | Integer $size): self
    {
        return new self(
            $this->stream,
            \is_int($size) ? Integer::of($size) : $size,
            $this->autoFlush,
            $this->lineEnding,
        );
    }

    public function withLineEnding(string | Str $ending): self
    {
        return new self(
            $this->stream,
            $this->bufferSize,
            $this->autoFlush,
            \is_string($ending) ? Str::of($ending) : $ending,
        );
    }

    public function withAutoFlush(bool $enabled = true): self
    {
        return new self(
            $this->stream,
            $this->bufferSize,
            $enabled,
            $this->lineEnding,
        );
    }

    /**
     * Writes data to the stream.
     *
     * @return Result<Integer, StreamWriteFailed>
     */
    public function write(string | Str $data): Result
    {
        $dataStr = \is_string($data) ? $data : $data->toString();

        if (\strlen($dataStr) === 0) {
            /** @var Result<Integer, StreamWriteFailed> */
            return Result::ok(Integer::of(0));
        }

        $written = @\fwrite($this->stream, $dataStr);

        if ($written === false) {
            /** @var Result<Integer, StreamWriteFailed> */
            return Result::err(new StreamWriteFailed());
        }

        if ($this->autoFlush) {
            $this->flush();
        }

        /** @var Result<Integer, StreamWriteFailed> */
        return Result::ok(Integer::of($written));
    }

    /**
     * Writes a line to the stream (appends line ending).
     *
     * @return Result<Integer, StreamWriteFailed>
     */
    public function writeLine(string | Str $line): Result
    {
        $line = \is_string($line) ? Str::of($line) : $line;

        return $this->write($line->append($this->lineEnding));
    }

    /**
     * Writes multiple lines to the stream.
     *
     * @param Sequence<Str>|array<string> $lines
     * @return Result<Integer, StreamWriteFailed>
     */
    public function writeLines($lines): Result
    {
        $sequence = $lines instanceof Sequence
            ? $lines
            : Sequence::of(...\array_map(static fn($l) => Str::of($l), $lines));

        $totalWritten = Integer::of(0);

        foreach ($sequence->iter() as $line) {
            $result = $this->writeLine($line);

            if ($result->isErr()) {
                /** @var Result<Integer, StreamWriteFailed> */
                return $result;
            }

            $totalWritten = $totalWritten->add($result->unwrap());
        }

        /** @var Result<Integer, StreamWriteFailed> */
        return Result::ok($totalWritten);
    }

    /**
     * Writes data in chunks to avoid memory issues with large data.
     *
     * @return Result<Integer, StreamWriteFailed>
     */
    public function writeChunked(string | Str $data): Result
    {
        $dataStr = \is_string($data) ? $data : $data->toString();
        $totalWritten = Integer::of(0);
        $offset = 0;
        $length = \strlen($dataStr);
        $chunkSize = $this->bufferSize->toInt();

        while ($offset < $length) {
            $chunk = \substr($dataStr, $offset, $chunkSize);
            $result = $this->write($chunk);

            if ($result->isErr()) {
                /** @var Result<Integer, StreamWriteFailed> */
                return $result;
            }

            $totalWritten = $totalWritten->add($result->unwrap());
            $offset += $chunkSize;
        }

        /** @var Result<Integer, StreamWriteFailed> */
        return Result::ok($totalWritten);
    }

    /**
     * Flushes the stream buffer.
     *
     * @return Result<null, StreamFlushFailed>
     */
    public function flush(): Result
    {
        $result = @\fflush($this->stream);

        if ($result !== false) {
            /** @var Result<null, StreamFlushFailed> */
            return Result::ok(null);
        }

        /** @var Result<null, StreamFlushFailed> */
        return Result::err(new StreamFlushFailed());
    }
}
