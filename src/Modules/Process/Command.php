<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Map\Map;
use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Modules\Time\Duration;
use Jsadaa\PhpCoreLibrary\Modules\Time\SystemTime;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;

/**
 * @psalm-immutable
 */
final readonly class Command {
    private Str $name;
    /** @var Sequence<Str> */
    private Sequence $args;
    private Path $cwd;
    /** @var Map<Str, Str> */
    private Map $env;
    /** @var Sequence<Command> */
    private Sequence $pipeline;
    private Duration $timeout;

    /**
     * @param Str $name
     * @param Sequence<Str> $args
     * @param Map<Str, Str> $env
     * @param Sequence<Command> $pipeline
     * @param Duration $timeout
     */
     private function __construct(Str $name, Sequence $args, Path $cwd, Map $env, Sequence $pipeline, Duration $timeout)
     {
         $this->name = $name;
         $this->args = $args;
         $this->cwd = $cwd;
         $this->env = $env;
         $this->pipeline = $pipeline;
         $this->timeout = $timeout;
     }

    /**
     * @psalm-pure
     */
    public static function of(string | Str $name): self
    {
        return new self(
            \is_string($name) ? Str::of($name) : $name,
            Sequence::new(),
            Path::of(\getcwd() ?: '/'),
            Map::new(),
            Sequence::new(),
            Duration::fromSeconds(30),
        );
    }

    public function withArg(string | Str $arg): self
    {
        return new self(
            $this->name,
            $this->args->add(\is_string($arg) ? Str::of($arg) : $arg),
            $this->cwd,
            $this->env,
            $this->pipeline,
            $this->timeout,
        );
    }

    public function atPath(string | Path $path): self
    {
        return new self(
            $this->name,
            $this->args,
            \is_string($path) ? Path::of($path) : $path,
            $this->env,
            $this->pipeline,
            $this->timeout,
        );
    }

    public function withEnv(string | Str $var, string | Str $value): self
    {
        return new self(
            $this->name,
            $this->args,
            $this->cwd,
            $this->env->add(
                \is_string($var) ? Str::of($var) : $var,
                \is_string($value) ? Str::of($value) : $value,
            ),
            $this->pipeline,
            $this->timeout,
        );
    }

    public function withTimeout(int | Integer $timeout): self
    {
        return new self(
            $this->name,
            $this->args,
            $this->cwd,
            $this->env,
            $this->pipeline,
            Duration::fromSeconds($timeout),
        );
    }

    /**
     * Pipe this command into another command
     */
    public function pipe(self $command): self
    {
        return new self(
            $this->name,
            $this->args,
            $this->cwd,
            $this->env,
            $this->pipeline->add($command),
            $this->timeout,
        );
    }

    public function run(): Result
    {
        return $this->pipeline->isEmpty()
            ? $this->runSingle()
            : $this->runPipeline();
    }

    /**
     * Runs the command and returns the result.
     *
     * @return Result<Output, Output|\Exception>
     */
     private function runSingle(): Result
     {
         if ($this->name->isEmpty() || \preg_match('/[;&|`$()]/', $this->name->toString())) {
             return Result::err('Invalid command name: contains dangerous characters');
         }

         if (!$this->cwd->isDir()) {
             return Result::err("Specified directory path does not exist: {$this->cwd->toString()}");
         }

         $pipes = [];

         $process = \proc_open(
             $this->buildCommand()->toString(),
             $this->standardDescriptors(),
             $pipes,
             $this->cwd->toString(),
             $this->buildEnvironment($this),
             $this->options(),
         );

         if (!\is_resource($process)) {
             return Result::err('Failed to start process');
         }

         \fclose($pipes[0]); // Close stdin

         $stream = $this->read($pipes[1], $pipes[2]);

         if ($stream->isErr()) {
             \proc_terminate($process, \SIGKILL);
             $this->cleanupStreams([$pipes[1], $pipes[2]]);
             \proc_close($process);

             return $stream;
         }

         [$stdout, $stderr] = $stream->unwrap();
         $this->cleanupStreams([$pipes[1], $pipes[2]]);

         $status = Status::of($process);
         \proc_close($process);

         $output = Output::of($stdout, $stderr, $status);

         return $output->isSuccess() ? Result::ok($output) : Result::err($output);
     }

    private function runPipeline(): Result
    {
        $commands = Sequence::of($this)->append($this->pipeline);

        foreach ($commands->iter() as $cmd) {
            if ($cmd->name->isEmpty() || \preg_match('/[;&|`$()]/', $cmd->name->toString())) {
                return Result::err('Invalid command name: contains dangerous characters');
            }
        }

        $pipeline = $this->pipelineProcesses($commands);

        if ($pipeline->isErr()) {
            return $pipeline;
        }

        [$processes, $pipes] = $pipeline->unwrap();
        $lastIndex = $commands->size()->sub(1)->toInt();

        $stream = $this->read($pipes[$lastIndex][1], $pipes[$lastIndex][2]);

        if ($stream->isErr()) {
            $this->cleanupProcesses($processes, $pipes);

            return $stream;
        }

        $this->wait($processes);

        [$stdout, $stderr] = $stream->unwrap();
        $finalStatus = Status::of($processes[$lastIndex]);

        $this->cleanupProcesses($processes, $pipes);

        $output = Output::of($stdout, $stderr, $finalStatus);

        return $output->isSuccess() ? Result::ok($output) : Result::err($output);
    }

    private function standardDescriptors(): array
    {
        return [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];
    }

    private function options(): array
    {
        return [
            'suppress_errors' => true,
            'bypass_shell' => false,
        ];
    }

    private function isTimedOut(SystemTime $startTime): bool
    {
        $elapsed = SystemTime::now()->durationSince($startTime);

        return $elapsed->isErr() || $elapsed->unwrap()->ge($this->timeout);
    }

    private function read($stdoutPipe, $stderrPipe): Result
    {
        \stream_set_blocking($stdoutPipe, false);
        \stream_set_blocking($stderrPipe, false);

        $stdout = Str::new();
        $stderr = Str::new();

        $startTime = SystemTime::now();

        while (true) {
            if ($this->isTimedOut($startTime)) {
                return Result::err('Command execution timed out');
            }

            $read = [$stdoutPipe, $stderrPipe];
            $write = null;
            $except = null;

            $result = \stream_select($read, $write, $except, 1);

            if ($result === false) {
                break;
            }

            $hasData = false;

            foreach ($read as $stream) {
                $data = \fread($stream, 8192);

                if ($data !== false && $data !== '') {
                    $hasData = true;

                    if ($stream === $stdoutPipe) {
                        $stdout = $stdout->append($data);  // Concaténation string native
                    } else {
                        $stderr = $stderr->append($data);  // Concaténation string native
                    }
                }
            }

            if (!$hasData) {
                break;
            }
        }

        // Read remaining data
        $stdout = $stdout->append(\stream_get_contents($stdoutPipe) ?: '');
        $stderr = $stderr->append(\stream_get_contents($stderrPipe) ?: '');

        return Result::ok([$stdout, $stderr]);
    }

    private function pipelineProcesses(Sequence $commands): Result
    {
        $processes = [];
        $pipes = [];

        foreach ($commands->iter() as $index => $cmd) {
            $processes[$index] = \proc_open(
                $cmd->buildCommand()->toString(),
                $this->pipelineDescriptors($index, $pipes),
                $pipes[$index],
                $cmd->cwd->toString(),
                $this->buildEnvironment($cmd),
                $this->options(),
            );

            if (!\is_resource($processes[$index])) {
                $this->cleanupProcesses($processes, $pipes);

                return Result::err("Failed to start process {$index} in pipeline");
            }

            if ($index === 0) {
                \fclose($pipes[$index][0]); // Close stdin of first command
            }
        }

        return Result::ok([$processes, $pipes]);
    }

    private function pipelineDescriptors(int $index, array $pipes): array
    {
        return $index === 0
            ? $this->standardDescriptors()
            : [
                0 => $pipes[$index - 1][1],  // stdin from previous stdout
                1 => ['pipe', 'w'],          // stdout
                2 => ['pipe', 'w'],          // stderr
            ];
    }

    private function wait(array $processes): void
    {
        $startTime = SystemTime::now();

        while (true) {
            if ($this->isTimedOut($startTime)) {
                foreach ($processes as $process) {
                    \proc_terminate($process, \SIGKILL);
                }
                break;
            }

            $allFinished = true;

            foreach ($processes as $process) {
                $status = Status::of($process);

                if ($status->isRunning()) {
                    $allFinished = false;
                    break;
                }
            }

            if ($allFinished) {
                break;
            }

            \usleep(100000); // Sleep 100ms
        }
    }

    private function cleanupStreams(array $streams): void
    {
        foreach ($streams as $stream) {
            if (\is_resource($stream)) {
                \fclose($stream);
            }
        }
    }

    private function cleanupProcesses(array $processes, array $pipes): void
    {
        foreach ($pipes as $pipeSet) {
            foreach ($pipeSet as $pipe) {
                if (\is_resource($pipe)) {
                    \fclose($pipe);
                }
            }
        }

        foreach ($processes as $process) {
            if (\is_resource($process)) {
                \proc_close($process);
            }
        }
    }

    /**
     * Builds the command string.
     *
     */
     private function buildCommand(): Str
    {
        $command = $this->name->map(static fn(string $name) => \escapeshellcmd($name));

        if (!$this->args->isEmpty()) {
            $command = $this
                ->args
                ->map(
                    // If the argument contains environment variable syntax,
                    // wrap in double quotes instead of using escapeshellarg
                    static fn(Str $arg) => match (true) {
                        // Use double quotes to allow variable expansion
                        // but escape dangerous characters except $
                        \preg_match(
                            '/\$[A-Z_][A-Z0-9_]*/i',
                            $arg->toString(),
                        ) !== false => Str::of('"' . \str_replace(['\\', '"', '`'], ['\\\\', '\\"', '\\`'], $arg->toString()) . '"'),
                        // Normal argument - escape normally
                        default => $arg->map(static fn($a) => \escapeshellarg($a)),
                    },
                )
                ->fold(
                    static fn(Str $command, Str $arg) => $command->append($arg->prepend(' ')),
                    $command,
                );
        }

        return $command;
    }

    private function buildEnvironment(self $cmd): array
    {
        return $cmd->env->fold(
            static fn(array $carry, Str $var, Str $value) => [...$carry, $var->toString() => $value->toString()],
            $_ENV,
        );
    }
}
