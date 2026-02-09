<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use Jsadaa\PhpCoreLibrary\Modules\Process\Error\InvalidCommand;
use Jsadaa\PhpCoreLibrary\Modules\Process\Error\InvalidWorkingDirectory;
use Jsadaa\PhpCoreLibrary\Modules\Process\Error\PipelineSpawnFailed;
use Jsadaa\PhpCoreLibrary\Modules\Process\Error\ProcessSpawnFailed;
use Jsadaa\PhpCoreLibrary\Modules\Process\Error\ProcessTimeout;
use Jsadaa\PhpCoreLibrary\Modules\Process\Error\StreamReadFailed;
use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Modules\Time\Duration;
use Jsadaa\PhpCoreLibrary\Modules\Time\Error\TimeOverflow;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;

/**
 * High-level command execution with pipeline support.
 * Acts as a convenient wrapper around ProcessBuilder.
 *
 * @psalm-immutable
 */
final readonly class Command
{
    private ProcessBuilder $builder;
    /** @var Sequence<ProcessBuilder> */
    private Sequence $pipeline;
    private Duration $timeout;

    /**
     * @param Sequence<ProcessBuilder> $pipeline
     */
    private function __construct(
        ProcessBuilder $builder,
        Sequence $pipeline,
        Duration $timeout,
    ) {
        $this->builder = $builder;
        $this->pipeline = $pipeline;
        $this->timeout = $timeout;
    }

    /**
     * Creates a new command.
     *
     * @psalm-suppress ImpureFunctionCall
     */
    public static function of(string | Str $name): self
    {
        return new self(
            ProcessBuilder::command($name),
            Sequence::new(),
            Duration::fromSeconds(30),
        );
    }

    public function withArg(string | Str $arg): self
    {
        return new self(
            $this->builder->arg($arg),
            $this->pipeline,
            $this->timeout,
        );
    }

    public function atPath(string | Path $path): self
    {
        return new self(
            $this->builder->workingDirectory($path),
            $this->pipeline,
            $this->timeout,
        );
    }

    public function withEnv(string | Str $var, string | Str $value): self
    {
        return new self(
            $this->builder->env($var, $value),
            $this->pipeline,
            $this->timeout,
        );
    }

    public function withTimeout(int | Duration $timeout): self
    {
        $duration = match (true) {
            $timeout instanceof Duration => $timeout,
            default => Duration::fromSeconds($timeout),
        };

        return new self(
            $this->builder,
            $this->pipeline,
            $duration,
        );
    }

    /**
     * Configures input/output streams.
     */
    public function withStreams(ProcessStreams $streams): self
    {
        return new self(
            $this->builder->streams($streams),
            $this->pipeline,
            $this->timeout,
        );
    }

    /**
     * Redirects stdin from a file.
     */
    public function fromFile(string | Path $path): self
    {
        return new self(
            $this->builder->stdin(StreamDescriptor::file($path, 'r')),
            $this->pipeline,
            $this->timeout,
        );
    }

    /**
     * Redirects stdout to a file.
     */
    public function toFile(string | Path $path): self
    {
        return new self(
            $this->builder->stdout(StreamDescriptor::file($path, 'w')),
            $this->pipeline,
            $this->timeout,
        );
    }

    /**
     * Redirects stderr to a file.
     */
    public function errorToFile(string | Path $path): self
    {
        return new self(
            $this->builder->stderr(StreamDescriptor::file($path, 'w')),
            $this->pipeline,
            $this->timeout,
        );
    }

    /**
     * Suppresses all output (redirects to null).
     */
    public function quiet(): self
    {
        return new self(
            $this->builder
                ->stdout(StreamDescriptor::null())
                ->stderr(StreamDescriptor::null()),
            $this->pipeline,
            $this->timeout,
        );
    }

    /**
     * Pipe this command into another command.
     */
    public function pipe(self | ProcessBuilder $command): self
    {
        $builder = $command instanceof self ? $command->builder : $command;

        return new self(
            $this->builder,
            $this->pipeline->add($builder),
            $this->timeout,
        );
    }

    /**
     * Executes the command and returns the output.
     *
     * @psalm-suppress ImpureFunctionCall
     * @psalm-suppress ImpureMethodCall
     *
     * @return Result<Output, Output|InvalidCommand|InvalidWorkingDirectory|ProcessSpawnFailed|ProcessTimeout|StreamReadFailed|TimeOverflow>
     */
    public function run(): Result
    {
        return $this->pipeline->isEmpty()
            ? $this->runSingle()
            : $this->runPipeline();
    }

    /**
     * Spawns the command without waiting for it to complete.
     *
     * @psalm-suppress ImpureFunctionCall
     * @psalm-suppress ImpureMethodCall
     *
     * @return Result<Process, InvalidCommand|InvalidWorkingDirectory|ProcessSpawnFailed|PipelineSpawnFailed>
     */
    public function spawn(): Result
    {
        if (!$this->pipeline->isEmpty()) {
            /** @var Result<Process, InvalidCommand|InvalidWorkingDirectory|ProcessSpawnFailed|PipelineSpawnFailed> */
            return Result::err(new PipelineSpawnFailed());
        }

        /** @var Result<Process, InvalidCommand|InvalidWorkingDirectory|ProcessSpawnFailed|PipelineSpawnFailed> */
        return $this->builder->spawn();
    }

    /**
     * Executes the command and returns only stdout as a string.
     *
     * @psalm-suppress ImpureFunctionCall
     * @psalm-suppress ImpureMethodCall
     *
     * @return Result<Str, Str>
     */
    public function output(): Result
    {
        $result = $this->run();

        if ($result->isErr()) {
            $error = $result->unwrapErr();

            /** @var Result<Str, Str> */
            return Result::err(
                $error instanceof Output
                ? Str::of($error->stderr()->toString())
                : Str::of($error->getMessage()),
            );
        }

        /** @var Result<Str, Str> */
        return Result::ok($result->unwrap()->stdout());
    }

    /**
     * Gets the underlying ProcessBuilder for advanced configuration.
     */
    public function builder(): ProcessBuilder
    {
        return $this->builder;
    }

    /**
     * @psalm-suppress ImpureMethodCall
     * @psalm-suppress ImpureFunctionCall
     *
     * @return Result<Output, Output|InvalidCommand|InvalidWorkingDirectory|ProcessSpawnFailed|ProcessTimeout|StreamReadFailed|TimeOverflow>
     */
    private function runSingle(): Result
    {
        $processResult = $this->builder->spawn();

        if ($processResult->isErr()) {
            /** @var Result<Output, Output|InvalidCommand|InvalidWorkingDirectory|ProcessSpawnFailed|ProcessTimeout|StreamReadFailed|TimeOverflow> */
            return Result::err($processResult->unwrapErr());
        }

        $process = $processResult->unwrap();
        $outputResult = $process->output($this->timeout);
        $process->close();

        if ($outputResult->isErr()) {
            /** @var Result<Output, Output|InvalidCommand|InvalidWorkingDirectory|ProcessSpawnFailed|ProcessTimeout|StreamReadFailed|TimeOverflow> */
            return Result::err($outputResult->unwrapErr());
        }

        $output = $outputResult->unwrap();

        if ($output->isSuccess()) {
            /** @var Result<Output, Output|InvalidCommand|InvalidWorkingDirectory|ProcessSpawnFailed|ProcessTimeout|StreamReadFailed|TimeOverflow> */
            return Result::ok($output);
        }

        /** @var Result<Output, Output|InvalidCommand|InvalidWorkingDirectory|ProcessSpawnFailed|ProcessTimeout|StreamReadFailed|TimeOverflow> */
        return Result::err($output);
    }

    /**
     * @psalm-suppress ImpureMethodCall
     * @psalm-suppress ImpureFunctionCall
     * @psalm-suppress InvalidPassByReference
     *
     * @return Result<Output, Output|InvalidCommand|InvalidWorkingDirectory|ProcessSpawnFailed|ProcessTimeout|StreamReadFailed|TimeOverflow>
     */
    private function runPipeline(): Result
    {
        /** @var Sequence<ProcessBuilder> $builders */
        $builders = Sequence::of($this->builder)->append($this->pipeline);

        /** @var Sequence<Process> $processes */
        $processes = Sequence::new();

        $i = 0;

        foreach ($builders->iter() as $builder) {
            $index = $i++;

            if ($index > 0) {
                $prevProcess = $processes->get($index - 1)->unwrap();
                $stdoutResult = $prevProcess->stdout();

                if ($stdoutResult->isSome()) {
                    $builder = $builder->stdin(
                        StreamDescriptor::resource($stdoutResult->unwrap()),
                    );
                }
            }

            if ($index < $builders->size() - 1) {
                $currentStreams = $builder->getStreams();
                $stdoutDesc = $currentStreams->get(FileDescriptor::stdout());

                if ($stdoutDesc->isNone() || $stdoutDesc->unwrap()->isPipe()) {
                    $builder = $builder->stdout(StreamDescriptor::pipe('w'));
                }
            }

            $processResult = $builder->spawn();

            if ($processResult->isErr()) {
                $this->cleanupProcesses($processes);

                /** @var Result<Output, Output|InvalidCommand|InvalidWorkingDirectory|ProcessSpawnFailed|ProcessTimeout|StreamReadFailed|TimeOverflow> */
                return Result::err($processResult->unwrapErr());
            }

            $processes = $processes->add($processResult->unwrap());
        }

        $lastProcess = $processes->last()->unwrap();

        $firstProcess = $processes->first()->unwrap();
        $stdinResult = $firstProcess->stdin();

        if ($stdinResult->isSome()) {
            $stdinStream = $stdinResult->unwrap();
            \fclose($stdinStream);
        }

        $outputResult = $lastProcess->output($this->timeout);

        $this->cleanupProcesses($processes);

        if ($outputResult->isErr()) {
            /** @var Result<Output, Output|InvalidCommand|InvalidWorkingDirectory|ProcessSpawnFailed|ProcessTimeout|StreamReadFailed|TimeOverflow> */
            return Result::err($outputResult->unwrapErr());
        }

        $output = $outputResult->unwrap();

        if ($output->isSuccess()) {
            /** @var Result<Output, Output|InvalidCommand|InvalidWorkingDirectory|ProcessSpawnFailed|ProcessTimeout|StreamReadFailed|TimeOverflow> */
            return Result::ok($output);
        }

        /** @var Result<Output, Output|InvalidCommand|InvalidWorkingDirectory|ProcessSpawnFailed|ProcessTimeout|StreamReadFailed|TimeOverflow> */
        return Result::err($output);
    }

    /**
     * @psalm-suppress ImpureMethodCall
     *
     * @param Sequence<Process> $processes
     */
    private function cleanupProcesses(Sequence $processes): void
    {
        foreach ($processes->iter() as $process) {
            if ($process->isRunning()) {
                $process->kill(\SIGKILL);
            }
            $process->close();
        }
    }
}
