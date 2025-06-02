<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\FileSystem\Error;

final class InvalidFileType extends \RuntimeException
{
    public function __construct(string $message = 'Invalid file type', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
