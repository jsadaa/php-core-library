<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process\Error;

final class InvalidPid extends \RuntimeException
{
    public function __construct(string $message = 'Process has no valid PID', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
