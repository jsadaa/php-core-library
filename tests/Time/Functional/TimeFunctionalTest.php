<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Time\Functional;

use Jsadaa\PhpCoreLibrary\Modules\Time\Duration;
use Jsadaa\PhpCoreLibrary\Modules\Time\SystemTime;
use PHPUnit\Framework\TestCase;

final class TimeFunctionalTest extends TestCase
{
    public function testTimeMeasurementWorkflow(): void
    {
        // Simulate measuring execution time of operations
        $operations = [
            'fast' => static function() { \usleep(1000); }, // 1ms
            'medium' => static function() { \usleep(5000); }, // 5ms
            'slow' => static function() { \usleep(10000); }, // 10ms
        ];

        $results = [];

        foreach ($operations as $name => $operation) {
            $start = SystemTime::now();
            $operation();
            $end = SystemTime::now();

            $duration = $end->durationSince($start)->unwrap();
            $results[$name] = $duration;

            $this->assertGreaterThan(0, $duration->toNanos());
            $this->assertLessThan(100_000_000, $duration->toNanos()); // Less than 100ms
        }

        $fastMs = $results['fast']->toNanos() / 1_000_000;
        $mediumMs = $results['medium']->toNanos() / 1_000_000;
        $slowMs = $results['slow']->toNanos() / 1_000_000;

        $this->assertLessThan($slowMs, $fastMs); // fast should be much less than slow
        $this->assertLessThan($slowMs, $mediumMs); // medium should be less than slow
    }

    public function testSchedulingAndTimeouts(): void
    {
        // Simulate a scheduling system with timeouts
        $now = SystemTime::now();

        $tasks = [
            ['name' => 'immediate', 'delay' => Duration::zero()],
            ['name' => 'short', 'delay' => Duration::fromMillis(10)],
            ['name' => 'medium', 'delay' => Duration::fromSeconds(1)],
            ['name' => 'long', 'delay' => Duration::fromMins(5)],
        ];

        $scheduledTasks = [];

        foreach ($tasks as $task) {
            $scheduledTime = $now->add($task['delay'])->unwrap();
            $scheduledTasks[] = [
                'name' => $task['name'],
                'scheduled_time' => $scheduledTime,
                'delay' => $task['delay'],
            ];
        }

        $this->assertEquals('immediate', $scheduledTasks[0]['name']);
        $this->assertEquals('short', $scheduledTasks[1]['name']);
        $this->assertEquals('medium', $scheduledTasks[2]['name']);
        $this->assertEquals('long', $scheduledTasks[3]['name']);

        $immediateTime = $scheduledTasks[0]['scheduled_time'];
        $longTime = $scheduledTasks[3]['scheduled_time'];

        $totalDelay = $longTime->durationSince($immediateTime)->unwrap();
        $expectedDelay = Duration::fromMins(5);

        // Should be approximately the same (allowing for small precision differences)
        $diff = $totalDelay->absDiff($expectedDelay);
        $this->assertLessThan(1000, $diff->toNanos()); // Less than 1 microsecond difference
    }

    public function testRateLimitingSimulation(): void
    {
        // Simulate a rate limiting system
        $rateLimitWindow = Duration::fromSeconds(1);
        $maxRequests = 5;

        $requests = [];

        // Simulate requests coming in
        for ($i = 0; $i < 10; $i++) {
            $requestTime = SystemTime::now();

            // Clean up old requests outside the window
            $requests = \array_filter($requests, static function($request) use ($requestTime, $rateLimitWindow) {
                $ageResult = $requestTime->durationSince($request);

                if ($ageResult->isErr()) {
                    return false; // If we can't calculate age, consider it old
                }
                $age = $ageResult->unwrap();

                return $age->lt($rateLimitWindow);
            });

            if (\count($requests) < $maxRequests) {
                $requests[] = $requestTime;
                $allowed = true;
            } else {
                $allowed = false;
            }

            // For the first 5 requests, they should be allowed
            if ($i < 5) {
                $this->assertTrue($allowed, "Request $i should be allowed");
            }

            \usleep(100_000); // 100ms between requests
        }

        $this->assertLessThanOrEqual($maxRequests, \count($requests));
    }

