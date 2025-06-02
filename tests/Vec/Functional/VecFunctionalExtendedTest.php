<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Vec\Functional;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Vec\Vec;
use Jsadaa\PhpCoreLibrary\Modules\Option\Option;
use PHPUnit\Framework\TestCase;

final class VecFunctionalExtendedTest extends TestCase
{
    public function testDataValidationWithAllAny(): void
    {
        $formSubmissions = Vec::from(
            ['name' => 'John Doe', 'age' => 17, 'email' => 'john@example.com', 'termsAccepted' => true],
            ['name' => 'Jane Smith', 'age' => 25, 'email' => 'jane@example.com', 'termsAccepted' => true],
            ['name' => 'Bob Johnson', 'age' => 42, 'email' => 'bob@example.com', 'termsAccepted' => false],
            ['name' => 'Alice Brown', 'age' => 30, 'email' => 'alice@example.com', 'termsAccepted' => true],
            ['name' => 'Charlie Davis', 'age' => 16, 'email' => 'charlie@invalid', 'termsAccepted' => true],
        );

        $isAdult = static fn($submission) => $submission['age'] >= 18;
        $hasValidEmail = static fn($submission) => \filter_var($submission['email'], \FILTER_VALIDATE_EMAIL) !== false;
        $hasAcceptedTerms = static fn($submission) => $submission['termsAccepted'] === true;

        $validSubmissions = $formSubmissions->filter(static function($submission) use ($isAdult, $hasValidEmail, $hasAcceptedTerms) {
            $requirements = Vec::from($isAdult($submission), $hasValidEmail($submission), $hasAcceptedTerms($submission));

            return $requirements->all(static fn($req) => $req === true);
        });

        $this->assertSame(2, $validSubmissions->len()->toInt());
        $this->assertSame('Jane Smith', $validSubmissions->get(0)->unwrapOr(null)['name']);
        $this->assertSame('Alice Brown', $validSubmissions->get(1)->unwrapOr(null)['name']);

        $hasAnyInvalidEmail = $formSubmissions->any(static fn($submission) => !$hasValidEmail($submission));
        $this->assertTrue($hasAnyInvalidEmail);

        $adultSubmissions = $formSubmissions->filter($isAdult);
        $allAdultsAcceptedTerms = $adultSubmissions->all($hasAcceptedTerms);
        $this->assertFalse($allAdultsAcceptedTerms);
    }

    public function testVecEqualityInDataProcessing(): void
    {
        $stockA = Vec::from(145.30, 147.80, 146.90, 149.20, 148.80);
        $stockB = Vec::from(145.30, 147.80, 146.90, 149.20, 148.80);
        $stockC = Vec::from(245.10, 242.50, 240.30, 247.80, 251.20);

        $expectedPattern = Vec::from(145.30, 147.80, 146.90, 149.20, 148.80);

        $matchingStocks = Vec::from(
            ['name' => 'Stock A', 'data' => $stockA],
            ['name' => 'Stock B', 'data' => $stockB],
            ['name' => 'Stock C', 'data' => $stockC],
        )->filter(static fn($stock) => $stock['data']->eq($expectedPattern));

        $this->assertSame(2, $matchingStocks->len()->toInt());

        $matchingNames = $matchingStocks->map(static fn($stock) => $stock['name'])->toArray();
        $this->assertSame(['Stock A', 'Stock B'], $matchingNames);

        $slightlyDifferentStock = Vec::from(145.30, 147.81, 146.90, 149.20, 148.80); // Small difference
        $this->assertFalse($stockA->eq($slightlyDifferentStock));
    }

