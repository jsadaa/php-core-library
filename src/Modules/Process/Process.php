<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process;

use Jsadaa\PhpCoreLibrary\Modules\Option\Option;
use Jsadaa\PhpCoreLibrary\Modules\Process\Error\InvalidPid;
use Jsadaa\PhpCoreLibrary\Modules\Process\Error\ProcessSignalFailed;
use Jsadaa\PhpCoreLibrary\Modules\Process\Error\ProcessTimeout;
use Jsadaa\PhpCoreLibrary\Modules\Process\Error\StreamReadFailed;
use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Modules\Time\Duration;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\TimeOverflow;
use Jsadaa\PhpCoreLibrary\Modules\Time\SystemTime;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use Jsadaa\PhpCoreLibrary\Primitives\Unit;

/**
 * Represents a running or finished process.
 *
 * This class wraps mutable OS resources (process handle, pipes) and is
 * intentionally NOT marked @psalm-immutable.
 */
final class Process
{
    /** @var resource */
    private $handle;
    /** @var array<int, resource> */
    private array $pipes;
    private SystemTime $startTime;

    /**
     * @param resource $handle
     * @param array<int, resource> $pipes
     */
    private function __construct(
        $handle,
        array $pipes,
        SystemTime $startTime,
    ) {
        $this->handle = $handle;
        $this->pipes = $pipes;
        $this->startTime = $startTime;
    }

    /**
     * @param resource $handle
     * @param array<int, resource> $pipes
     */
    public static function fromHandle($handle, array $pipes): self
    {
        return new self($handle, $pipes, SystemTime::now());
    }

    public function status(): Status
    {
        return Status::of($this->handle);
    }

    public function isRunning(): bool
    {
        return $this->status()->isRunning();
    }

    /**
     * @return Result<Integer, InvalidPid>
     */
    public function pid(): Result
    {
        $pid = $this->status()->pid();

        if ($pid->gt(0)) {
            /** @var Result<Integer, InvalidPid> */
            return Result::ok($pid);
        }

        /** @var Result<Integer, InvalidPid> */
        return Result::err(new InvalidPid());
    }

    /**
     * Waits for the process to finish with optional timeout.
     *
     * @return Result<Status, ProcessTimeout|TimeOverflow>
     */
    public function wait(?Duration $timeout = null): Result
    {
        $deadline = $timeout !== null
            ? SystemTime::now()->add($timeout)
            : null;

        if ($deadline !== null && $deadline->isErr()) {
            /** @var Result<Status, ProcessTimeout|TimeOverflow> */
            return $deadline;
        }

        while ($this->isRunning()) {
            if ($deadline !== null) {
                $now = SystemTime::now();

                if ($now->ge($deadline->unwrap())) {
                    /** @var Result<Status, ProcessTimeout|TimeOverflow> */
                    return Result::err(new ProcessTimeout('Process wait timed out'));
                }
            }

            \usleep(10000); // 10ms
        }

        /** @var Result<Status, ProcessTimeout|TimeOverflow> */
        return Result::ok($this->status());
    }

    /**
     * Kills the process with the specified signal.
     *
     * @return Result<Unit, ProcessSignalFailed>
     */
    public function kill(int $signal = \SIGTERM): Result
    {
        if (!$this->isRunning()) {
            /** @var Result<Unit, ProcessSignalFailed> */
            return Result::ok(Unit::new());
        }

        $result = \proc_terminate($this->handle, $signal);

        if ($result) {
            /** @var Result<Unit, ProcessSignalFailed> */
            return Result::ok(Unit::new());
        }

        /** @var Result<Unit, ProcessSignalFailed> */
        return Result::err(new ProcessSignalFailed());
    }

    /**
     * @return Option<resource>
     */
    public function stdin(): Option
    {
        return $this->getPipe(0);
    }

    /**
     * @return Option<resource>
     */
    public function stdout(): Option
    {
        return $this->getPipe(1);
    }

    /**
     * @return Option<resource>
     */
    public function stderr(): Option
    {
        return $this->getPipe(2);
    }

    /**
     * Gets a writer for stdin.
     *
     * @return Result<StreamWriter, StreamReadFailed>
     */
    public function stdinWriter(): Result
    {
        $stdin = $this->stdin();

        if ($stdin->isSome()) {
            /** @var Result<StreamWriter, StreamReadFailed> */
            return Result::ok(StreamWriter::createAutoFlushing($stdin->unwrap()));
        }

        /** @var Result<StreamWriter, StreamReadFailed> */
        return Result::err(new StreamReadFailed('Failed to get stdin'));
    }

    /**
     * Writes data to the process's stdin using StreamWriter.
     *
     * @return Result<Integer, StreamReadFailed|Error\StreamWriteFailed>
     */
    public function writeStdin(string | Str $data): Result
    {
        $writer = $this->stdinWriter();

        if ($writer->isErr()) {
            /** @var Result<Integer, StreamReadFailed|Error\StreamWriteFailed> */
            return Result::err($writer->unwrapErr());
        }

        /** @var Result<Integer, StreamReadFailed|Error\StreamWriteFailed> */
        return $writer->unwrap()->write($data);
    }