    public function testPerformanceBenchmarking(): void
    {
        // Simulate benchmarking different algorithms
        $algorithms = [
            'bubble_sort' => static function(array $data) {
                $n = \count($data);

                for ($i = 0; $i < $n - 1; $i++) {
                    for ($j = 0; $j < $n - $i - 1; $j++) {
                        if ($data[$j] > $data[$j + 1]) {
                            [$data[$j], $data[$j + 1]] = [$data[$j + 1], $data[$j]];
                        }
                    }
                }

                return $data;
            },
            'php_sort' => static function(array $data) {
                \sort($data);

                return $data;
            },
        ];

        $testData = \range(1, 100);
        \shuffle($testData);

        $benchmarkResults = [];

        foreach ($algorithms as $name => $algorithm) {
            $iterations = 10;
            $totalDuration = Duration::zero();

            for ($i = 0; $i < $iterations; $i++) {
                $start = SystemTime::now();
                $algorithm($testData);
                $end = SystemTime::now();

                $duration = $end->durationSince($start)->unwrap();
                $totalDuration = $totalDuration->add($duration)->unwrap();
            }

            $averageDuration = $totalDuration->div($iterations)->unwrap();
            $benchmarkResults[$name] = $averageDuration;
        }

        // PHP's built-in sort should be faster than bubble sort
        $this->assertTrue(
            $benchmarkResults['php_sort']->lt($benchmarkResults['bubble_sort']),
            'PHP sort should be faster than bubble sort',
        );

        // Both should complete in reasonable time (less than 1 second average)
        foreach ($benchmarkResults as $name => $duration) {
            $this->assertLessThan(
                1_000_000_000,
                $duration->toNanos(),
                "$name should complete in less than 1 second on average",
            );
        }
    }

    public function testTimeZoneHandling(): void
    {
        // Test creating times in different time zones and converting
        $utcTime = new \DateTimeImmutable('2023-06-15 12:00:00 UTC');
        $parisTime = new \DateTimeImmutable('2023-06-15 14:00:00 Europe/Paris'); // Same moment, different timezone
        $tokyoTime = new \DateTimeImmutable('2023-06-15 21:00:00 Asia/Tokyo'); // Same moment, different timezone

        $systemUtc = SystemTime::fromDateTimeImmutable($utcTime)->unwrap();
        $systemParis = SystemTime::fromDateTimeImmutable($parisTime)->unwrap();
        $systemTokyo = SystemTime::fromDateTimeImmutable($tokyoTime)->unwrap();

        $this->assertTrue($systemUtc->eq($systemParis));
        $this->assertTrue($systemUtc->eq($systemTokyo));
        $this->assertTrue($systemParis->eq($systemTokyo));

        $backToDateTime = $systemUtc->toDateTimeImmutable()->unwrap();
        $this->assertEquals('2023-06-15 12:00:00', $backToDateTime->format('Y-m-d H:i:s'));
        // UTC timezone might be represented as '+00:00' in some PHP versions
        $this->assertTrue(\in_array($backToDateTime->getTimezone()->getName(), ['UTC', '+00:00'], true));
    }

    public function testDurationCalculationsWorkflow(): void
    {
        // Simulate calculating work hours, breaks, and overtime
        $workDayStart = SystemTime::fromTimestamp(\mktime(9, 0, 0, 6, 15, 2023)); // 9 AM

        $breaks = [
            ['start' => Duration::fromHours(2), 'duration' => Duration::fromMins(15)], // 11 AM, 15 min break
            ['start' => Duration::fromHours(4), 'duration' => Duration::fromHours(1)], // 1 PM, 1 hour lunch
            ['start' => Duration::fromHours(6)->add(Duration::fromMins(30))->unwrap(), 'duration' => Duration::fromMins(15)], // 3:30 PM, 15 min break
        ];

        $workDayEnd = $workDayStart->add(Duration::fromHours(9))->unwrap(); // 6 PM

        $totalBreakTime = Duration::zero();

        foreach ($breaks as $break) {
            $totalBreakTime = $totalBreakTime->add($break['duration'])->unwrap();
        }

        $totalTime = $workDayEnd->durationSince($workDayStart)->unwrap();
        $actualWorkTime = $totalTime->sub($totalBreakTime)->unwrap();

        $this->assertEquals(9 * 3600, $totalTime->toSeconds()); // 9 hours total
        $this->assertEquals((15 + 60 + 15) * 60, $totalBreakTime->toSeconds()); // 1.5 hours breaks
        $this->assertEquals((9 * 60 - 90) * 60, $actualWorkTime->toSeconds()); // 7.5 hours work

        $standardWorkDay = Duration::fromHours(8);

        if ($actualWorkTime->gt($standardWorkDay)) {
            $overtime = $actualWorkTime->sub($standardWorkDay)->unwrap();
            $this->assertEquals(0, $overtime->toSeconds()); // No overtime in this case
        } else {
            $shortfall = $standardWorkDay->sub($actualWorkTime)->unwrap();
            $this->assertEquals(30 * 60, $shortfall->toSeconds()); // 30 minutes short
        }
    }

