<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Time\Error;

final class DurationOverflow extends \OverflowException
{
    public function __construct(string $message = 'Duration operation resulted in overflow', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
