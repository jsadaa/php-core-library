<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Time\Error;

final class TryFromFloatFailed extends \InvalidArgumentException
{
    public function __construct(string $message = 'Failed to convert floating-point value to time', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
