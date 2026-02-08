<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\IO\Error;

final class ReadFailed extends \RuntimeException
{
    public function __construct(string $message = 'Failed to read from input stream', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
