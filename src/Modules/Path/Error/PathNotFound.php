<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Path\Error;

final class PathNotFound extends \RuntimeException
{
    public function __construct(string $message = 'Path not found', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
