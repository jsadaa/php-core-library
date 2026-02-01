<?php

declare(strict_types=1);

namespace Jsadaa\PhpCoreLibrary\Tests\Process\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Process\Command;
use Jsadaa\PhpCoreLibrary\Modules\Process\Process;
use Jsadaa\PhpCoreLibrary\Modules\Process\ProcessBuilder;
use PHPUnit\Framework\TestCase;

class ProcessTest extends TestCase
{
    public function testProcessBuilderSpawn(): void
    {
        $processBuilder = ProcessBuilder::command('echo');

        $process = $processBuilder->arg('hello')
            ->spawn();

        $this->assertTrue($process->isOk());
        $this->assertInstanceOf(Process::class, $process->unwrap());

        $output = $process->unwrap()->output();
        $this->assertTrue($output->isOk());
        $this->assertEquals("hello\n", $output->unwrap()->stdout()->toString());
    }

    public function testCommandRun(): void
    {
        $result = Command::of('echo')
            ->withArg('world')
            ->output();

        $this->assertTrue($result->isOk());
        $this->assertEquals("world\n", $result->unwrap()->toString());
    }

    public function testPipeline(): void
    {
        $result = Command::of('echo')
            ->withArg('hello world')
            ->pipe(Command::of('grep')->withArg('world'))
            ->output();

        $this->assertTrue($result->isOk());
        $this->assertEquals("hello world\n", $result->unwrap()->toString());
    }
}
