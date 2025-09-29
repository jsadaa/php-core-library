<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process;

use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;

/**
 * @psalm-immutable
 */
final readonly class Status {
    private Str $command;
    private Integer $pid;
    private bool $running;
    private bool $signaled;
    private bool $stopped;
    private Integer $exitCode;
    private Integer $termSignal;
    private Integer $stopSignal;

    private function __construct(
        Str $command,
        Integer $pid,
        bool $running,
        bool $signaled,
        bool $stopped,
        Integer $exitCode,
        Integer $termSignal,
        Integer $stopSignal,
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
     * @psalm-pure
     * @param resource $resource
     */
    public static function of(
        $resource,
    ): self {
        $status = \proc_get_status($resource);

        return new self(
            Str::of($status['command']),
            Integer::of($status['pid']),
            $status['running'],
            $status['signaled'],
            $status['stopped'],
            Integer::of($status['exitcode']),
            Integer::of($status['termsig']),
            Integer::of($status['stopsig']),
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
            Integer::of($data['pid']),
            $data['running'],
            $data['signaled'],
            $data['stopped'],
            Integer::of($data['exitCode']),
            Integer::of($data['termSignal']),
            Integer::of($data['stopSignal']),
        );
    }

    public function command(): Str
    {
        return $this->command;
    }

    public function pid(): Integer
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

    public function exitCode(): Integer
    {
        return $this->exitCode;
    }

    public function termSignal(): Integer
    {
        return $this->termSignal;
    }

    public function stopSignal(): Integer
    {
        return $this->stopSignal;
    }

    public function isSuccess(): bool
    {
        return $this->exitCode->eq(0);
    }

    public function isFailure(): bool
    {
        return !$this->isSuccess();
    }
}