    public function testDataStreamZipping(): void
    {
        // Simulated sensor data from different sources that need to be combined
        $timestamps = Vec::from(1625097600, 1625097660, 1625097720, 1625097780, 1625097840);
        $temperatures = Vec::from(22.5, 23.0, 23.2, 23.4, 23.8);
        $humidity = Vec::from(48.2, 48.5, 48.7, 49.0, 49.2);

        // Zip timestamps with temperature and humidity for a combined dataset
        $sensorData = $timestamps
            ->zip($temperatures) // Combine timestamps with temperatures
            ->map(static function($pair) {
                return [
                    'timestamp' => $pair->get(0)->unwrap(),
                    'temperature' => $pair->get(1)->unwrap(),
                ];
            });

        // Add humidity data using a second zip operation
        $completeData = $sensorData
            ->zip($humidity)
            ->map(static function($pair) {
                $record = $pair->get(0)->unwrap();
                $record['humidity'] = $pair->get(1)->unwrap();

                return $record;
            });

        $this->assertSame(5, $completeData->len()->toInt());

        $firstRecord = $completeData->get(0)->unwrap();
        $this->assertSame(1625097600, $firstRecord['timestamp']);
        $this->assertSame(22.5, $firstRecord['temperature']);
        $this->assertSame(48.2, $firstRecord['humidity']);

        $shortTimestamps = $timestamps->take(3);
        $zippedShort = $shortTimestamps->zip($temperatures);
        $this->assertSame(3, $zippedShort->len()->toInt());
    }

    public function testDataProcessingWithBoundaryOperations(): void
    {
        // Simulated time series data with some outliers
        $timeSeriesData = Vec::from(999.9, 25.4, 26.1, 25.9, 26.3, 27.0, 26.8, 26.5, 999.9);

        // Check if first and last elements are outliers (999.9)
        $firstElement = $timeSeriesData->first()->unwrap();
        $lastElement = $timeSeriesData->last()->unwrap();

        $this->assertSame(999.9, $firstElement);
        $this->assertSame(999.9, $lastElement);

        // Remove outliers by truncating first and last elements
        $cleanedData = $timeSeriesData
            ->skip(1)
            ->truncate($timeSeriesData->len()->toInt() - 2);

        $this->assertSame(7, $cleanedData->len()->toInt());
        $this->assertSame(25.4, $cleanedData->first()->unwrap());
        $this->assertSame(26.5, $cleanedData->last()->unwrap());

        // Calculate statistics on cleaned data
        $sum = $cleanedData->fold(static fn($acc, $val) => $acc + $val, 0);
        $average = $sum / $cleanedData->len()->toInt();

        $this->assertEqualsWithDelta(26.3, $average, 0.1);

        // Reset for next analysis
        $resetData = $cleanedData->clear();
        $this->assertSame(0, $resetData->len()->toInt());
        $this->assertTrue($resetData->isEmpty());
    }

    public function testLogProcessingWithRangeOperations(): void
    {
        // Simulated log entries with timestamps and severity
        $logs = Vec::from(
            ['timestamp' => '2023-01-01 00:01:23', 'severity' => 'INFO', 'message' => 'Application started'],
            ['timestamp' => '2023-01-01 00:02:15', 'severity' => 'DEBUG', 'message' => 'User login attempt'],
            ['timestamp' => '2023-01-01 00:02:45', 'severity' => 'ERROR', 'message' => 'Database connection failed'],
            ['timestamp' => '2023-01-01 00:03:12', 'severity' => 'ERROR', 'message' => 'Retry database connection'],
            ['timestamp' => '2023-01-01 00:03:30', 'severity' => 'ERROR', 'message' => 'Retry database connection'],
            ['timestamp' => '2023-01-01 00:03:45', 'severity' => 'INFO', 'message' => 'Database connection established'],
            ['timestamp' => '2023-01-01 00:04:10', 'severity' => 'DEBUG', 'message' => 'User authenticated'],
            ['timestamp' => '2023-01-01 00:05:01', 'severity' => 'INFO', 'message' => 'User logged out'],
        );

        $initialLogs = $logs->take(3);
        $this->assertSame(3, $initialLogs->len()->toInt());
        $this->assertSame('Application started', $initialLogs->get(0)->unwrap()['message']);

        $laterLogs = $logs->skip(5);
        $this->assertSame(3, $laterLogs->len()->toInt());
        $this->assertSame('Database connection established', $laterLogs->get(0)->unwrap()['message']);

        $beforeFirstError = $logs->takeWhile(static fn($log) => $log['severity'] !== 'ERROR');
        $this->assertSame(2, $beforeFirstError->len()->toInt());

        $preErrorLogs = $logs->takeWhile(static fn($log) => $log['severity'] !== 'ERROR');
        $remainingLogs = $logs->skip($preErrorLogs->len()); // Skip to first error
        $postErrorLogs = $remainingLogs->skipWhile(static fn($log) => $log['severity'] === 'ERROR');

        $this->assertSame(3, $postErrorLogs->len()->toInt());
        $this->assertSame('Database connection established', $postErrorLogs->first()->unwrap()['message']);
    }

