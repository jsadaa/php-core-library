<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Time\Error;

final class DurationCalculationInvalid extends \RuntimeException
{
    public function __construct(string $message = 'Duration calculation resulted in an invalid value (infinity or NaN)', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
