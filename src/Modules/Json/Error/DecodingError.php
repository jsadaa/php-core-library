<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Json\Error;

final class DecodingError extends \RuntimeException
{
    public function __construct(string $message ='')
    {
        parent::__construct("Json decoding error : $message");
    }
}
