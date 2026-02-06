<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Map\Map;
use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
use Jsadaa\PhpCoreLibrary\Modules\Process\Error\InvalidCommand;
use Jsadaa\PhpCoreLibrary\Modules\Process\Error\InvalidWorkingDirectory;
use Jsadaa\PhpCoreLibrary\Modules\Process\Error\ProcessSpawnFailed;
use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;

/**
 * Immutable builder for creating processes.
 *
 * @psalm-immutable
 */
final readonly class ProcessBuilder
{
    private Str $command;
    /** @var Sequence<Str> */
    private Sequence $args;
    private Path $workingDirectory;
    /** @var Map<Str, Str> */
    private Map $environment;
    private ProcessStreams $streams;
    private bool $inheritEnv;

    /**
     * @param Sequence<Str> $args
     * @param Map<Str, Str> $environment
     */
    private function __construct(
        Str $command,
        Sequence $args,
        Path $workingDirectory,
        Map $environment,
        ProcessStreams $streams,
        bool $inheritEnv,
    ) {
        $this->command = $command;
        $this->args = $args;
        $this->workingDirectory = $workingDirectory;
        $this->environment = $environment;
        $this->streams = $streams;
        $this->inheritEnv = $inheritEnv;
    }

    /**
     * @psalm-suppress ImpureFunctionCall
     */
    public static function command(string | Str $command): self
    {
        return new self(
            \is_string($command) ? Str::of($command) : $command,
            Sequence::new(),
            Path::of(\getcwd() ?: '/'),
            Map::new(),
            ProcessStreams::defaults(),
            true,
        );
    }

    public function arg(string | Str $arg): self
    {
        return new self(
            $this->command,
            $this->args->add(\is_string($arg) ? Str::of($arg) : $arg),
            $this->workingDirectory,
            $this->environment,
            $this->streams,
            $this->inheritEnv,
        );
    }

    /**
     * @param array<string>|Sequence<Str> $args
     */
    public function args($args): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        $sequence = $args instanceof Sequence
            ? $args
            : Sequence::of(...\array_map(static fn($a) => Str::of($a), $args));

        return new self(
            $this->command,
            $this->args->append($sequence),
            $this->workingDirectory,
            $this->environment,
            $this->streams,
            $this->inheritEnv,
        );
    }

    public function workingDirectory(string | Path $path): self
    {
        return new self(
            $this->command,
            $this->args,
            \is_string($path) ? Path::of($path) : $path,
            $this->environment,
            $this->streams,
            $this->inheritEnv,
        );
    }

    public function env(string | Str $key, string | Str $value): self
    {
        return new self(
            $this->command,
            $this->args,
            $this->workingDirectory,
            $this->environment->add(
                \is_string($key) ? Str::of($key) : $key,
                \is_string($value) ? Str::of($value) : $value,
            ),
            $this->streams,
            $this->inheritEnv,
        );
    }

    public function inheritEnv(bool $inherit = true): self
    {
        return new self(
            $this->command,
            $this->args,
            $this->workingDirectory,
            $this->environment,
            $this->streams,
            $inherit,
        );
    }

    public function clearEnv(): self
    {
        return new self(
            $this->command,
            $this->args,
            $this->workingDirectory,
            Map::new(),
            $this->streams,
            false,
        );
    }

    public function stdin(StreamDescriptor $descriptor): self
    {
        return new self(
            $this->command,
            $this->args,
            $this->workingDirectory,
            $this->environment,
            $this->streams->withStdin($descriptor),
            $this->inheritEnv,
        );
    }

    public function stdout(StreamDescriptor $descriptor): self
    {
        return new self(
            $this->command,
            $this->args,
            $this->workingDirectory,
            $this->environment,
            $this->streams->withStdout($descriptor),
            $this->inheritEnv,
        );
    }

    public function stderr(StreamDescriptor $descriptor): self
    {
        return new self(
            $this->command,
            $this->args,
            $this->workingDirectory,
            $this->environment,
            $this->streams->withStderr($descriptor),
            $this->inheritEnv,
        );
    }

    public function streams(ProcessStreams $streams): self
    {
        return new self(
            $this->command,
            $this->args,
            $this->workingDirectory,
            $this->environment,
            $streams,
            $this->inheritEnv,
        );
    }

    public function getStreams(): ProcessStreams
    {
        return $this->streams;
    }

    /**
     * Spawns the process.
     *
     * @psalm-suppress ImpureFunctionCall
     * @psalm-suppress ImpureMethodCall
     *
     * @return Result<Process, InvalidCommand|InvalidWorkingDirectory|ProcessSpawnFailed>
     */
    public function spawn(): Result
    {
        if ($this->command->isEmpty()) {
            /** @var Result<Process, InvalidCommand|InvalidWorkingDirectory|ProcessSpawnFailed> */
            return Result::err(new InvalidCommand());
        }

        if (!$this->workingDirectory->isDir()) {
            /** @var Result<Process, InvalidCommand|InvalidWorkingDirectory|ProcessSpawnFailed> */
            return Result::err(new InvalidWorkingDirectory($this->workingDirectory->toString()));
        }

        $commandArray = $this->buildCommandArray();
        $descriptorSpec = $this->streams->toDescriptorArray();
        /** @var array<int, resource> $pipes */
        $pipes = [];

        $handle = \proc_open(
            $commandArray,
            $descriptorSpec,
            $pipes,
            $this->workingDirectory->toString(),
            $this->buildEnvironmentArray(),
            ['suppress_errors' => true, 'bypass_shell' => true],
        );

        if (!\is_resource($handle)) {
            /** @var Result<Process, InvalidCommand|InvalidWorkingDirectory|ProcessSpawnFailed> */
            return Result::err(new ProcessSpawnFailed());
        }

        /** @var array<int, resource> $typedPipes */
        $typedPipes = $pipes;

        /** @var Result<Process, InvalidCommand|InvalidWorkingDirectory|ProcessSpawnFailed> */
        return Result::ok(Process::fromHandle($handle, $typedPipes));
    }

    /**
     * Builds the command as an array for proc_open (bypass_shell mode).
     *
     * @return array<int, string>
     */
    private function buildCommandArray(): array
    {
        /** @psalm-suppress ImpureFunctionCall */
        $args = $this->args->fold(
            /**
             * @param array<int, string> $carry
             * @return array<int, string>
             */
            static fn(array $carry, Str $arg): array => [...$carry, $arg->toString()],
            [],
        );

        return [$this->command->toString(), ...$args];
    }

    /**
     * @return array<string, string>|null
     */
    private function buildEnvironmentArray(): ?array
    {
        if (!$this->inheritEnv && $this->environment->isEmpty()) {
            return [];
        }

        if ($this->inheritEnv && $this->environment->isEmpty()) {
            return null;
        }

        /** @psalm-suppress ImpureFunctionCall */
        $custom = $this->environment->fold(
            /**
             * @param array<string, string> $env
             * @return array<string, string>
             */
            static fn(array $env, Str $key, Str $value): array => \array_merge(
                $env,
                [$key->toString() => $value->toString()],
            ),
            [],
        );

        if (!$this->inheritEnv) {
            return $custom;
        }

        /** @psalm-suppress ImpureFunctionCall */
        return \array_merge($_ENV, $custom);
    }
}
