<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Map\Map;
use Jsadaa\PhpCoreLibrary\Modules\Option\Option;
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
 * @psalm-immutable
 * @psalm-suppress ImpureFunctionCall
 * @psalm-suppress ImpureMethodCall
 * @psalm-suppress MixedReturnTypeCoercion
 * @psalm-suppress MixedArgument
 * @psalm-suppress InvalidReturnStatement
 * @psalm-suppress RedundantConditionGivenDocblockType
 */
final class Process
{
    /** @var resource */
    private $handle;
    /** @var Map<FileDescriptor, resource> */
    private Map $pipes;
    private SystemTime $startTime;

    /**
     * @param resource $handle
     * @param Map<FileDescriptor, resource> $pipes
     */
    private function __construct(
        $handle,
        Map $pipes,
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
        /** @var Map<FileDescriptor, resource> $pipeMap */
        $pipeMap = Map::new();

        foreach ($pipes as $fd => $pipe) {
            $pipeMap = $pipeMap->add(
                FileDescriptor::custom($fd),
                $pipe,
            );
        }

        return new self($handle, $pipeMap, SystemTime::now());
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
     * @return Result<Integer, string>
     */
    public function pid(): Result
    {
        $pid = $this->status()->pid();

        return $pid->gt(0)
            ? Result::ok($pid)
            : Result::err('Process has no valid PID');
    }

    /**
     * Waits for the process to finish with optional timeout.
     *
     * @return Result<Status, string|TimeOverflow>
     */
    public function wait(?Duration $timeout = null): Result
    {
        $deadline = $timeout !== null
            ? SystemTime::now()->add($timeout)
            : null;

        if ($deadline !== null && $deadline->isErr()) {
            return $deadline;
        }

        while ($this->isRunning()) {
            if ($deadline !== null) {
                $now = SystemTime::now();

                if ($now->ge($deadline->unwrap())) {
                    return Result::err('Process wait timed out');
                }
            }

            \usleep(10000); // 10ms
        }

        return Result::ok($this->status());
    }

    /**
     * Kills the process with the specified signal.
     *
     * @return Result<Unit, string>
     */
    public function kill(int $signal = \SIGTERM): Result
    {
        if (!$this->isRunning()) {
            return Result::ok(Unit::new());
        }

        $result = \proc_terminate($this->handle, $signal);

        return $result
            ? Result::ok(Unit::new())
            : Result::err('Failed to send signal to process');
    }

    /**
     * @return Option<resource>
     */
    public function stdin(): Option
    {
        return $this->pipes->get(FileDescriptor::stdin());
    }

    /**
     * @return Option<resource>
     */
    public function stdout(): Option
    {
        return $this->pipes->get(FileDescriptor::stdout());
    }

    /**
     * @return Option<resource>
     */
    public function stderr(): Option
    {
        return $this->pipes->get(FileDescriptor::stderr());
    }

    /**
     * Gets a writer for stdin.
     *
     * @return Result<StreamWriter, string>
     */
    public function stdinWriter(): Result
    {
        $stdin = $this->stdin();

        return $stdin->isSome()
            ? Result::ok(StreamWriter::createAutoFlushing($stdin->unwrap()))
            : Result::err('Failed to get stdin');
    }

    /**
     * Writes data to the process's stdin using StreamWriter.
     *
     * @return Result<Integer, string>
     */
    public function writeStdin(string | Str $data): Result
    {
        $writer = $this->stdinWriter();

        return $writer->isOk()
            ? $writer->unwrap()->write($data)
            : Result::err($writer->unwrapErr());
    }

    /**
     * Reads all available data from stdout.
     *
     * @return Result<Str, string>
     */
    public function readStdout(): Result
    {
        $stdout = $this->stdout();

        if ($stdout->isNone()) {
            return Result::err('Failed to get stdout');
        }

        \stream_set_blocking($stdout->unwrap(), false);
        $data = \stream_get_contents($stdout->unwrap());

        return $data !== false
            ? Result::ok(Str::of($data))
            : Result::err('Failed to read from stdout');
    }

    /**
     * Reads all available data from stderr.
     *
     * @return Result<Str, string>
     */
    public function readStderr(): Result
    {
        $stderr = $this->stderr();

        if ($stderr->isNone()) {
            return Result::err('Failed to get stderr');
        }

        \stream_set_blocking($stderr->unwrap(), false);
        $data = \stream_get_contents($stderr->unwrap());

        return $data !== false
            ? Result::ok(Str::of($data))
            : Result::err('Failed to read from stderr');
    }

    /**
     * Collects all output from the process.
     *
     * @return Result<Output, string|TimeOverflow>
     */
    public function output(Duration $timeout): Result
    {
        // Close stdin if available
        $stdinResult = $this->stdin();

        if ($stdinResult->isSome()) {
            $stdinRes = $stdinResult->unwrap();
            \fclose($stdinRes);
        }

        // Create readers for stdout and stderr
        $stdoutResult = $this->stdout();
        $stderrResult = $this->stderr();

        if ($stdoutResult->isNone()) {
            return Result::err('Failed to read from stdout');
        }

        if ($stderrResult->isNone()) {
            return Result::err('Failed to read from stderr');
        }

        $stdoutReader = StreamReader::from($stdoutResult->unwrap());
        $stderrReader = StreamReader::from($stderrResult->unwrap());

        $deadline = SystemTime::now()->add($timeout);

        if ($deadline->isErr()) {
            return $deadline;
        }

        $stdout = Str::new();
        $stderr = Str::new();

        while (true) {
            if (SystemTime::now()->ge($deadline->unwrap())) {
                $this->kill(\SIGKILL);

                return Result::err('Process execution timed out');
            }

            // Read available data from both streams
            $stdoutData = $stdoutReader->readAvailable();

            if ($stdoutData->isOk() && !$stdoutData->unwrap()->isEmpty()) {
                $stdout = $stdout->append($stdoutData->unwrap());
            }

            $stderrData = $stderrReader->readAvailable();

            if ($stderrData->isOk() && !$stderrData->unwrap()->isEmpty()) {
                $stderr = $stderr->append($stderrData->unwrap());
            }

            if (!$this->isRunning()) {
                // Process finished, read any remaining data
                $finalStdout = $stdoutReader->readAvailable();

                if ($finalStdout->isOk()) {
                    $stdout = $stdout->append($finalStdout->unwrap());
                }

                $finalStderr = $stderrReader->readAvailable();

                if ($finalStderr->isOk()) {
                    $stderr = $stderr->append($finalStderr->unwrap());
                }

                break;
            }

            \usleep(10000); // 10ms
        }

        $stdoutReader->close();
        $stderrReader->close();

        return Result::ok(Output::of($stdout, $stderr, $this->status()));
    }

    /**
     * Closes all pipes and the process handle.
     */
    public function close(): void
    {
        $this->pipes->forEach(
            static function($_, $resource) {
                if (\is_resource($resource)) {
                    \fclose($resource);
                }
            },
        );

        if (\is_resource($this->handle)) {
            \proc_close($this->handle);
        }
    }
}
