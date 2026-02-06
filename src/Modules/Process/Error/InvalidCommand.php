<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process\Error;

final class InvalidCommand extends \InvalidArgumentException
{
    public function __construct(string $message = 'Command cannot be empty', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
