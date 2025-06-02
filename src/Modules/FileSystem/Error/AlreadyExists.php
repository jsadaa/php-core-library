<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error;

final class AlreadyExists extends \RuntimeException
{
    public function __construct(string $message = 'File or directory already exists', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
