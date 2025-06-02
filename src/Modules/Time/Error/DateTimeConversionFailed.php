<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Time\Error;

final class DateTimeConversionFailed extends \RuntimeException
{
    public function __construct(string $message = 'Failed to convert between SystemTime and DateTimeImmutable', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
