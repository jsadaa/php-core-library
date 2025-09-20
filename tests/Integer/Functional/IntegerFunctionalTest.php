<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Integer\Functional;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use PHPUnit\Framework\TestCase;

final class IntegerFunctionalTest extends TestCase
{
    public function testComplexCalculation(): void
    {
        $a = Integer::from(5);
        $b = Integer::from(12);
        $c = Integer::from(4);

        $aSquared = $a->pow(2);
        $this->assertSame(25, $aSquared->toInt());

        $bSquared = $b->pow(2);
        $this->assertSame(144, $bSquared->toInt());

        $numerator = $aSquared->add($bSquared);
        $this->assertSame(169, $numerator->toInt());

        $denominator = $c->mul(2);
        $this->assertSame(8, $denominator->toInt());

        $result = $numerator->div($denominator);
        $this->assertTrue($result->isOk());
        $this->assertSame(21, $result->unwrap()->toInt(), 'Result should be 21 (truncated from 21.125)');
    }

    public function testFactorialCalculation(): void
    {
        // Implement factorial: n! = n * (n-1) * ... * 1
        $factorial = static function(int $n): Integer {
            $result = Integer::from(1);

            for ($i = 2; $i <= $n; $i++) {
                $result = $result->mul($i);
            }

            return $result;
        };

        $this->assertSame(1, $factorial(0)->toInt());
        $this->assertSame(1, $factorial(1)->toInt());
        $this->assertSame(2, $factorial(2)->toInt());
        $this->assertSame(6, $factorial(3)->toInt());
        $this->assertSame(24, $factorial(4)->toInt());
        $this->assertSame(120, $factorial(5)->toInt());
        $this->assertSame(720, $factorial(6)->toInt());
    }

    public function testFibonacciSequence(): void
    {
        // Implement Fibonacci sequence: F(n) = F(n-1) + F(n-2) with F(0)=0, F(1)=1
        $fibonacci = static function(int $n): Integer {
            if ($n <= 0) {
                return Integer::from(0);
            }

            if ($n === 1) {
                return Integer::from(1);
            }

            $prev = Integer::from(0);
            $current = Integer::from(1);

            for ($i = 2; $i <= $n; $i++) {
                $next = $prev->add($current);
                $prev = $current;
                $current = $next;
            }

            return $current;
        };

        $this->assertSame(0, $fibonacci(0)->toInt());
        $this->assertSame(1, $fibonacci(1)->toInt());
        $this->assertSame(1, $fibonacci(2)->toInt());
        $this->assertSame(2, $fibonacci(3)->toInt());
        $this->assertSame(3, $fibonacci(4)->toInt());
        $this->assertSame(5, $fibonacci(5)->toInt());
        $this->assertSame(8, $fibonacci(6)->toInt());
        $this->assertSame(13, $fibonacci(7)->toInt());
        $this->assertSame(21, $fibonacci(8)->toInt());
    }

    public function testGcdCalculation(): void {
        $result1 = $this->gcd(Integer::from(48), Integer::from(18));
        $this->assertTrue($result1->isOk());
        $this->assertSame(6, $result1->unwrap()->toInt());

        $result2 = $this->gcd(Integer::from(17), Integer::from(5));
        $this->assertTrue($result2->isOk());
        $this->assertSame(1, $result2->unwrap()->toInt());

        $result3 = $this->gcd(Integer::from(0), Integer::from(5));
        $this->assertTrue($result3->isOk());
        $this->assertSame(5, $result3->unwrap()->toInt());
    }

    public function testStatisticsCalculation(): void
    {
        $values = Sequence::of(
            Integer::from(12),
            Integer::from(5),
            Integer::from(8),
            Integer::from(15),
            Integer::from(10),
        );

        // Calculate sum
        $sum = $values->fold(
            static fn(Integer $acc, Integer $val) => $acc->add($val),
            Integer::from(0),
        );
        $this->assertSame(50, $sum->toInt());

        // Calculate mean (average)
        $count = $values->len();
        $meanResult = $sum->div($count);
        $this->assertTrue($meanResult->isOk());
        $this->assertSame(10, $meanResult->unwrap()->toInt());

        // Calculate min and max
        $min = $values->fold(
            static function(Integer $acc, Integer $val) {
                return $acc->cmp($val)->le(0) ? $acc : $val;
            },
            Integer::from(\PHP_INT_MAX),
        );
        $this->assertSame(5, $min->toInt());

        $max = $values->fold(
            static function(Integer $acc, Integer $val) {
                return $acc->cmp($val)->ge(0) ? $acc : $val;
            },
            Integer::from(\PHP_INT_MIN),
        );
        $this->assertSame(15, $max->toInt());
    }

