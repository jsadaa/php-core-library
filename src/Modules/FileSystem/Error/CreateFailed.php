<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error;

final class CreateFailed extends \RuntimeException
{
    public function __construct(string $message = 'Failed to create file or directory', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
