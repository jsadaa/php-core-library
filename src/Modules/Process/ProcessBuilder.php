<?php

declare(strict_types=1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Map\Map;
use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use Jsadaa\PhpCoreLibrary\Modules\Path\Path;
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

    /**
     * @param Sequence<Str> $args
     * @param Map<Str, Str> $environment
     */
    private function __construct(
        Str $command,
        Sequence $args,
        Path $workingDirectory,
        Map $environment,
        ProcessStreams $streams
    ) {
        $this->command = $command;
        $this->args = $args;
        $this->workingDirectory = $workingDirectory;
        $this->environment = $environment;
        $this->streams = $streams;
    }

    /**
     * @psalm-pure
     * @psalm-suppress ImpureFunctionCall
     */
    public static function command(string|Str $command): self
    {
        return new self(
            is_string($command) ? Str::of($command) : $command,
            Sequence::new(),
            Path::of(getcwd() ?: '/'),
            Map::new(),
            ProcessStreams::defaults()
        );
    }

    public function arg(string|Str $arg): self
    {
        return new self(
            $this->command,
            $this->args->add(is_string($arg) ? Str::of($arg) : $arg),
            $this->workingDirectory,
            $this->environment,
            $this->streams
        );
    }

    /**
     * @param array<string>|Sequence<Str> $args
     */
    public function args($args): self
    {
        $sequence = $args instanceof Sequence
            ? $args
            : Sequence::of(...array_map(fn($a) => Str::of($a), $args));

        return new self(
            $this->command,
            $this->args->append($sequence),
            $this->workingDirectory,
            $this->environment,
            $this->streams
        );
    }

    public function workingDirectory(string|Path $path): self
    {
        return new self(
            $this->command,
            $this->args,
            is_string($path) ? Path::of($path) : $path,
            $this->environment,
            $this->streams
        );
    }

    public function env(string|Str $key, string|Str $value): self
    {
        return new self(
            $this->command,
            $this->args,
            $this->workingDirectory,
            $this->environment->add(
                is_string($key) ? Str::of($key) : $key,
                is_string($value) ? Str::of($value) : $value
            ),
            $this->streams
        );
    }

    public function stdin(StreamDescriptor $descriptor): self
    {
        return new self(
            $this->command,
            $this->args,
            $this->workingDirectory,
            $this->environment,
            $this->streams->withStdin($descriptor)
        );
    }

    public function stdout(StreamDescriptor $descriptor): self
    {
        return new self(
            $this->command,
            $this->args,
            $this->workingDirectory,
            $this->environment,
            $this->streams->withStdout($descriptor)
        );
    }

    public function stderr(StreamDescriptor $descriptor): self
    {
        return new self(
            $this->command,
            $this->args,
            $this->workingDirectory,
            $this->environment,
            $this->streams->withStderr($descriptor)
        );
    }

    public function streams(ProcessStreams $streams): self
    {
        return new self(
            $this->command,
            $this->args,
            $this->workingDirectory,
            $this->environment,
            $streams
        );
    }

    public function getStreams(): ProcessStreams
    {
        return $this->streams;
    }

    /**
     * Spawns the process.
     *
     * @return Result<Process, string>
     */
    public function spawn(): Result
    {
        if ($this->command->isEmpty()) {
            return Result::err("Command cannot be empty");
        }

        if (!$this->workingDirectory->isDir()) {
            return Result::err("Working directory does not exist: {$this->workingDirectory->toString()}");
        }

        $commandLine = $this->buildCommandLine();
        $descriptorSpec = $this->streams->toDescriptorArray();
        $pipes = [];

        $handle = proc_open(
            $commandLine->toString(),
            $descriptorSpec,
            $pipes,
            $this->workingDirectory->toString(),
            $this->buildEnvironmentArray(),
            ['suppress_errors' => true, 'bypass_shell' => false]
        );

        if (!is_resource($handle)) {
            return Result::err("Failed to spawn process");
        }

        return Result::ok(Process::fromHandle($handle, $pipes));
    }

    private function buildCommandLine(): Str
    {
        $escaped = $this->command->map(fn($c) => escapeshellcmd($c));

        return $this->args->fold(
            fn(Str $cmd, Str $arg) => $cmd->append(
                $arg->map(fn($a) => ' ' . escapeshellarg($a))
            ),
            $escaped
        );
    }

    private function buildEnvironmentArray(): array
    {
        return $this->environment->fold(
            fn(array $env, Str $key, Str $value) => array_merge(
                $env,
                [$key->toString() => $value->toString()]
            ),
            $_ENV
        );
    }
}
