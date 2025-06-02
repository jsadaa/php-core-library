<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Collections\Vec\Error;

final class IndexOutOfBounds extends \RuntimeException
{
    public function __construct(int $index, int $size)
    {
        parent::__construct("Index $index is out of bounds for size $size");
    }
}
