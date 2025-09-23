<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Primitives;

/**
 * The Unit type represents a value that has no meaningful content.
 * It is used as a placeholder for a value that is not needed or relevant, like a function returning void.
 *
 * @psalm-immutable
 */
final readonly class Unit
{
    /**
     * @psalm-pure
     */
    public static function new(): self
    {
        return new self();
    }
}
