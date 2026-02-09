<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Process\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Process\Command;
use Jsadaa\PhpCoreLibrary\Modules\Process\Error\InvalidCommand;
use Jsadaa\PhpCoreLibrary\Modules\Process\Error\InvalidWorkingDirectory;
use Jsadaa\PhpCoreLibrary\Modules\Process\Error\PipelineSpawnFailed;
use Jsadaa\PhpCoreLibrary\Modules\Process\Error\ProcessTimeout;
use Jsadaa\PhpCoreLibrary\Modules\Process\Process;
use Jsadaa\PhpCoreLibrary\Modules\Process\ProcessBuilder;
use Jsadaa\PhpCoreLibrary\Modules\Time\Duration;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use PHPUnit\Framework\TestCase;

class ProcessTest extends TestCase
{
    // ── ProcessBuilder ──────────────────────────────────────────

    public function testProcessBuilderSpawn(): void
    {
        $process = ProcessBuilder::command('echo')
            ->arg('hello')
            ->spawn();

        $this->assertTrue($process->isOk());
        $this->assertInstanceOf(Process::class, $process->unwrap());

        $output = $process->unwrap()->output(Duration::fromSeconds(5));
        $this->assertTrue($output->isOk());
        $this->assertEquals("hello\n", $output->unwrap()->stdout()->toString());
    }

    public function testProcessBuilderEmptyCommandReturnsError(): void
    {
        $result = ProcessBuilder::command('')->spawn();

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(InvalidCommand::class, $result->unwrapErr());
    }

    public function testProcessBuilderInvalidWorkingDirectoryReturnsError(): void
    {
        $result = ProcessBuilder::command('echo')
            ->workingDirectory('/nonexistent/path/that/does/not/exist')
            ->spawn();

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(InvalidWorkingDirectory::class, $result->unwrapErr());
    }

    public function testProcessBuilderWithMultipleArgs(): void
    {
        $result = ProcessBuilder::command('echo')
            ->args(['-n', 'hello world'])
            ->spawn();

        $this->assertTrue($result->isOk());

        $output = $result->unwrap()->output(Duration::fromSeconds(5));
        $this->assertTrue($output->isOk());
        $this->assertEquals('hello world', $output->unwrap()->stdout()->toString());
    }

    public function testProcessBuilderWithWorkingDirectory(): void
    {
        $result = ProcessBuilder::command('pwd')
            ->workingDirectory('/tmp')
            ->spawn();

        $this->assertTrue($result->isOk());

        $output = $result->unwrap()->output(Duration::fromSeconds(5));
        $this->assertTrue($output->isOk());

        $path = \realpath('/tmp');
        $this->assertEquals($path . "\n", $output->unwrap()->stdout()->toString());
    }

    public function testProcessBuilderWithEnvironmentVariable(): void
    {
        $result = ProcessBuilder::command('sh')
            ->args(['-c', 'echo $MY_TEST_VAR'])
            ->env('MY_TEST_VAR', 'test_value')
            ->spawn();

        $this->assertTrue($result->isOk());

        $output = $result->unwrap()->output(Duration::fromSeconds(5));
        $this->assertTrue($output->isOk());
        $this->assertEquals("test_value\n", $output->unwrap()->stdout()->toString());
    }

    public function testProcessBuilderClearEnv(): void
    {
        $result = ProcessBuilder::command('env')
            ->clearEnv()
            ->env('ONLY_THIS', 'var')
            ->spawn();

        $this->assertTrue($result->isOk());

        $output = $result->unwrap()->output(Duration::fromSeconds(5));
        $this->assertTrue($output->isOk());
        $this->assertEquals("ONLY_THIS=var\n", $output->unwrap()->stdout()->toString());
    }

    public function testProcessBuilderInheritEnvFalse(): void
    {
        $result = ProcessBuilder::command('env')
            ->inheritEnv(false)
            ->env('A', '1')
            ->env('B', '2')
            ->spawn();

        $this->assertTrue($result->isOk());

        $output = $result->unwrap()->output(Duration::fromSeconds(5));
        $this->assertTrue($output->isOk());

        $lines = \explode("\n", \trim($output->unwrap()->stdout()->toString()));
        \sort($lines);
        $this->assertEquals(['A=1', 'B=2'], $lines);
    }

    // ── Process ─────────────────────────────────────────────────

    public function testProcessStatus(): void
    {
        $process = ProcessBuilder::command('sleep')
            ->arg('10')
            ->spawn()
            ->unwrap();

        $this->assertTrue($process->isRunning());
        $process->kill();

        \usleep(100000); // 100ms
        $this->assertFalse($process->isRunning());
        $process->close();
    }