    public function testAgeCalculation(): void
    {
        // Test calculating ages and time periods
        $birthDate = new \DateTimeImmutable('1990-06-15 10:30:00 UTC');
        $currentDate = new \DateTimeImmutable('2023-06-15 15:45:30 UTC');

        $birthTime = SystemTime::fromDateTimeImmutable($birthDate)->unwrap();
        $currentTime = SystemTime::fromDateTimeImmutable($currentDate)->unwrap();

        $age = $currentTime->durationSince($birthTime)->unwrap();

        // Calculate years (approximately)
        $yearsInSeconds = 365.25 * 24 * 3600; // Account for leap years
        $ageInYears = (float)$age->toSeconds() / $yearsInSeconds;

        $this->assertGreaterThan(32, $ageInYears);
        $this->assertLessThan(34, $ageInYears);

        $totalDays = $age->toSeconds() / (24 * 3600);
        $this->assertGreaterThan(12000, $totalDays); // More than 12000 days
        $this->assertLessThan(13000, $totalDays); // Less than 13000 days
    }

    public function testCachingWithTTL(): void
    {
        // Simulate a cache system with time-to-live
        $cache = [];
        $defaultTTL = Duration::fromSeconds(2);

        $setValue = static function(string $key, $value, ?Duration $ttl = null) use (&$cache, $defaultTTL) {
            $ttl = $ttl ?? $defaultTTL;
            $cache[$key] = [
                'value' => $value,
                'expires_at' => SystemTime::now()->add($ttl)->unwrap(),
            ];
        };

        $getValue = static function(string $key) use (&$cache) {
            if (!isset($cache[$key])) {
                return;
            }

            $entry = $cache[$key];
            $now = SystemTime::now();

            if ($now->gt($entry['expires_at'])) {
                unset($cache[$key]);

                return; // Expired
            }

            return $entry['value'];
        };

        $setValue('fast', 'quick_data', Duration::fromMillis(500));
        $setValue('normal', 'normal_data'); // Uses default TTL
        $setValue('long', 'persistent_data', Duration::fromSeconds(5));

        $this->assertEquals('quick_data', $getValue('fast'));
        $this->assertEquals('normal_data', $getValue('normal'));
        $this->assertEquals('persistent_data', $getValue('long'));

        \usleep(600_000); // 600ms

        $this->assertNull($getValue('fast')); // Should be expired
        $this->assertEquals('normal_data', $getValue('normal')); // Should still be valid
        $this->assertEquals('persistent_data', $getValue('long')); // Should still be valid

        // Wait for normal cache to expire
        \usleep(1_500_000); // Additional 1.5s (total 2.1s)

        $this->assertNull($getValue('fast')); // Still expired
        $this->assertNull($getValue('normal')); // Should now be expired
        $this->assertEquals('persistent_data', $getValue('long')); // Should still be valid
    }

    public function testEventTimeline(): void
    {
        // Simulate tracking events over time
        $startTime = SystemTime::now();
        $events = [];

        $addEvent = static function(string $type, string $description, ?Duration $delay = null) use (&$events, $startTime) {
            $eventTime = $delay ? $startTime->add($delay)->unwrap() : SystemTime::now();
            $events[] = [
                'type' => $type,
                'description' => $description,
                'timestamp' => $eventTime,
                'relative_time' => $eventTime->durationSince($startTime)->unwrap(),
            ];
        };

        $addEvent('start', 'Process started');
        $addEvent('init', 'Initialization complete', Duration::fromMillis(100));
        $addEvent('work', 'Work phase started', Duration::fromMillis(250));
        $addEvent('checkpoint', 'Checkpoint reached', Duration::fromSeconds(1));
        $addEvent('complete', 'Process completed', Duration::fromSeconds(2));

        for ($i = 1; $i < \count($events); $i++) {
            $this->assertTrue(
                $events[$i]['timestamp']->ge($events[$i-1]['timestamp']),
                "Event {$i} should be at or after event " . ($i-1),
            );
        }

        $totalDuration = $events[\count($events)-1]['relative_time'];
        $this->assertEquals(2, $totalDuration->toSeconds());

        $earlyEvents = \array_filter($events, static function($event) {
            return $event['relative_time']->lt(Duration::fromMillis(500));
        });

        $this->assertCount(3, $earlyEvents); // start, init, work

        $lateEvents = \array_filter($events, static function($event) {
            return $event['relative_time']->gt(Duration::fromMillis(500));
        });

        $this->assertCount(2, $lateEvents); // checkpoint, complete
    }

