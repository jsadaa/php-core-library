<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Time\Error;

final class TimeBeforeUnixEpoch extends \LogicException
{
    public function __construct(string $message = 'Time conversion represents a time before Unix epoch', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