    public function testProcessPid(): void
    {
        $process = ProcessBuilder::command('echo')
            ->arg('test')
            ->spawn()
            ->unwrap();

        $pid = $process->pid();
        $this->assertTrue($pid->isOk());
        $this->assertGreaterThan(0, $pid->unwrap());
        $process->close();
    }

    public function testProcessWaitWithTimeout(): void
    {
        $process = ProcessBuilder::command('echo')
            ->arg('fast')
            ->spawn()
            ->unwrap();

        $status = $process->wait(Duration::fromSeconds(5));
        $this->assertTrue($status->isOk());
        $this->assertTrue($status->unwrap()->isSuccess());
        $process->close();
    }

    public function testProcessWaitTimeoutExceeded(): void
    {
        $process = ProcessBuilder::command('sleep')
            ->arg('60')
            ->spawn()
            ->unwrap();

        $status = $process->wait(Duration::fromMillis(50));
        $this->assertTrue($status->isErr());
        $this->assertInstanceOf(ProcessTimeout::class, $status->unwrapErr());

        $process->kill(\SIGKILL);
        $process->close();
    }

    public function testProcessKill(): void
    {
        $process = ProcessBuilder::command('sleep')
            ->arg('60')
            ->spawn()
            ->unwrap();

        $this->assertTrue($process->isRunning());

        $result = $process->kill();
        $this->assertTrue($result->isOk());

        \usleep(100000); // 100ms
        $this->assertFalse($process->isRunning());
        $process->close();
    }

    public function testProcessReadStdout(): void
    {
        $process = ProcessBuilder::command('echo')
            ->arg('hello stdout')
            ->spawn()
            ->unwrap();

        \usleep(100000); // 100ms - let process finish

        $result = $process->readStdout();
        $this->assertTrue($result->isOk());
        $this->assertEquals("hello stdout\n", $result->unwrap()->toString());
        $process->close();
    }

    public function testProcessReadStderr(): void
    {
        $process = ProcessBuilder::command('sh')
            ->args(['-c', 'echo error >&2'])
            ->spawn()
            ->unwrap();

        \usleep(100000); // 100ms

        $result = $process->readStderr();
        $this->assertTrue($result->isOk());
        $this->assertEquals("error\n", $result->unwrap()->toString());
        $process->close();
    }

    public function testProcessOutput(): void
    {
        $process = ProcessBuilder::command('echo')
            ->arg('output test')
            ->spawn()
            ->unwrap();

        $output = $process->output(Duration::fromSeconds(5));
        $this->assertTrue($output->isOk());
        $this->assertTrue($output->unwrap()->isSuccess());
        $this->assertEquals("output test\n", $output->unwrap()->stdout()->toString());
        $this->assertEquals('', $output->unwrap()->stderr()->toString());
        $process->close();
    }

    public function testProcessOutputTimeout(): void
    {
        $process = ProcessBuilder::command('sleep')
            ->arg('60')
            ->spawn()
            ->unwrap();

        $output = $process->output(Duration::fromMillis(100));
        $this->assertTrue($output->isErr());
        $this->assertInstanceOf(ProcessTimeout::class, $output->unwrapErr());
        $process->close();
    }

    public function testProcessOutputWithStderr(): void
    {
        $process = ProcessBuilder::command('sh')
            ->args(['-c', 'echo out && echo err >&2'])
            ->spawn()
            ->unwrap();

        $output = $process->output(Duration::fromSeconds(5));
        $this->assertTrue($output->isOk());
        $this->assertEquals("out\n", $output->unwrap()->stdout()->toString());
        $this->assertEquals("err\n", $output->unwrap()->stderr()->toString());
        $process->close();
    }

    public function testProcessWriteStdin(): void
    {
        $process = ProcessBuilder::command('cat')
            ->spawn()
            ->unwrap();

        $writeResult = $process->writeStdin('hello from stdin');
        $this->assertTrue($writeResult->isOk());

        // Close stdin to signal EOF to cat
        $stdin = $process->stdin();

        if ($stdin->isSome()) {
            $s = $stdin->unwrap();
            \fclose($s);
        }

        // Wait and read output
        $process->wait(Duration::fromSeconds(5));
        $output = $process->readStdout();
        $this->assertTrue($output->isOk());
        $this->assertEquals('hello from stdin', $output->unwrap()->toString());
        $process->close();
    }

    public function testProcessExitCode(): void
    {
        $process = ProcessBuilder::command('sh')
            ->args(['-c', 'exit 42'])
            ->spawn()
            ->unwrap();

        $output = $process->output(Duration::fromSeconds(5));
        $this->assertTrue($output->isOk());
        $this->assertTrue($output->unwrap()->isFailure());
        $this->assertEquals(42, $output->unwrap()->exitCode());
        $process->close();
    }

