<?php

declare(strict_types=1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Map\Map;
use Jsadaa\PhpCoreLibrary\Modules\Option\Option;

/**
 * Immutable collection of process stream descriptors.
 *
 * @psalm-immutable
 */
final readonly class ProcessStreams
{
    /** @var Map<FileDescriptor, StreamDescriptor> */
    private Map $descriptors;

    /**
     * @param Map<FileDescriptor, StreamDescriptor> $descriptors
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
        return new self(
            Map::of(FileDescriptor::stdin(), StreamDescriptor::pipe('r'))
                ->add(FileDescriptor::stdout(), StreamDescriptor::pipe('w'))
                ->add(FileDescriptor::stderr(), StreamDescriptor::pipe('w'))
        );
    }

    /**
     * Creates streams that inherit from parent process.
     *
     * @psalm-pure
     */
    public static function inherit(): self
    {
        return new self(
            Map::of(FileDescriptor::stdin(), StreamDescriptor::inherit())
                ->add(FileDescriptor::stdout(), StreamDescriptor::inherit())
                ->add(FileDescriptor::stderr(), StreamDescriptor::inherit())
        );
    }

    /**
     * Creates null streams (all redirected to /dev/null).
     *
     * @psalm-pure
     */
    public static function null(): self
    {
        return new self(
            Map::of(FileDescriptor::stdin(), StreamDescriptor::null())
                ->add(FileDescriptor::stdout(), StreamDescriptor::null())
                ->add(FileDescriptor::stderr(), StreamDescriptor::null())
        );
    }

    public function withStdin(StreamDescriptor $descriptor): self
    {
        return new self(
            $this->descriptors->add(FileDescriptor::stdin(), $descriptor)
        );
    }

    public function withStdout(StreamDescriptor $descriptor): self
    {
        return new self(
            $this->descriptors->add(FileDescriptor::stdout(), $descriptor)
        );
    }

    public function withStderr(StreamDescriptor $descriptor): self
    {
        return new self(
            $this->descriptors->add(FileDescriptor::stderr(), $descriptor)
        );
    }

    public function withDescriptor(FileDescriptor $fd, StreamDescriptor $descriptor): self
    {
        return new self(
            $this->descriptors->add($fd, $descriptor)
        );
    }

    /**
     * @return Option<StreamDescriptor>
     */
    public function get(FileDescriptor $fd): Option
    {
        return $this->descriptors->get($fd);
    }

    /**
     * Converts to PHP's proc_open descriptor array format.
     *
     * @return array<int, array|resource>
     */
    public function toDescriptorArray(): array
    {
        return $this
            ->descriptors
            ->fold(
                /**
                 * @param array<int, array<int, string>|resource> $carry
                 * @return array<int, array<int, string>|resource>
                 */
                function (array $carry, FileDescriptor $fd, StreamDescriptor $descriptor): array {
                    $val = $descriptor->toDescriptor();
                    if ($val !== null) {
                        $carry[$fd->toInt()] = $val;
                    }
                    return $carry;
                },
                []
            );
    }

    /**
     * Creates a pipeline connection from this process's stdout to another's stdin.
     */
    public function pipeTo(self $target): self
    {
        $stdout = $this->descriptors->get(FileDescriptor::stdout());

        return match (true) {
            $stdout->isSome() && $stdout->unwrap()->isPipe() => $target->withStdin($stdout->unwrap()),
            default => $target,
        };
    }
}
