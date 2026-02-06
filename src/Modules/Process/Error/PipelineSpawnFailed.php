<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process\Error;

final class PipelineSpawnFailed extends \RuntimeException
{
    public function __construct(string $message = 'Cannot spawn a pipeline command, use run() instead', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
