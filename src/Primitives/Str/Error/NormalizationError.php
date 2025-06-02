<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Primitives\Str\Error;

final class NormalizationError extends \RuntimeException
{
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
