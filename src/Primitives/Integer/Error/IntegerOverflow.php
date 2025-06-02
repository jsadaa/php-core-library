<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Primitives\Integer\Error;

final class IntegerOverflow extends \OverflowException
{
    public function __construct()
    {
        parent::__construct('Integer overflow');
    }
}
