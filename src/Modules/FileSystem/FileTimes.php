<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\FileSystem;

use Jsadaa\PhpCoreLibrary\Modules\Option\Option;
use Jsadaa\PhpCoreLibrary\Modules\Time\SystemTime;

/**
 * Represents the various timestamps that can be set on a file.
 *
 * This class provides a way to specify timestamps for files without directly
 * modifying the file. It allows you to create timestamp collections for
 * access time, modification time, and creation time that can later be
 * applied to a file using File::setTimes().
 *
 * Creation time is only supported on certain platforms (Windows, macOS)
 * and may be ignored on Unix/Linux systems.
 *
 * @psalm-immutable
 */
final readonly class FileTimes
{
    /**
     * @var Option<SystemTime> The access time to set
     */
    private Option $accessed;

    /**
     * @var Option<SystemTime> The modified time to set
     */
    private Option $modified;

    /**
     * @var Option<SystemTime> The creation time to set (only used on certain platforms)
     */
    private Option $created;

    /**
     * @param Option<SystemTime> $accessed The access time to set
     * @param Option<SystemTime> $modified The modified time to set
     * @param Option<SystemTime> $created The creation time to set (only used on certain platforms)
     */
    private function __construct(
        Option $accessed,
        Option $modified,
        Option $created,
    ) {
        $this->accessed = $accessed;
        $this->modified = $modified;
        $this->created = $created;
    }

    /**
     * Create a new FileTimes with no times set
     *
     * Using the resulting FileTimes in File::setTimes() will not modify any timestamps.
     * This serves as the starting point for creating a customized set of timestamps.
     *
     * @return self New FileTimes instance with no times set
     * @psalm-pure
     */
    public static function new(): self
    {
        return new self(Option::none(), Option::none(), Option::none());
    }

    /**
     * Set the last access time
     *
     * Creates a new FileTimes instance with the specified access time,
     * preserving any other timestamps that were already set.
     *
     * @param SystemTime $time The new access time
     * @return self A new FileTimes with the access time set
     */
    public function setAccessed(SystemTime $time): self
    {
        return new self(Option::some($time), $this->modified, $this->created);
    }

    /**
     * Set the last modified time
     *
     * Creates a new FileTimes instance with the specified modification time,
     * preserving any other timestamps that were already set.
     *
     * @param SystemTime $time The new modified time
     * @return self A new FileTimes with the modified time set
     */
    public function setModified(SystemTime $time): self
    {
        return new self($this->accessed, Option::some($time), $this->created);
    }

    /**
     * Set the creation time
     *
     * Creates a new FileTimes instance with the specified creation time,
     * preserving any other timestamps that were already set.
     *
     * This is only supported on some platforms (Windows, macOS).
     * On Unix/Linux systems, this setting may be ignored as the creation time
     * is often not maintained or accessible.
     *
     * @param SystemTime $time The new creation time
     * @return self A new FileTimes with the creation time set
     */
    public function setCreated(SystemTime $time): self
    {
        return new self($this->accessed, $this->modified, Option::some($time));
    }

    /**
     * Get the access time, if it was set
     *
     * @return Option<SystemTime> The access time, or None if not set
     */
    public function accessed(): Option
    {
        return $this->accessed;
    }

    /**
     * Get the modified time, if it was set
     *
     * @return Option<SystemTime> The modified time, or None if not set
     */
    public function modified(): Option
    {
        return $this->modified;
    }

    /**
     * Get the creation time, if it was set
     *
     * @return Option<SystemTime> The creation time, or None if not set
     */
    public function created(): Option
    {
        return $this->created;
    }
}