    public function testTimeBasedDecisions(): void
    {
        // Simulate making decisions based on time conditions
        $businessHoursStart = 9; // 9 AM
        $businessHoursEnd = 17;  // 5 PM

        $testTimes = [
            ['hour' => 8, 'expected' => 'before_hours'],
            ['hour' => 10, 'expected' => 'business_hours'],
            ['hour' => 12, 'expected' => 'business_hours'],
            ['hour' => 18, 'expected' => 'after_hours'],
            ['hour' => 22, 'expected' => 'after_hours'],
        ];

        foreach ($testTimes as $test) {
            $dateTime = new \DateTimeImmutable(\sprintf('2023-06-15 %02d:00:00 UTC', $test['hour']));
            $systemTime = SystemTime::fromDateTimeImmutable($dateTime)->unwrap();

            $backToDateTime = $systemTime->toDateTimeImmutable()->unwrap();
            $hour = (int)$backToDateTime->format('H');

            if ($hour < $businessHoursStart) {
                $category = 'before_hours';
            } elseif ($hour >= $businessHoursEnd) {
                $category = 'after_hours';
            } else {
                $category = 'business_hours';
            }

            $this->assertEquals($test['expected'], $category, "Hour {$test['hour']} should be {$test['expected']}");
        }
    }

    public function testDurationArithmetic(): void
    {
        // Test complex duration calculations for project planning
        $tasks = [
            'analysis' => Duration::fromDays(3),
            'design' => Duration::fromDays(5),
            'development' => Duration::fromWeeks(2),
            'testing' => Duration::fromDays(4),
            'deployment' => Duration::fromHours(8),
        ];

        $totalDuration = Duration::zero();

        foreach ($tasks as $duration) {
            $totalDuration = $totalDuration->add($duration)->unwrap();
        }

        $this->assertEquals(3 * 24 * 3600, $tasks['analysis']->toSeconds());
        $this->assertEquals(5 * 24 * 3600, $tasks['design']->toSeconds());
        $this->assertEquals(14 * 24 * 3600, $tasks['development']->toSeconds());
        $this->assertEquals(4 * 24 * 3600, $tasks['testing']->toSeconds());
        $this->assertEquals(8 * 3600, $tasks['deployment']->toSeconds());

        // Total should be 26 days and 8 hours
        $expectedSeconds = (26 * 24 + 8) * 3600;
        $this->assertEquals($expectedSeconds, $totalDuration->toSeconds());

        // Calculate with 20% buffer
        $bufferSeconds = (int)((float)$totalDuration->toSeconds() * 0.2); // 20% buffer
        $bufferDuration = Duration::fromSeconds($bufferSeconds);
        $totalWithBuffer = $totalDuration->add($bufferDuration)->unwrap();

        // Should be 120% of original (approximately)
        $expectedWithBuffer = ($expectedSeconds * 1.2);
        $actualWithBuffer = (float)$totalWithBuffer->toSeconds();
        $this->assertEqualsWithDelta($expectedWithBuffer, $actualWithBuffer, $expectedSeconds * 0.1); // Allow 10% tolerance
    }

    public function testHighPrecisionTiming(): void
    {
        // Test nanosecond precision operations
        $start = SystemTime::now();

        // Perform a very quick operation
        $x = 0;

        for ($i = 0; $i < 1000; $i++) {
            $x += $i;
        }

        $end = SystemTime::now();
        $duration = $end->durationSince($start)->unwrap();

        $this->assertGreaterThan(0, $duration->toNanos());
        $this->assertLessThan(1_000_000, $duration->toNanos()); // Less than 1ms

        $nano1 = Duration::fromNanos(123_456_789);
        $nano2 = Duration::fromNanos(987_654_321);
        $sum = $nano1->add($nano2)->unwrap();

        $this->assertEquals(123_456_789 + 987_654_321, $sum->toNanos());
        $this->assertEquals(1, $sum->toSeconds());
        $this->assertEquals(111_111_110, $sum->subsecNanos());
    }
}
