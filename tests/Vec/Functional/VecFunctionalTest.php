<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Vec\Functional;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Vec\Vec;
use PHPUnit\Framework\TestCase;

final class VecFunctionalTest extends TestCase
{
    public function testComplexNumberProcessing(): void
    {
        $numbers = Vec::from(1, -2, 3, -4, 5, -6, 7, -8, 9, -10);

        $result = $numbers
            ->map(static fn($n) => \abs($n))                   // [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
            ->filter(static fn($n) => $n % 2 === 0)           // [2, 4, 6, 8, 10]
            ->map(static fn($n) => $n * 2)                    // [4, 8, 12, 16, 20]
            ->fold(static fn($sum, $n) => $sum + $n, 0);    // 60

        $this->assertSame(60, $result);
    }

    public function testTextProcessing(): void
    {
        $sentences = Vec::from(
            'Hello world',
            'PHP is great',
            'Simple collections',
            'Vector implementation',
            'Functional programming',
        );

        $result = $sentences
            ->map(static fn($sentence) => \explode(' ', $sentence))
            ->flatten()
            ->filter(static fn($word) => \strlen($word) > 4)
            ->map(static fn($word) => \strtoupper($word))
            ->sortBy(static fn($a, $b) => \strlen($a) <=> \strlen($b))
            ->fold(static fn($acc, $word) => $acc ? "$acc, $word" : $word, '');

        $this->assertSame('HELLO, WORLD, GREAT, SIMPLE, VECTOR, FUNCTIONAL, COLLECTIONS, PROGRAMMING, IMPLEMENTATION', $result);
    }

    public function testComplexObjectManipulation(): void
    {
        $users = Vec::from(
            $this->createUser('Alice', 28, ['PHP', 'JavaScript', 'Python']),
            $this->createUser('Bob', 22, ['Java', 'C#']),
            $this->createUser('Charlie', 35, ['Rust', 'Go', 'C++']),
            $this->createUser('David', 42, ['PHP', 'Ruby']),
            $this->createUser('Eve', 19, ['JavaScript', 'TypeScript']),
        );

        // Find users who know PHP, are older than 25, get their names, and join them
        $phpDevelopers = $users
            ->filter(static fn($user) => $user->age > 25)
            ->filter(static fn($user) => \in_array('PHP', $user->skills, true))
            ->map(static fn($user) => $user->name)
            ->fold(static fn($acc, $name) => $acc ? "$acc and $name" : $name, '');

        $this->assertSame('Alice and David', $phpDevelopers);

        $uniqueSkillCount = $users
            ->flatMap(static fn($user) => $user->skills)
            ->dedup()
            ->len();

        $this->assertSame(10, $uniqueSkillCount->toInt());
    }

    public function testDataTransformationPipeline(): void
    {
        // Raw data: [userId, amount, status]
        $transactions = Vec::from(
            [1, 100.0, 'completed'],
            [2, 150.0, 'pending'],
            [1, 200.0, 'completed'],
            [3, 50.0, 'failed'],
            [2, 75.0, 'completed'],
            [1, 125.0, 'pending'],
            [3, 100.0, 'completed'],
        );

        $completedTransactions = $transactions
            ->filter(static fn($tx) => $tx[2] === 'completed')
            ->map(static fn($tx) => (object)['userId' => $tx[0], 'amount' => $tx[1]]);

        $userIds = $completedTransactions
            ->map(static fn($tx) => $tx->userId)
            ->dedup();

        $userSummaries = $userIds
            ->map(static function($userId) use ($completedTransactions) {
                $userTransactions = $completedTransactions
                    ->filter(static fn($tx) => $tx->userId === $userId);

                $totalAmount = $userTransactions
                    ->map(static fn($tx) => $tx->amount)
                    ->fold(static fn($sum, $amount) => $sum + $amount, 0);

                return (object)[
                    'userId' => $userId,
                    'totalAmount' => $totalAmount,
                ];
            })
            ->sortBy(static fn($a, $b) => $b->totalAmount <=> $a->totalAmount);

        $this->assertSame(3, $userSummaries->len()->toInt());

        $firstUser = $userSummaries->get(0)->match(static fn($u) => $u, static fn() => null);
        $this->assertSame(1, $firstUser->userId);
        $this->assertSame(300.0, $firstUser->totalAmount);

        $secondUser = $userSummaries->get(1)->match(static fn($u) => $u, static fn() => null);
        $this->assertSame(3, $secondUser->userId);
        $this->assertSame(100.0, $secondUser->totalAmount);

        $thirdUser = $userSummaries->get(2)->match(static fn($u) => $u, static fn() => null);
        $this->assertSame(2, $thirdUser->userId);
        $this->assertSame(75.0, $thirdUser->totalAmount);
    }

