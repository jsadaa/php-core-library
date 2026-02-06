<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Map\Map;
use Jsadaa\PhpCoreLibrary\Modules\Option\Option;

/**
 * Immutable collection of process stream descriptors.
 *
 * Uses int keys (file descriptor numbers) for Map compatibility.
 *
 * @psalm-immutable
 */
final readonly class ProcessStreams
{
    /** @var Map<int, StreamDescriptor> */
    private Map $descriptors;

    /**
     * @param Map<int, StreamDescriptor> $descriptors
     */
    private function __construct(Map $descriptors)
    {
        $this->descriptors = $descriptors;
    }

    /**
     * Creates default streams (stdin pipe, stdout pipe, stderr pipe).
     *
     * @psalm-pure
     */
    public static function defaults(): self
    {
        /** @var Map<int, StreamDescriptor> $map */
        $map = Map::of(0, StreamDescriptor::pipe('r'));

        return new self(
            $map->add(1, StreamDescriptor::pipe('w'))
                ->add(2, StreamDescriptor::pipe('w')),
        );
    }

    /**
     * Creates streams that inherit from parent process.
     *
     * @psalm-pure
     */
    public static function inherit(): self
    {
        /** @var Map<int, StreamDescriptor> $map */
        $map = Map::of(0, StreamDescriptor::inherit());

        return new self(
            $map->add(1, StreamDescriptor::inherit())
                ->add(2, StreamDescriptor::inherit()),
        );
    }

    /**
     * Creates null streams (all redirected to /dev/null).
     *
     * @psalm-pure
     */
    public static function null(): self
    {
        /** @var Map<int, StreamDescriptor> $map */
        $map = Map::of(0, StreamDescriptor::null());

        return new self(
            $map->add(1, StreamDescriptor::null())
                ->add(2, StreamDescriptor::null()),
        );
    }

    public function withStdin(StreamDescriptor $descriptor): self
    {
        return new self(
            $this->descriptors->add(0, $descriptor),
        );
    }

    public function withStdout(StreamDescriptor $descriptor): self
    {
        return new self(
            $this->descriptors->add(1, $descriptor),
        );
    }

    public function withStderr(StreamDescriptor $descriptor): self
    {
        return new self(
            $this->descriptors->add(2, $descriptor),
        );
    }

    public function withDescriptor(FileDescriptor $fd, StreamDescriptor $descriptor): self
    {
        return new self(
            $this->descriptors->add($fd->toInt(), $descriptor),
        );
    }

    /**
     * @return Option<StreamDescriptor>
     */
    public function get(FileDescriptor $fd): Option
    {
        return $this->descriptors->get($fd->toInt());
    }

    /**
     * Converts to PHP's proc_open descriptor array format.
     *
     * @return array<int, array|resource>
     */
    public function toDescriptorArray(): array
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $this
            ->descriptors
            ->fold(
                /**
                 * @param array<int, array<int, string>|resource> $carry
                 * @return array<int, array<int, string>|resource>
                 */
                static function(array $carry, int $fd, StreamDescriptor $descriptor): array {
                    $val = $descriptor->toDescriptor();

                    if ($val !== null) {
                        $carry[$fd] = $val;
                    }

                    return $carry;
                },
                [],
            );
    }
}
