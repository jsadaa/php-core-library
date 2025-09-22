<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Double\Functional;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use Jsadaa\PhpCoreLibrary\Primitives\Double\Double;
use Jsadaa\PhpCoreLibrary\Primitives\Double\Error\DivisionByZero;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use PHPUnit\Framework\TestCase;

final class DoubleFunctionalTest extends TestCase
{
    public function testComplexCalculation(): void
    {
        $a = Double::of(5.5);
        $b = Double::of(12.3);
        $c = Double::of(4.2);

        $aSquared = $a->pow(2);
        $this->assertEqualsWithDelta(30.25, $aSquared->toFloat(), 0.0001);

        $bSquared = $b->pow(2);
        $this->assertEqualsWithDelta(151.29, $bSquared->toFloat(), 0.0001);

        $numerator = $aSquared->add($bSquared);
        $this->assertEqualsWithDelta(181.54, $numerator->toFloat(), 0.0001);

        $denominator = $c->mul(2);
        $this->assertEqualsWithDelta(8.4, $denominator->toFloat(), 0.0001);

        $result = $numerator->div($denominator);
        $this->assertTrue($result->isOk());
        $this->assertEqualsWithDelta(21.61, $result->unwrap()->toFloat(), 0.01);
    }

    public function testFactorialCalculation(): void
    {
        // Implement factorial: n! = n * (n-1) * ... * 1
        $factorial = static function(float $n): Double {
            $result = Double::of(1.0);
            $current = Double::of($n);

            while ($current->gt(0)) {
                $result = $result->mul($current);
                $current = $current->sub(1);
            }

            return $result;
        };

        $this->assertSame(1.0, $factorial(0)->toFloat());
        $this->assertSame(1.0, $factorial(1)->toFloat());
        $this->assertSame(2.0, $factorial(2)->toFloat());
        $this->assertSame(6.0, $factorial(3)->toFloat());
        $this->assertSame(24.0, $factorial(4)->toFloat());
        $this->assertSame(120.0, $factorial(5)->toFloat());
        $this->assertSame(720.0, $factorial(6)->toFloat());
    }

    public function testProjectileMotion(): void
    {
        // Calculate the trajectory of a projectile
        // x(t) = v0 * cos(θ) * t
        // y(t) = v0 * sin(θ) * t - 0.5 * g * t^2
        // where v0 is initial velocity, θ is launch angle, g is gravity, t is time

        $initialVelocity = Double::of(30.0); // 30 m/s
        $launchAngleDegrees = Double::of(45.0); // 45 degrees
        $gravity = Double::of(9.81); // 9.81 m/s^2

        // Convert angle to radians
        $launchAngle = $launchAngleDegrees->toRadians();

        // Calculate components
        $v0x = $initialVelocity->mul($launchAngle->cos());
        $v0y = $initialVelocity->mul($launchAngle->sin());

        // Calculate time of flight
        // Time to reach max height: t_max = v0y / g
        // Total flight time: t_total = 2 * t_max = 2 * v0y / g
        $timeToMaxHeight = $v0y->div($gravity)->match(
            static fn($result) => $result,
            static fn() => Double::of(0.0),
        );
        $totalFlightTime = $timeToMaxHeight->mul(2);

        // Calculate maximum height
        // h_max = v0y^2 / (2*g)
        $maxHeight = $v0y->pow(2)->div($gravity->mul(2))->match(
            static fn($result) => $result,
            static fn() => Double::of(0.0),
        );

        // Calculate range
        // R = v0^2 * sin(2θ) / g
        $range = $initialVelocity->pow(2)->mul($launchAngle->mul(2)->sin())->div($gravity)->match(
            static fn($result) => $result,
            static fn() => Double::of(0.0),
        );

        $this->assertEqualsWithDelta(4.32, $totalFlightTime->toFloat(), 0.01);
        $this->assertEqualsWithDelta(22.9, $maxHeight->toFloat(), 0.1);
        $this->assertEqualsWithDelta(91.7, $range->toFloat(), 0.1);

        // Calculate position at different times
        $positions = [];
        $timeStep = Double::of(0.5);
        $time = Double::of(0.0);

        while ($time->le($totalFlightTime)) {
            $x = $v0x->mul($time);
            $y = $v0y->mul($time)->sub($gravity->mul($time->pow(2))->div(2.0)->match(
                static fn($result) => $result,
                static fn() => Double::of(0.0),
            ));
            $positions[] = [
                'time' => $time->toFloat(),
                'x' => $x->toFloat(),
                'y' => $y->toFloat(),
            ];
            $time = $time->add($timeStep);
        }

        $this->assertGreaterThan(0, \count($positions));

        // At t=0, object should be at origin
        $this->assertEqualsWithDelta(0.0, $positions[0]['x'], 0.01);
        $this->assertEqualsWithDelta(0.0, $positions[0]['y'], 0.01);

        // At t=totalFlightTime, the height will not necessarily be exactly 0
        // due to the discretization of time steps
        $lastPos = $positions[\count($positions) - 1];
        $this->assertLessThan(7.0, $lastPos['y'], 'Height at the end of trajectory should be small');

        // At t=timeToMaxHeight, should reach maximum height
        $midIndex = (int) (\count($positions) / 2);
        $this->assertEqualsWithDelta($maxHeight->toFloat(), $positions[$midIndex]['y'], 1.0);
    }