    public function testArrayManipulationForSorting(): void
    {
        // Simulate implementing a sort algorithm with Vec operations
        $data = Vec::from(7, 2, 5, 8, 3, 1, 6, 4);

        $reversedData = $data->reverse();
        $this->assertSame([4, 6, 1, 3, 8, 5, 2, 7], $reversedData->toArray());

        // Manual bubble sort implementation using swap
        $sortedData = $this->bubbleSortUsingVec($data);
        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 8], $sortedData->toArray());

        $modifiedData = $data->swap(0, $data->len()->sub(1)->toInt())->unwrap(); // Swap first and last elements
        $this->assertSame(4, $modifiedData->get(0)->unwrap());
        $this->assertSame(7, $modifiedData->get($modifiedData->len()->toInt() - 1)->unwrap());
    }

    public function testIterationAndDisplayProcessing(): void
    {
        // Simulate generating a formatted report
        $employees = Vec::from(
            ['name' => 'John Doe', 'department' => 'Engineering', 'years' => 5],
            ['name' => 'Jane Smith', 'department' => 'Marketing', 'years' => 7],
            ['name' => 'Bob Johnson', 'department' => 'Finance', 'years' => 3],
        );

        $output = '';
        $employees->forEach(static function($employee) use (&$output) {
            $output .= "Name: {$employee['name']}, Dept: {$employee['department']}\n";
        });

        $expectedOutput = "Name: John Doe, Dept: Engineering\n" .
                         "Name: Jane Smith, Dept: Marketing\n" .
                         "Name: Bob Johnson, Dept: Finance\n";
        $this->assertSame($expectedOutput, $output);

        $reportLines = [];

        foreach ($employees->iter() as $employee) {
            $reportLines[] = "{$employee['name']} ({$employee['years']} years)";
        }

        $this->assertSame(
            'John Doe (5 years), Jane Smith (7 years), Bob Johnson (3 years)',
            \implode(', ', $reportLines),
        );
    }

    public function testHandlingSparseDataWithOptionals(): void
    {
        // Simulated API response with some data gaps (missing values represented as null)
        $apiData = Vec::from(
            ['id' => 1, 'value' => 42, 'metadata' => ['valid' => true]],
            ['id' => 2, 'value' => null, 'metadata' => ['valid' => false]],
            ['id' => 3, 'value' => 28, 'metadata' => null],
            ['id' => 4, 'value' => 35, 'metadata' => ['valid' => true]],
            ['id' => 5, 'value' => null, 'metadata' => null],
        );

        $validValues = $apiData->filterMap(static function($item) {
            $hasValue = $item['value'] !== null;
            $isValid = isset($item['metadata']['valid']) && $item['metadata']['valid'] === true;

            return ($hasValue && $isValid)
                ? Option::some(['id' => $item['id'], 'value' => $item['value']])
                : Option::none();
        });

        $this->assertSame(2, $validValues->len()->toInt());
        $this->assertSame(1, $validValues->get(0)->unwrap()['id']);
        $this->assertSame(4, $validValues->get(1)->unwrap()['id']);

        $firstHighValue = $apiData->findMap(static function($item) {
            if ($item['value'] !== null && $item['value'] > 40) {
                return Option::some(['id' => $item['id'], 'value' => $item['value']]);
            }

            return Option::none();
        });

        $this->assertTrue($firstHighValue->isSome());
        $this->assertSame(1, $firstHighValue->unwrap()['id']);
        $this->assertSame(42, $firstHighValue->unwrap()['value']);
    }

    /**
     * Implement a bubble sort using Vec's swap method
     *
     * @param Vec<int> $data The data to sort
     * @return Vec<int> The sorted data
     */
    private function bubbleSortUsingVec(Vec $data): Vec
    {
        $len = $data->len()->toInt();
        $result = $data; // Clone of original data

        for ($i = 0; $i < $len; $i++) {
            for ($j = 0; $j < $len - $i - 1; $j++) {
                $current = $result->get($j)->unwrap();
                $next = $result->get($j + 1)->unwrap();

                if ($current > $next) {
                    $result = $result->swap($j, $j + 1)->unwrap();
                }
            }
        }

        return $result;
    }
}
