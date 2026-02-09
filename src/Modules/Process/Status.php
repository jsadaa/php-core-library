<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process;

use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;

/**
 * @psalm-immutable
 */
final readonly class Status {
    private Str $command;
    private int $pid;
    private bool $running;
    private bool $signaled;
    private bool $stopped;
    private int $exitCode;
    private int $termSignal;
    private int $stopSignal;

    private function __construct(
        Str $command,
        int $pid,
        bool $running,
        bool $signaled,
        bool $stopped,
        int $exitCode,
        int $termSignal,
        int $stopSignal,
    ) {
        $this->command = $command;
        $this->pid = $pid;
        $this->running = $running;
        $this->signaled = $signaled;
        $this->stopped = $stopped;
        $this->exitCode = $exitCode;
        $this->termSignal = $termSignal;
        $this->stopSignal = $stopSignal;
    }

    /**
     * @param resource $resource
     */
    public static function of(
        $resource,
    ): self {
        $status = \proc_get_status($resource);

        return new self(
            Str::of($status['command']),
            $status['pid'],
            $status['running'],
            $status['signaled'],
            $status['stopped'],
            $status['exitcode'],
            $status['termsig'],
            $status['stopsig'],
        );
    }

    /**
     * @psalm-pure
     * @param array{
     *     command: string,
     *     pid: int,
     *     running: bool,
     *     signaled: bool,
     *     stopped: bool,
     *     exitCode: int,
     *     termSignal: int,
     *     stopSignal: int
     * } $data
     */
    public static function ofArray(array $data): self
    {
        return new self(
            Str::of($data['command']),
            $data['pid'],
            $data['running'],
            $data['signaled'],
            $data['stopped'],
            $data['exitCode'],
            $data['termSignal'],
            $data['stopSignal'],
        );
    }

    public function command(): Str
    {
        return $this->command;
    }

    public function pid(): int
    {
        return $this->pid;
    }

    public function isRunning(): bool
    {
        return $this->running;
    }

    public function isSignaled(): bool
    {
        return $this->signaled;
    }

    public function isStopped(): bool
    {
        return $this->stopped;
    }

    public function exitCode(): int
    {
        return $this->exitCode;
    }

    public function termSignal(): int
    {
        return $this->termSignal;
    }

    public function stopSignal(): int
    {
        return $this->stopSignal;
    }

    public function isSuccess(): bool
    {
        return $this->exitCode === 0;
    }

    public function isFailure(): bool
    {
        return !$this->isSuccess();
    }
}
