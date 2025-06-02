<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Path\Error;

final class PathInvalid extends \InvalidArgumentException
{
    public function __construct(string $message = 'Invalid path', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
