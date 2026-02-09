<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Process\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Process\Output;
use Jsadaa\PhpCoreLibrary\Modules\Process\Status;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use PHPUnit\Framework\TestCase;

class OutputTest extends TestCase
{
    public function testSuccessOutput(): void
    {
        $output = Output::of(
            Str::of('hello'),
            Str::of(''),
            $this->makeStatus(0),
        );

        $this->assertTrue($output->isSuccess());
        $this->assertFalse($output->isFailure());
        $this->assertEquals(0, $output->exitCode());
        $this->assertEquals('hello', $output->stdout()->toString());
        $this->assertEquals('', $output->stderr()->toString());
    }

    public function testFailureOutput(): void
    {
        $output = Output::of(
            Str::of(''),
            Str::of('error message'),
            $this->makeStatus(1),
        );

        $this->assertTrue($output->isFailure());
        $this->assertFalse($output->isSuccess());
        $this->assertEquals('error message', $output->stderr()->toString());
    }

    public function testToStringSuccess(): void
    {
        $output = Output::of(
            Str::of('stdout content'),
            Str::of('stderr content'),
            $this->makeStatus(0),
        );

        $this->assertEquals('stdout content', $output->toString());
    }

    public function testToStringFailure(): void
    {
        $output = Output::of(
            Str::of('stdout content'),
            Str::of('stderr content'),
            $this->makeStatus(1),
        );

        $this->assertEquals('stderr content', $output->toString());
    }
    private function makeStatus(int $exitCode): Status
    {
        return Status::ofArray([
            'command' => 'test',
            'pid' => 1,
            'running' => false,
            'signaled' => false,
            'stopped' => false,
            'exitCode' => $exitCode,
            'termSignal' => 0,
            'stopSignal' => 0,
        ]);
    }
}