    public function testWorkingWithLargeValues(): void
    {
        $largeValue = Integer::from((int) (\PHP_INT_MAX / 2));
        $largeValue2 = Integer::from((int) (\PHP_INT_MAX / 2) + 1);
        $mediumValue = Integer::from(1000);

        $sum = $largeValue->saturatingAdd($largeValue2);

        $this->assertSame(\PHP_INT_MAX, $sum->toInt());

        $difference = $sum->saturatingSub($mediumValue);
        $this->assertLessThanOrEqual(\PHP_INT_MAX, $difference->toInt());

        $divResult = $difference->div($mediumValue);
        $this->assertTrue($divResult->isOk());
    }

    public function testCompoundInterestCalculation(): void
    {
        // Calculate compound interest with the formula: A = P(1 + r/n)^(nt)
        // Where:
        // - A is the final amount
        // - P is the principal (initial investment)
        // - r is the annual interest rate (as a decimal)
        // - n is the number of times interest is compounded per year
        // - t is the time in years

        // For integer math, we'll work with cents and percentage points
        // e.g., $1000 = 100000 cents, 5% = 500 basis points

        // Parameters (in integer representation)
        $principal = Integer::from(100_000); // $1000.00
        $rate = Integer::from(500); // 5%
        $compoundingsPerYear = Integer::from(12); // monthly
        $timeInYears = Integer::from(5); // 5 years

        // Calculate (1 + r/n) - here we use basis points
        $ratePerPeriod = $rate->div($compoundingsPerYear)
            ->match(
                static function($result) {
                    // Convert rate to decimal (need to divide by 10000 for basis points)
                    // For integer math, we'll scale by 10000
                    return Integer::from(10000)->add($result);
                },
                static function() {
                    // Should not happen
                    return Integer::from(10000);
                },
            );

        // Calculate number of periods
        $periods = $compoundingsPerYear->mul($timeInYears);

        // Calculate (1 + r/n)^(nt) using repeated multiplication
        // Initialize with scaled 1.0 (10000)
        $compoundFactor = Integer::from(10000);

        for ($i = 0; $i < $periods->toInt(); $i++) {
            // Multiply, then divide by 10000 to maintain scale
            $compoundFactor = $compoundFactor->mul($ratePerPeriod);
            $compoundFactor = $compoundFactor->div(Integer::from(10000))->unwrapOr(Integer::from(0));
        }

        // Calculate final amount = principal * compound factor / scaling
        $amount = $principal->mul($compoundFactor);
        $amount = $amount->div(Integer::from(10000))->unwrapOr(Integer::from(0));

        // Verify result is reasonable for 5 years of 5% interest compounded monthly
        // Expected result should be around $1,280.08 = 128,008 cents
        $this->assertGreaterThan(
            120_000,
            $amount->toInt(),
            'Final amount should be significantly more than the principal',
        );
        $this->assertLessThan(
            140_000,
            $amount->toInt(),
            'Final amount should be within reasonable bounds',
        );
    }

    public function testErrorHandlingInCalculations(): void
    {
        $a = Integer::from(10);
        $b = Integer::from(0);

        $result = $a->div($b);

        $this->assertTrue($result->isErr());

        $safeResult = $result->match(
            static function($value) {
                return $value->toInt();
            },
            static function($error) {
                return 0;
            },
        );

        $this->assertSame(0, $safeResult);

        $complexResult = $a->div($b)->match(
            static function($value) {
                return $value->mul(5);
            },
            static function($error) {
                return Integer::from(42);
            },
        );

        $this->assertSame(42, $complexResult->toInt());
    }

    private function gcd(Integer $a, Integer $b): Result {
        // Ensure a and b are positive
        $a = $a->abs();
        $b = $b->abs();

        // Base case: if b is zero, return a
        if ($b->eq(0)) {
            return Result::ok($a);
        }

        // Recursive step: gcd(a, b) = gcd(b, a mod b)
        $mod = $a->div($b)->match(
            static function($quotient) use ($a, $b) {
                $product = $quotient->mul($b);

                return $a->sub($product);
            },
            static function() {
                return Integer::from(0);
            },
        );

        // Recursively calculate GCD with b and the remainder
        return $this->gcd($b, $mod);
    }
}
