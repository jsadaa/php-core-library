<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Process\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Process\Status;
use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    public function testStatusFromArray(): void
    {
        $status = Status::ofArray([
            'command' => 'echo hello',
            'pid' => 1234,
            'running' => false,
            'signaled' => false,
            'stopped' => false,
            'exitCode' => 0,
            'termSignal' => 0,
            'stopSignal' => 0,
        ]);

        $this->assertEquals('echo hello', $status->command()->toString());
        $this->assertEquals(1234, $status->pid()->toInt());
        $this->assertFalse($status->isRunning());
        $this->assertFalse($status->isSignaled());
        $this->assertFalse($status->isStopped());
        $this->assertEquals(0, $status->exitCode()->toInt());
        $this->assertTrue($status->isSuccess());
        $this->assertFalse($status->isFailure());
    }

    public function testStatusFailure(): void
    {
        $status = Status::ofArray([
            'command' => 'false',
            'pid' => 5678,
            'running' => false,
            'signaled' => false,
            'stopped' => false,
            'exitCode' => 1,
            'termSignal' => 0,
            'stopSignal' => 0,
        ]);

        $this->assertTrue($status->isFailure());
        $this->assertFalse($status->isSuccess());
        $this->assertEquals(1, $status->exitCode()->toInt());
    }

    public function testStatusRunning(): void
    {
        $status = Status::ofArray([
            'command' => 'sleep 60',
            'pid' => 9999,
            'running' => true,
            'signaled' => false,
            'stopped' => false,
            'exitCode' => -1,
            'termSignal' => 0,
            'stopSignal' => 0,
        ]);

        $this->assertTrue($status->isRunning());
    }

    public function testStatusSignaled(): void
    {
        $status = Status::ofArray([
            'command' => 'killed',
            'pid' => 111,
            'running' => false,
            'signaled' => true,
            'stopped' => false,
            'exitCode' => -1,
            'termSignal' => 9,
            'stopSignal' => 0,
        ]);

        $this->assertTrue($status->isSignaled());
        $this->assertEquals(9, $status->termSignal()->toInt());
    }

    public function testStatusStopped(): void
    {
        $status = Status::ofArray([
            'command' => 'stopped',
            'pid' => 222,
            'running' => false,
            'signaled' => false,
            'stopped' => true,
            'exitCode' => -1,
            'termSignal' => 0,
            'stopSignal' => 19,
        ]);

        $this->assertTrue($status->isStopped());
        $this->assertEquals(19, $status->stopSignal()->toInt());
    }

    public function testStatusFromLiveProcess(): void
    {
        $pipes = [];
        $handle = \proc_open(['echo', 'live'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $this->assertNotFalse($handle);

        \usleep(100000); // 100ms

        $status = Status::of($handle);
        // With bypass_shell=true, proc_get_status returns only the command name
        $this->assertStringContainsString('echo', $status->command()->toString());

        foreach ($pipes as $pipe) {
            \fclose($pipe);
        }
        \proc_close($handle);
    }
}
