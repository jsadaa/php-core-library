<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Option;

/**
 * Represents the absence of a value.
 * @psalm-immutable
 */
final readonly class None
{
    private function __construct()
    {
    }

    /**
     * Creates a new None instance.
     * @psalm-pure
     */
    public static function new(): self
    {
        return new self();
    }
}
