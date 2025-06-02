<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Path\Error;

final class PrefixNotFound extends \InvalidArgumentException
{
    public function __construct(string $message = 'Prefix not found in path', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
