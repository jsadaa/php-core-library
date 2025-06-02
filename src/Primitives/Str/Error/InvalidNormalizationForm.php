<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Primitives\Str\Error;

final class InvalidNormalizationForm extends \InvalidArgumentException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
