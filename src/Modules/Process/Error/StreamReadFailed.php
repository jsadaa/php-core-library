<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process\Error;

final class StreamReadFailed extends \RuntimeException
{
    public function __construct(string $message = 'Failed to read from stream', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
