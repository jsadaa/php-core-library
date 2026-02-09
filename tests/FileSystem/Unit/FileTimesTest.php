<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\FileSystem\Unit;

use Jsadaa\PhpCoreLibrary\Modules\FileSystem\FileTimes;
use Jsadaa\PhpCoreLibrary\Modules\Time\SystemTime;
use PHPUnit\Framework\TestCase;

final class FileTimesTest extends TestCase
{
    public function testCreateFileTimes(): void
    {
        $accessTime = 1609459200; // 2021-01-01 00:00:00
        $modificationTime = 1609545600; // 2021-01-02 00:00:00
        $creationTime = 1609372800; // 2020-12-31 00:00:00

        $fileTimes = FileTimes::new()
            ->setAccessed(SystemTime::fromDateTimeImmutable(
                (new \DateTimeImmutable())->setTimestamp($accessTime),
            )->unwrap())
            ->setModified(SystemTime::fromDateTimeImmutable(
                (new \DateTimeImmutable())->setTimestamp($modificationTime),
            )->unwrap())
            ->setCreated(SystemTime::fromDateTimeImmutable(
                (new \DateTimeImmutable())->setTimestamp($creationTime),
            )->unwrap());

        $this->assertSame($accessTime, $fileTimes->accessed()->unwrap()->seconds());
        $this->assertSame($modificationTime, $fileTimes->modified()->unwrap()->seconds());
        $this->assertSame($creationTime, $fileTimes->created()->unwrap()->seconds());
    }

    public function testAccessTimeAsDateTime(): void
    {
        $accessTime = 1609459200; // 2021-01-01 00:00:00
        $modificationTime = 1609545600; // 2021-01-02 00:00:00
        $creationTime = 1609372800; // 2020-12-31 00:00:00

        $fileTimes = FileTimes::new()
            ->setAccessed(SystemTime::fromDateTimeImmutable(
                (new \DateTimeImmutable())->setTimestamp($accessTime),
            )->unwrap())
            ->setModified(SystemTime::fromDateTimeImmutable(
                (new \DateTimeImmutable())->setTimestamp($modificationTime),
            )->unwrap())
            ->setCreated(SystemTime::fromDateTimeImmutable(
                (new \DateTimeImmutable())->setTimestamp($creationTime),
            )->unwrap());

        $dateTime = $fileTimes
            ->accessed()
            ->unwrap()
            ->toDateTimeImmutable()
            ->unwrap();

        $this->assertInstanceOf(\DateTimeImmutable::class, $dateTime);
        $this->assertSame('2021-01-01T00:00:00+00:00', $dateTime->format(\DateTimeInterface::ATOM));
    }

    public function testModificationTimeAsDateTime(): void
    {
        $accessTime = 1609459200; // 2021-01-01 00:00:00
        $modificationTime = 1609545600; // 2021-01-02 00:00:00
        $creationTime = 1609372800; // 2020-12-31 00:00:00

        $fileTimes = FileTimes::new()
            ->setAccessed(SystemTime::fromDateTimeImmutable(
                (new \DateTimeImmutable())->setTimestamp($accessTime),
            )->unwrap())
            ->setModified(SystemTime::fromDateTimeImmutable(
                (new \DateTimeImmutable())->setTimestamp($modificationTime),
            )->unwrap())
            ->setCreated(SystemTime::fromDateTimeImmutable(
                (new \DateTimeImmutable())->setTimestamp($creationTime),
            )->unwrap());

        $dateTime = $fileTimes
            ->modified()
            ->unwrap()
            ->toDateTimeImmutable()
            ->unwrap();

        $this->assertInstanceOf(\DateTimeImmutable::class, $dateTime);
        $this->assertSame('2021-01-02T00:00:00+00:00', $dateTime->format(\DateTimeInterface::ATOM));
    }

    public function testCreationTimeAsDateTime(): void
    {
        $accessTime = 1609459200; // 2021-01-01 00:00:00
        $modificationTime = 1609545600; // 2021-01-02 00:00:00
        $creationTime = 1609372800; // 2020-12-31 00:00:00

        $fileTimes = FileTimes::new()
            ->setAccessed(SystemTime::fromDateTimeImmutable(
                (new \DateTimeImmutable())->setTimestamp($accessTime),
            )->unwrap())
            ->setModified(SystemTime::fromDateTimeImmutable(
                (new \DateTimeImmutable())->setTimestamp($modificationTime),
            )->unwrap())
            ->setCreated(SystemTime::fromDateTimeImmutable(
                (new \DateTimeImmutable())->setTimestamp($creationTime),
            )->unwrap());

        $dateTime = $fileTimes
            ->created()
            ->unwrap()
            ->toDateTimeImmutable()
            ->unwrap();

        $this->assertInstanceOf(\DateTimeImmutable::class, $dateTime);
        $this->assertSame('2020-12-31T00:00:00+00:00', $dateTime->format(\DateTimeInterface::ATOM));
    }

    public function testEqualityComparison(): void
    {
        $time1 = 1609459200;
        $time2 = 1609545600;

        $fileTimes1 = FileTimes::new()
            ->setAccessed(SystemTime::fromDateTimeImmutable(
                (new \DateTimeImmutable())->setTimestamp($time1),
            )->unwrap())
            ->setModified(SystemTime::fromDateTimeImmutable(
                (new \DateTimeImmutable())->setTimestamp($time1),
            )->unwrap())
            ->setCreated(SystemTime::fromDateTimeImmutable(
                (new \DateTimeImmutable())->setTimestamp($time1),
            )->unwrap());

        $fileTimes2 = FileTimes::new()
            ->setAccessed(SystemTime::fromDateTimeImmutable(
                (new \DateTimeImmutable())->setTimestamp($time1),
            )->unwrap())
            ->setModified(SystemTime::fromDateTimeImmutable(
                (new \DateTimeImmutable())->setTimestamp($time1),
            )->unwrap())
            ->setCreated(SystemTime::fromDateTimeImmutable(
                (new \DateTimeImmutable())->setTimestamp($time1),
            )->unwrap());

        $fileTimes3 = FileTimes::new()
            ->setAccessed(SystemTime::fromDateTimeImmutable(
                (new \DateTimeImmutable())->setTimestamp($time2),
            )->unwrap())
            ->setModified(SystemTime::fromDateTimeImmutable(
                (new \DateTimeImmutable())->setTimestamp($time2),
            )->unwrap())
            ->setCreated(SystemTime::fromDateTimeImmutable(
                (new \DateTimeImmutable())->setTimestamp($time2),
            )->unwrap());

        $this->assertEquals($fileTimes1->accessed(), $fileTimes2->accessed());
        $this->assertEquals($fileTimes1->modified(), $fileTimes2->modified());
        $this->assertEquals($fileTimes1->created(), $fileTimes2->created());

        $this->assertNotEquals($fileTimes1->accessed(), $fileTimes3->accessed());
    }
}