    /**
     * Reads all available data from stdout.
     *
     * @return Result<Str, StreamReadFailed>
     */
    public function readStdout(): Result
    {
        $stdout = $this->stdout();

        if ($stdout->isNone()) {
            /** @var Result<Str, StreamReadFailed> */
            return Result::err(new StreamReadFailed('Failed to get stdout'));
        }

        \stream_set_blocking($stdout->unwrap(), false);
        $data = \stream_get_contents($stdout->unwrap());

        if ($data !== false) {
            /** @var Result<Str, StreamReadFailed> */
            return Result::ok(Str::of($data));
        }

        /** @var Result<Str, StreamReadFailed> */
        return Result::err(new StreamReadFailed());
    }

    /**
     * Reads all available data from stderr.
     *
     * @return Result<Str, StreamReadFailed>
     */
    public function readStderr(): Result
    {
        $stderr = $this->stderr();

        if ($stderr->isNone()) {
            /** @var Result<Str, StreamReadFailed> */
            return Result::err(new StreamReadFailed('Failed to get stderr'));
        }

        \stream_set_blocking($stderr->unwrap(), false);
        $data = \stream_get_contents($stderr->unwrap());

        if ($data !== false) {
            /** @var Result<Str, StreamReadFailed> */
            return Result::ok(Str::of($data));
        }

        /** @var Result<Str, StreamReadFailed> */
        return Result::err(new StreamReadFailed());
    }

    /**
     * Collects all output from the process using stream_select for efficiency.
     *
     * @return Result<Output, ProcessTimeout|StreamReadFailed|TimeOverflow>
     */
    public function output(Duration $timeout): Result
    {
        $stdinResult = $this->stdin();

        if ($stdinResult->isSome()) {
            $stdinStream = $stdinResult->unwrap();
            \fclose($stdinStream);
        }

        $stdoutResult = $this->stdout();
        $stderrResult = $this->stderr();

        if ($stdoutResult->isNone()) {
            /** @var Result<Output, ProcessTimeout|StreamReadFailed|TimeOverflow> */
            return Result::err(new StreamReadFailed('Failed to get stdout'));
        }

        if ($stderrResult->isNone()) {
            /** @var Result<Output, ProcessTimeout|StreamReadFailed|TimeOverflow> */
            return Result::err(new StreamReadFailed('Failed to get stderr'));
        }

        $stdoutStream = $stdoutResult->unwrap();
        $stderrStream = $stderrResult->unwrap();

        \stream_set_blocking($stdoutStream, false);
        \stream_set_blocking($stderrStream, false);

        $deadline = SystemTime::now()->add($timeout);

        if ($deadline->isErr()) {
            /** @var Result<Output, ProcessTimeout|StreamReadFailed|TimeOverflow> */
            return $deadline;
        }

        $stdout = '';
        $stderr = '';

        while (true) {
            if (SystemTime::now()->ge($deadline->unwrap())) {
                $this->kill(\SIGKILL);

                /** @var Result<Output, ProcessTimeout|StreamReadFailed|TimeOverflow> */
                return Result::err(new ProcessTimeout());
            }

            $read = [];

            if (!\feof($stdoutStream)) {
                $read[] = $stdoutStream;
            }

            if (!\feof($stderrStream)) {
                $read[] = $stderrStream;
            }

            if ($read === [] && !$this->isRunning()) {
                break;
            }

            if ($read !== []) {
                $write = null;
                $except = null;
                $changed = @\stream_select($read, $write, $except, 0, 50000); // 50ms

                if ($changed !== false && $changed > 0) {
                    foreach ($read as $stream) {
                        $data = \fread($stream, 8192);

                        if ($data !== false && $data !== '') {
                            if ($stream === $stdoutStream) {
                                $stdout .= $data;
                            } else {
                                $stderr .= $data;
                            }
                        }
                    }
                }
            } else {
                \usleep(10000); // 10ms - process still running but streams at EOF
            }
        }

        // Final read for any remaining data
        $remaining = \stream_get_contents($stdoutStream);

        if ($remaining !== false) {
            $stdout .= $remaining;
        }

        \fclose($stdoutStream);

        $remaining = \stream_get_contents($stderrStream);

        if ($remaining !== false) {
            $stderr .= $remaining;
        }

        \fclose($stderrStream);

        /** @var Result<Output, ProcessTimeout|StreamReadFailed|TimeOverflow> */
        return Result::ok(Output::of(
            Str::of($stdout),
            Str::of($stderr),
            $this->status(),
        ));
    }

    /**
     * Closes all pipes and the process handle.
     */
    public function close(): void
    {
        foreach ($this->pipes as $pipe) {
            /** @psalm-suppress RedundantConditionGivenDocblockType */
            if (\is_resource($pipe)) {
                \fclose($pipe);
            }
        }

        /** @psalm-suppress RedundantConditionGivenDocblockType */
        if (\is_resource($this->handle)) {
            \proc_close($this->handle);
        }
    }

    /**
     * @return Option<resource>
     */
    private function getPipe(int $fd): Option
    {
        if (isset($this->pipes[$fd])) {
            /** @var Option<resource> */
            return Option::some($this->pipes[$fd]);
        }

        /** @var Option<resource> */
        return Option::none();
    }
}
