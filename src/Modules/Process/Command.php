<?php

declare(strict_types=1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Modules\Time\Duration;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;

/**
 * High-level command execution with pipeline support.
 * Acts as a convenient wrapper around ProcessBuilder.
 *
 * Acts as a convenient wrapper around ProcessBuilder.
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
        Duration $timeout
    ) {
        $this->builder = $builder;
        $this->pipeline = $pipeline;
        $this->timeout = $timeout;
    }

    /**
     * Creates a new command.
     */
    public static function of(string|Str $name): self
    {
        return new self(
            ProcessBuilder::command($name),
            Sequence::new(),
            Duration::fromSeconds(30),
        );
    }

    public function withArg(string|Str $arg): self
    {
        return new self(
            $this->builder->arg($arg),
            $this->pipeline,
            $this->timeout,
        );
    }

    public function atPath(string|Path $path): self
    {
        return new self(
            $this->builder->workingDirectory($path),
            $this->pipeline,
            $this->timeout,
        );
    }

    public function withEnv(string|Str $var, string|Str $value): self
    {
        return new self(
            $this->builder->env($var, $value),
            $this->pipeline,
            $this->timeout,
        );
    }

    public function withTimeout(int|Integer|Duration $timeout): self
    {
        $duration = match (true) {
            $timeout instanceof Duration => $timeout,
            $timeout instanceof Integer => Duration::fromSeconds($timeout->toInt()),
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
    public function fromFile(string|Path $path): self
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
    public function toFile(string|Path $path): self
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
    public function errorToFile(string|Path $path): self
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
    public function pipe(self|ProcessBuilder $command): self
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
     * @return Result<Output, Output|string>
     */
    public function run(): Result
    {
        return $this->pipeline->isEmpty()
            ? $this->runSingle()
            : $this->runPipeline();
    }

    /**
     * @return Result<Output, Output|string>
     */
    private function runSingle(): Result
    {
        $processResult = $this->builder->spawn();

        if ($processResult->isErr()) {
            /** @var Result<Output, Output|string> $err */
            $err = Result::err($processResult->unwrapErr());
            return $err;
        }

        $process = $processResult->unwrap();
        $outputResult = $process->output($this->timeout);
        $process->close();

        if ($outputResult->isErr()) {
            /** @var Result<Output, Output|string> $err */
            $err = Result::err($outputResult->unwrapErr());
            return $err;
        }

        $output = $outputResult->unwrap();

        if ($output->isSuccess()) {
            /** @var Result<Output, Output|string> $ok */
            $ok = Result::ok($output);
            return $ok;
        }

        /** @var Result<Output, Output|string> $err */
        $err = Result::err($output);
        return $err;
    }

    /**
     * @return Result<Output, Output|string>
     */
    private function runPipeline(): Result
    {
        // Build all builders in the pipeline
        /** @var Sequence<ProcessBuilder> $builders */
        $builders = Sequence::of($this->builder)->append($this->pipeline);

        /** @var Sequence<Process> $processes */
        $processes = Sequence::new();

        $i = 0;
        foreach ($builders->iter() as $builder) {
            $index = $i++;
            // Connect pipes between processes
            if ($index > 0) {
                $prevProcess = $processes->get(Integer::of($index - 1))->unwrap();
                $stdoutResult = $prevProcess->stdout();

                if ($stdoutResult->isSome()) {
                    $builder = $builder->stdin(
                        StreamDescriptor::resource($stdoutResult->unwrap())
                    );
                }
            }

            // For intermediate processes, ensure stdout is piped
            if ($index < $builders->size()->sub(1)->toInt()) {
                // Only set to pipe if not already configured
                // This respects user configuration like toFile()
                $currentStreams = $builder->getStreams();
                $stdoutDesc = $currentStreams->get(FileDescriptor::stdout());

                if ($stdoutDesc->isNone() || $stdoutDesc->unwrap()->isPipe()) {
                    $builder = $builder->stdout(StreamDescriptor::pipe('w'));
                }
            }

            $processResult = $builder->spawn();

            if ($processResult->isErr()) {
                // Clean up any already started processes
                $this->cleanupProcesses($processes);
                /** @var Result<Output, Output|string> $err */
                $err = Result::err($processResult->unwrapErr());
                return $err;
            }

            $processes = $processes->add($processResult->unwrap());
        }

        // Wait for all processes to complete and get output from the last one
        $lastProcess = $processes->last()->unwrap();

        // Close stdin of first process if it's still open
        $firstProcess = $processes->first()->unwrap();
        $stdinResult = $firstProcess->stdin();
        if ($stdinResult->isSome()) {
            $stdinRes = $stdinResult->unwrap();
            fclose($stdinRes);
        }

        $outputResult = $lastProcess->output($this->timeout);

        // Clean up all processes
        $this->cleanupProcesses($processes);

        if ($outputResult->isErr()) {
            /** @var Result<Output, Output|string> $err */
            $err = Result::err($outputResult->unwrapErr());
            return $err;
        }

        $output = $outputResult->unwrap();

        if ($output->isSuccess()) {
            /** @var Result<Output, Output|string> $ok */
            $ok = Result::ok($output);
            return $ok;
        }

        /** @var Result<Output, Output|string> $err */
        $err = Result::err($output);
        return $err;
    }

    /**
     * @param Sequence<Process> $processes
     */
    private function cleanupProcesses(Sequence $processes): void
    {
        foreach ($processes->iter() as $process) {
            if ($process->isRunning()) {
                $process->kill(SIGKILL);
            }
            $process->close();
        }
    }

    /**
     * Spawns the command without waiting for it to complete.
     *
     * @return Result<Process, string>
     */
    public function spawn(): Result
    {
        if (!$this->pipeline->isEmpty()) {
            /** @var Result<Process, string> */
            return Result::err("Cannot spawn a pipeline command. Use run() instead.");
        }

        return $this->builder->spawn();
    }

    /**
     * Executes the command and returns only stdout as a string.
     *
     * @return Result<Str, string>
     */
    public function output(): Result
    {
        $result = $this->run();

        if ($result->isErr()) {
            $error = $result->unwrapErr();
            /** @var Result<Str, string> $err */
            $err = Result::err(
                $error instanceof Output
                ? $error->stderr()->toString()
                : $error
            );
            return $err;
        }

        /** @var Result<Str, string> $ok */
        $ok = Result::ok($result->unwrap()->stdout());
        return $ok;
    }

    /**
     * Gets the underlying ProcessBuilder for advanced configuration.
     */
    public function builder(): ProcessBuilder
    {
        return $this->builder;
    }
}
