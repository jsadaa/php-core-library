<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process\Error;

final class InvalidWorkingDirectory extends \InvalidArgumentException
{
    public function __construct(string $path, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct("Working directory does not exist: {$path}", $code, $previous);
    }
}