    public function testSlidingWindowAnalysis(): void
    {
        // Sample time series data (e.g., stock prices)
        $timeSeriesData = Vec::from(10, 12, 15, 14, 13, 17, 19, 20, 18, 22);

        // Calculate moving averages using windows
        $movingAverages = $timeSeriesData
            ->windows(3)
            ->map(static function($window) {
                // Calculate average of each window
                return $window->fold(static fn($sum, $value) => $sum + $value, 0) / $window->len()->toInt();
            });

        // Expected moving averages: (10+12+15)/3, (12+15+14)/3, etc.
        $expectedAverages = [
            (10 + 12 + 15) / 3,
            (12 + 15 + 14) / 3,
            (15 + 14 + 13) / 3,
            (14 + 13 + 17) / 3,
            (13 + 17 + 19) / 3,
            (17 + 19 + 20) / 3,
            (19 + 20 + 18) / 3,
            (20 + 18 + 22) / 3,
        ];

        $this->assertSame(\count($expectedAverages), $movingAverages->len()->toInt());

        for ($i = 0; $i < \count($expectedAverages); $i++) {
            $actual = $movingAverages->get($i)->match(static fn($v) => $v, static fn() => null);
            $this->assertEqualsWithDelta($expectedAverages[$i], $actual, 0.001);
        }

        // Find local maxima in the time series (points where the value is higher than both neighbors)
        $localMaxima = $timeSeriesData
            ->windows(3)
            ->filter(static function($window) {
                $values = $window->toArray();

                return $values[1] > $values[0] && $values[1] > $values[2];
            })
            ->map(static function($window) {
                $values = $window->toArray();

                return $values[1]; // Return the middle value (the local maximum)
            });

        $this->assertSame([15, 20], $localMaxima->toArray());
    }

    public function testNestedDataProcessing(): void
    {
        $departments = Vec::from(
            [
                'name' => 'Engineering',
                'employees' => Vec::from(
                    ['name' => 'Alice', 'salary' => 85000, 'skills' => ['PHP', 'SQL', 'JavaScript']],
                    ['name' => 'Bob', 'salary' => 92000, 'skills' => ['Java', 'Python', 'Kubernetes']],
                    ['name' => 'Charlie', 'salary' => 78000, 'skills' => ['PHP', 'DevOps', 'AWS']],
                ),
            ],
            [
                'name' => 'Marketing',
                'employees' => Vec::from(
                    ['name' => 'David', 'salary' => 72000, 'skills' => ['SEO', 'Content', 'Analytics']],
                    ['name' => 'Eve', 'salary' => 68000, 'skills' => ['Social Media', 'Copywriting']],
                ),
            ],
            [
                'name' => 'Product',
                'employees' => Vec::from(
                    ['name' => 'Frank', 'salary' => 89000, 'skills' => ['UX', 'Agile', 'Jira']],
                    ['name' => 'Grace', 'salary' => 95000, 'skills' => ['Product Strategy', 'Data Analysis']],
                    ['name' => 'Heidi', 'salary' => 82000, 'skills' => ['UX', 'UI', 'User Research']],
                ),
            ],
        );

        // Task 1: Find the average salary across all departments
        $allEmployees = $departments
            ->flatMap(static fn($dept) => $dept['employees'])
            ->toArray();

        $totalSalary = \array_reduce($allEmployees, static fn($sum, $emp) => $sum + $emp['salary'], 0);
        $averageSalary = $totalSalary / \count($allEmployees);

        $this->assertEqualsWithDelta(82625, $averageSalary, 0.01);

        // Task 2: Find employees who know PHP and have above-average salary
        $highPaidPhpDevs = $departments
            ->flatMap(static fn($dept) => $dept['employees'])
            ->filter(static fn($emp) => \in_array('PHP', $emp['skills'], true))
            ->filter(static fn($emp) => $emp['salary'] > $averageSalary)
            ->map(static fn($emp) => $emp['name'])
            ->toArray();

        $this->assertSame(['Alice'], $highPaidPhpDevs);

        // Task 3: Count employees per department with salary > 80K
        $highPaidCountByDept = $departments
            ->map(static function($dept) {
                $highPaidCount = $dept['employees']
                    ->filter(static fn($emp) => $emp['salary'] > 80000)
                    ->len()
                    ->toInt();

                return [
                    'department' => $dept['name'],
                    'highPaidCount' => $highPaidCount,
                ];
            })
            ->sortBy(static fn($a, $b) => $b['highPaidCount'] <=> $a['highPaidCount'])
            ->toArray();

        $this->assertCount(3, $highPaidCountByDept);

        $topDepts = \array_filter($highPaidCountByDept, static fn($dept) => $dept['highPaidCount'] > 0);
        $this->assertCount(2, $topDepts);

        $deptNames = \array_column($highPaidCountByDept, 'department');
        $this->assertContains('Product', $deptNames);
        $this->assertContains('Engineering', $deptNames);

        // Task 4: Get all unique skills across the company
        $allUniqueSkills = $departments
            ->flatMap(static fn($dept) => $dept['employees'])
            ->flatMap(static fn($emp) => $emp['skills'])
            ->dedup()
            ->sort()
            ->toArray();

        $this->assertCount(20, $allUniqueSkills);
        $this->assertContains('PHP', $allUniqueSkills);
        $this->assertContains('UX', $allUniqueSkills);
    }

    private function createUser(string $name, int $age, array $skills): object
    {
        return (object)[
            'name' => $name,
            'age' => $age,
            'skills' => $skills,
        ];
    }
}