    public function testStatisticsCalculations(): void
    {
        $values = Sequence::of(
            Double::of(12.5),
            Double::of(5.2),
            Double::of(8.7),
            Double::of(15.3),
            Double::of(10.1),
        );

        // Calculate mean
        $sum = $values->fold(
            static fn(Double $acc, Double $val) => $acc->add($val),
            Double::of(0.0),
        );

        $count = $values->size()->toInt();
        $mean = $sum->div(Double::of($count))->unwrap();
        $this->assertEqualsWithDelta(10.36, $mean->toFloat(), 0.01);

        // Calculate variance and standard deviation
        $sumOfSquaredDifferences = $values->fold(
            static function(Double $acc, Double $val) use ($mean) {
                $diff = $val->sub($mean);

                return $acc->add($diff->pow(2));
            },
            Double::of(0.0),
        );

        $variance = $sumOfSquaredDifferences->div(Double::of($count))->unwrap();
        $standardDeviation = $variance->sqrt();

        $this->assertEqualsWithDelta(11.69, $variance->toFloat(), 0.01);
        $this->assertEqualsWithDelta(3.42, $standardDeviation->toFloat(), 0.01);

        // Calculate min and max
        $min = $values->fold(
            static function(Double $acc, Double $val) {
                return $acc->lt($val) ? $acc : $val;
            },
            Double::of(\PHP_FLOAT_MAX),
        );

        $max = $values->fold(
            static function(Double $acc, Double $val) {
                return $acc->gt($val) ? $acc : $val;
            },
            Double::of(-\PHP_FLOAT_MAX),
        );

        $this->assertSame(5.2, $min->toFloat());
        $this->assertSame(15.3, $max->toFloat());

        // Calculate median
        $sorted = $values->map(static fn($x) => $x->toFloat())->toArray();
        \sort($sorted);
        $medianValue = (\count($sorted) % 2 === 0)
            ? ($sorted[\count($sorted) / 2 - 1] + $sorted[\count($sorted) / 2]) / 2
            : $sorted[(\count($sorted) - 1) / 2];

        $this->assertEqualsWithDelta(10.1, $medianValue, 0.01);
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

        // Parameters
        $principal = Double::of(1000.00);
        $rate = Double::of(0.05); // 5%
        $compoundingsPerYear = Double::of(12); // monthly
        $timeInYears = Double::of(5); // 5 years

        // Calculate (1 + r/n)
        $ratePerPeriod = $rate->div($compoundingsPerYear)
            ->match(
                static function($result) {
                    return Double::of(1.0)->add($result);
                },
                static function() {
                    // Should not happen in this example
                    return Double::of(1.0);
                },
            );

        // Calculate number of periods
        $periods = $compoundingsPerYear->mul($timeInYears);

        // Calculate (1 + r/n)^(nt) using the pow function
        $compoundFactor = $ratePerPeriod->pow($periods);

        // Calculate final amount = principal * compound factor
        $amount = $principal->mul($compoundFactor);

        // Verify result is reasonable for 5 years of 5% interest compounded monthly
        // Expected result should be around $1,283.36
        $this->assertEqualsWithDelta(1283.36, $amount->toFloat(), 0.01);
    }