    // ── Command (high-level API) ────────────────────────────────

    public function testCommandRun(): void
    {
        $result = Command::of('echo')
            ->withArg('world')
            ->run();

        $this->assertTrue($result->isOk());
        $this->assertTrue($result->unwrap()->isSuccess());
        $this->assertEquals("world\n", $result->unwrap()->stdout()->toString());
    }

    public function testCommandOutput(): void
    {
        $result = Command::of('echo')
            ->withArg('world')
            ->output();

        $this->assertTrue($result->isOk());
        $this->assertEquals("world\n", $result->unwrap()->toString());
    }

    public function testCommandWithTimeout(): void
    {
        $result = Command::of('sleep')
            ->withArg('60')
            ->withTimeout(Duration::fromMillis(100))
            ->run();

        $this->assertTrue($result->isErr());
    }

    public function testCommandWithTimeoutInt(): void
    {
        $result = Command::of('echo')
            ->withArg('fast')
            ->withTimeout(5)
            ->output();

        $this->assertTrue($result->isOk());
        $this->assertEquals("fast\n", $result->unwrap()->toString());
    }

    public function testCommandAtPath(): void
    {
        $result = Command::of('pwd')
            ->atPath('/tmp')
            ->output();

        $this->assertTrue($result->isOk());

        $path = \realpath('/tmp');
        $this->assertEquals($path . "\n", $result->unwrap()->toString());
    }

    public function testCommandWithEnv(): void
    {
        $result = Command::of('sh')
            ->withArg('-c')
            ->withArg('echo $MY_VAR')
            ->withEnv('MY_VAR', 'hello_env')
            ->output();

        $this->assertTrue($result->isOk());
        $this->assertEquals("hello_env\n", $result->unwrap()->toString());
    }

    public function testCommandWithStrArg(): void
    {
        $result = Command::of(Str::of('echo'))
            ->withArg(Str::of('typed'))
            ->output();

        $this->assertTrue($result->isOk());
        $this->assertEquals("typed\n", $result->unwrap()->toString());
    }

    public function testCommandFailedExitCode(): void
    {
        $result = Command::of('sh')
            ->withArg('-c')
            ->withArg('exit 1')
            ->run();

        $this->assertTrue($result->isErr());
    }

    public function testCommandSpawn(): void
    {
        $result = Command::of('echo')
            ->withArg('spawned')
            ->spawn();

        $this->assertTrue($result->isOk());
        $this->assertInstanceOf(Process::class, $result->unwrap());

        $output = $result->unwrap()->output(Duration::fromSeconds(5));
        $this->assertTrue($output->isOk());
        $this->assertEquals("spawned\n", $output->unwrap()->stdout()->toString());
        $result->unwrap()->close();
    }

    public function testCommandSpawnPipelineReturnsError(): void
    {
        $result = Command::of('echo')
            ->withArg('test')
            ->pipe(Command::of('cat'))
            ->spawn();

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(PipelineSpawnFailed::class, $result->unwrapErr());
    }

    // ── Pipeline ────────────────────────────────────────────────

    public function testPipelineTwoCommands(): void
    {
        $result = Command::of('echo')
            ->withArg('hello world')
            ->pipe(Command::of('grep')->withArg('world'))
            ->output();

        $this->assertTrue($result->isOk());
        $this->assertEquals("hello world\n", $result->unwrap()->toString());
    }

    public function testPipelineThreeCommands(): void
    {
        $result = Command::of('echo')
            ->withArg('hello world foo')
            ->pipe(Command::of('tr')->withArg(' ')->withArg("\n"))
            ->pipe(Command::of('grep')->withArg('world'))
            ->output();

        $this->assertTrue($result->isOk());
        $this->assertEquals("world\n", $result->unwrap()->toString());
    }

    public function testPipelineNoMatch(): void
    {
        $result = Command::of('echo')
            ->withArg('hello')
            ->pipe(Command::of('grep')->withArg('notfound'))
            ->run();

        $this->assertTrue($result->isErr());
    }

    // ── Status and Output ───────────────────────────────────────

    public function testStatusSuccess(): void
    {
        $result = Command::of('true')->run();

        $this->assertTrue($result->isOk());
        $this->assertTrue($result->unwrap()->isSuccess());
        $this->assertFalse($result->unwrap()->isFailure());
        $this->assertEquals(0, $result->unwrap()->exitCode());
    }

    public function testStatusFailure(): void
    {
        $result = Command::of('false')->run();

        $this->assertTrue($result->isErr());
    }

    public function testOutputToString(): void
    {
        $result = Command::of('echo')
            ->withArg('to_string')
            ->run();

        $this->assertTrue($result->isOk());
        $this->assertStringContainsString('to_string', $result->unwrap()->toString());
    }
}
