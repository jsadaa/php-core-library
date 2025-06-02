<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Time\Error;

final class ZeroDuration extends \InvalidArgumentException
{
    public function __construct(string $message = 'Cannot perform operation with zero duration', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