    public function testErrorHandlingInCalculations(): void
    {
        $a = Double::of(10.0);
        $b = Double::of(0.0);

        $result = $a->div($b);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(DivisionByZero::class, $result->unwrapErr());

        $safeResult = $result->match(
            static function($value) {
                return $value->toFloat();
            },
            static function($error) {
                return 0.0;
            },
        );

        $this->assertSame(0.0, $safeResult);

        $complexResult = $a->div($b)->match(
            static function($value) {
                return $value->mul(5);
            },
            static function($error) {
                return Double::of(42.5);
            },
        );

        $this->assertSame(42.5, $complexResult->toFloat());
    }

    public function testNumericIntegration(): void
    {
        // Integrate a function using the trapezoidal rule
        $integrate = static function(callable $f, Double $a, Double $b, Integer $n): Double {
            if ($n->le(0)) {
                return Double::of(0.0);
            }

            $h = $b->sub($a)->div($n->toFloat())->match(
                static fn($result) => $result,
                static fn() => Double::of(0.0),
            );
            $sum = Double::of(0.0);
            $x = $a;

            // First point
            $sum = $sum->add($f($x)->div(2.0)->match(
                static fn($result) => $result,
                static fn() => Double::of(0.0),
            ));

            // Middle points
            for ($i = 1; $i < $n->toInt(); $i++) {
                $x = $a->add($h->mul($i));
                $sum = $sum->add($f($x));
            }

            // Last point
            $sum = $sum->add($f($b)->div(2.0)->match(
                static fn($result) => $result,
                static fn() => Double::of(0.0),
            ));

            return $sum->mul($h);
        };

        // Test integration of f(x) = x^2 from 0 to 1
        // The analytic result is 1/3
        $f1 = static function(Double $x): Double {
            return $x->pow(2);
        };

        $result1 = $integrate($f1, Double::of(0.0), Double::of(1.0), Integer::of(100));
        $this->assertEqualsWithDelta(1/3, $result1->toFloat(), 0.001);

        // Test integration of f(x) = sin(x) from 0 to π
        // The analytic result is 2
        $f2 = static function(Double $x): Double {
            return $x->sin();
        };

        $result2 = $integrate($f2, Double::of(0.0), Double::pi(), Integer::of(100));
        $this->assertEqualsWithDelta(2.0, $result2->toFloat(), 0.001);
    }

    public function testLogarithmicScientificComputations(): void
    {
        // Calculate pH value (pH = -log10([H+])) where [H+] is the hydrogen ion concentration
        $hydrogenConcentration = Double::of(0.0000001); // 10^-7 mol/L (neutral solution)
        $pH = $hydrogenConcentration->log(10.0)->mul(-1.0);
        $this->assertEqualsWithDelta(7.0, $pH->toFloat(), 0.01);

        // Test logarithmic scale conversions (e.g., decibels)
        // Decibel calculation: dB = 10 * log10(P2/P1)
        $powerRatio = Double::of(100.0); // P2/P1 = 100 (power increased 100×)
        $decibels = $powerRatio->log(10.0)->mul(10.0);
        $this->assertEqualsWithDelta(20.0, $decibels->toFloat(), 0.01);

        // Test logarithm base change formula: log_b(x) = log_a(x) / log_a(b)
        // Calculate log base 5 of 125 using log10
        $value = Double::of(125.0);
        $base = Double::of(5.0);

        // Using log base formula: log_5(125) = log_10(125) / log_10(5)
        $log10Value = $value->log10();
        $log10Base = $base->log10();
        $log5 = $log10Value->div($log10Base)->match(
            static fn($result) => $result,
            static fn() => Double::of(0.0),
        );

        // Should be 3 because 5^3 = 125
        $this->assertEqualsWithDelta(3.0, $log5->toFloat(), 0.001);

        // Direct calculation using the log method for comparison
        $directLog5 = $value->log($base);
        $this->assertEqualsWithDelta(3.0, $directLog5->toFloat(), 0.001);

        // Complex calculation: Shannon entropy for a binary system
        // H = -p*log2(p) - (1-p)*log2(1-p) where p is probability
        $probability = Double::of(0.25);
        $oneMinusP = Double::of(1.0)->sub($probability);

        $term1 = $probability->mul($probability->log2()->mul(-1.0));
        $term2 = $oneMinusP->mul($oneMinusP->log2()->mul(-1.0));
        $entropy = $term1->add($term2);

        // Expected entropy value for p=0.25 is approximately 0.811 bits
        $this->assertEqualsWithDelta(0.811, $entropy->toFloat(), 0.001);
    }
}
