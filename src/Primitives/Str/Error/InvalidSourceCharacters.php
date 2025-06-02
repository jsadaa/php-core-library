<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Primitives\Str\Error;

final class InvalidSourceCharacters extends EncodingError
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
