<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process\Error;

final class ProcessTimeout extends \RuntimeException
{
    public function __construct(string $message = 'Process execution timed out', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
