<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Jsadaa\PhpCoreLibrary\Modules\Process\Command;
use Jsadaa\PhpCoreLibrary\Modules\Process\ProcessBuilder;
use Jsadaa\PhpCoreLibrary\Modules\Process\ProcessStreams;
use Jsadaa\PhpCoreLibrary\Modules\Process\StreamDescriptor;
use Jsadaa\PhpCoreLibrary\Modules\Process\StreamReader;
use Jsadaa\PhpCoreLibrary\Modules\Process\StreamWriter;
use Jsadaa\PhpCoreLibrary\Modules\Time\Duration;

// Example 1: Simple command execution
$result = Command::of('ls')
    ->withArg('-la')
    ->atPath('/tmp')
    ->run();

if ($result->isOk()) {
    echo $result->unwrap()->stdout()->toString();
} else {
    echo "Error: " . $result->unwrapErr();
}

// Example 3: Redirect output to file
$result = Command::of('ls')
    ->withArg('-la')
    ->toFile('/tmp/directory-listing.txt')
    ->run();

// Example 4: Custom streams configuration
$streams = ProcessStreams::defaults()
    ->withStdout(StreamDescriptor::file('/tmp/output.log', 'w'))
    ->withStderr(StreamDescriptor::file('/tmp/error.log', 'w'))
    ->withStdin(StreamDescriptor::null());

$result = Command::of('some-command')
    ->withStreams($streams)
    ->run();

// Example 5: Spawn process without waiting
$processResult = Command::of('sleep')
    ->withArg('10')
    ->spawn();

if ($processResult->isOk()) {
    $process = $processResult->unwrap();

    // Do other work...

    // Wait for process with timeout
    $waitResult = $process->wait(Duration::fromSeconds(5));

    if ($waitResult->isErr()) {
        // Timeout occurred, kill the process
        $process->kill(SIGTERM);
    }

    $process->close();
}

// Example 6: Interactive process communication
$processResult = ProcessBuilder::command('bc')
    ->stdin(StreamDescriptor::pipe('r'))
    ->stdout(StreamDescriptor::pipe('w'))
    ->spawn();

if ($processResult->isOk()) {
    $process = $processResult->unwrap();

    // Send calculation
    $process->writeStdin("2 + 2\n");

    // Read result
    $output = $process->readStdout();
    if ($output->isOk()) {
        echo "Result: " . $output->unwrap()->toString();
    }

    // Send quit command
    $process->writeStdin("quit\n");

    $process->wait(Duration::fromSeconds(1));
    $process->close();
}

// Example 7: Using the old Command API (still works)
$result = Command::of('find')
    ->withArg('.')
    ->withArg('-name')
    ->withArg('*.php')
    ->pipe(Command::of('head')->withArg('-10'))
    ->run();

if ($result->isOk()) {
    echo "First 10 PHP files:\n";
    echo $result->unwrap()->stdout()->toString();
}

/*
// Example 8: Inheriting parent process streams
$result = ProcessBuilder::command('vim')
    ->arg('test.txt')
    ->streams(ProcessStreams::inherit())
    ->spawn();

if ($result->isOk()) {
    $process = $result->unwrap();
    $process->wait(null); // Wait indefinitely
    $process->close();
}
*/

// Example 8: Interactive process with StreamWriter
$processResult = ProcessBuilder::command('bc')
    ->stdin(StreamDescriptor::pipe('r'))
    ->stdout(StreamDescriptor::pipe('w'))
    ->stderr(StreamDescriptor::pipe('w'))
    ->spawn();

if ($processResult->isOk()) {
    $process = $processResult->unwrap();

    // Get writer for stdin
    $writerResult = $process->stdinWriter();

    if ($writerResult->isOk()) {
        $writer = $writerResult->unwrap();

        // Write multiple calculations
        $writer->writeLine("10 + 20");
        $writer->writeLine("scale=2");
        $writer->writeLine("22 / 7");
        $writer->flush();

        // Read results
        sleep(1); // Give bc time to process
        $output = $process->readStdout();

        if ($output->isOk()) {
            echo "Results:\n" . $output->unwrap()->toString();
        }

        $writer->writeLine("quit");
    }

    $process->wait(Duration::fromSeconds(2));
    $process->close();
}
