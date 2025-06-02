<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Primitives\Double\Error;

final class DivisionByZero extends \InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('Division by zero');
    }
}