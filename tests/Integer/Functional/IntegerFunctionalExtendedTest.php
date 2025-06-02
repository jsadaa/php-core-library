<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Integer\Functional;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Vec\Vec;
use Jsadaa\PhpCoreLibrary\Primitives\Integer\Integer;
use PHPUnit\Framework\TestCase;

final class IntegerFunctionalExtendedTest extends TestCase
{
    public function testSieveOfEratosthenes(): void
    {
        // Generate primes up to a given limit
        $generatePrimes = static function(int $limit): Vec {
            // Initialize all as potential primes (true)
            $sieve = [];

            for ($i = 0; $i <= $limit; $i++) {
                $sieve[$i] = true;
            }

            // 0 and 1 are not prime
            $sieve[0] = $sieve[1] = false;

            // Start with 2 (the first prime)
            $p = 2;

            // Run the sieve
            while ($p * $p <= $limit) {
                // If p is prime, mark its multiples as non-prime
                if ($sieve[$p]) {
                    for ($i = $p * $p; $i <= $limit; $i += $p) {
                        $sieve[$i] = false;
                    }
                }

                // Move to the next potential prime
                $p++;
            }

            // Collect the primes into a Vec
            $primes = Vec::new();

            for ($i = 2; $i <= $limit; $i++) {
                if ($sieve[$i]) {
                    $primes = $primes->push(Integer::from($i));
                }
            }

            return $primes;
        };

        // Generate primes up to 50
        $primes = $generatePrimes(50);

        $this->assertSame(15, $primes->len()->toInt(), 'There should be 15 primes up to 50');

        $expectedPrimes = [2, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37, 41, 43, 47];
        $actualPrimes = $primes->map(static fn(Integer $i) => $i->toInt())->toArray();

        $this->assertSame($expectedPrimes, $actualPrimes);

        // Test primality checking
        $isPrime = static function(Integer $n): bool {
            if ($n->cmp(2)->toInt() < 0) {
                return false;  // Less than 2 is not prime
            }

            if ($n->eq(2)) {
                return true;   // 2 is prime
            }

            if ($n->isEven()) {
                return false;  // Even numbers except 2 are not prime
            }

            // Check odd divisors up to sqrt(n)
            $sqrt = $n->sqrt();

            if ($sqrt->toInt() === \PHP_INT_MIN) {
                return false;
            }

            $i = Integer::from(3);

            while ($i->cmp($sqrt)->toInt() <= 0) {
                $mod = $n->div($i)->match(
                    static function($quotient) use ($n, $i) {
                        return $n->sub($quotient->mul($i));
                    },
                    static function() {
                        return Integer::from(1);
                    },
                );

                if ($mod->eq(0)) {
                    return false;  // Found a divisor
                }

                $i = $i->add(2);  // Next odd number
            }

            return true;  // No divisors found
        };

        $this->assertTrue($isPrime(Integer::from(2)));
        $this->assertTrue($isPrime(Integer::from(3)));
        $this->assertTrue($isPrime(Integer::from(5)));
        $this->assertTrue($isPrime(Integer::from(7)));
        $this->assertTrue($isPrime(Integer::from(11)));
        $this->assertTrue($isPrime(Integer::from(13)));

        $this->assertFalse($isPrime(Integer::from(1)));
        $this->assertFalse($isPrime(Integer::from(4)));
        $this->assertFalse($isPrime(Integer::from(6)));
        $this->assertFalse($isPrime(Integer::from(8)));
        $this->assertFalse($isPrime(Integer::from(9)));
        $this->assertFalse($isPrime(Integer::from(10)));
    }

    public function testBinaryOperations(): void
    {
        // Test the operations
        $a = Integer::from(0b1010); // 10 in decimal
        $b = Integer::from(0b1100); // 12 in decimal

        $this->assertSame(0b1000, $a->and($b)->toInt()); // 1010 AND 1100 = 1000 (8)
        $this->assertSame(0b1110, $a->or($b)->toInt()); // 1010 OR 1100 = 1110 (14)
        $this->assertSame(0b0110, $a->xor($b)->toInt()); // 1010 XOR 1100 = 0110 (6)

        $this->assertSame(0b101000, $a->leftShift(2)->toInt()); // 1010 << 2 = 101000 (40)
        $this->assertSame(0b101, $a->rightShift(1)->toInt()); // 1010 >> 1 = 101 (5)

        // Apply these to a practical problem: checking if a number is a power of 2
        $isPowerOfTwo = static function(Integer $n): bool {
            if ($n->cmp(0)->toInt() <= 0) {
                return false;
            }

            // A power of 2 has only one bit set, so (n & (n-1)) should be 0
            $nMinus1 = $n->sub(Integer::from(1));
            $result = $n->and($nMinus1);

            return $result->eq(0);
        };

        $this->assertTrue($isPowerOfTwo(Integer::from(1)));
        $this->assertTrue($isPowerOfTwo(Integer::from(2)));
        $this->assertTrue($isPowerOfTwo(Integer::from(4)));
        $this->assertTrue($isPowerOfTwo(Integer::from(8)));
        $this->assertTrue($isPowerOfTwo(Integer::from(16)));
        $this->assertTrue($isPowerOfTwo(Integer::from(32)));

        $this->assertFalse($isPowerOfTwo(Integer::from(0)));
        $this->assertFalse($isPowerOfTwo(Integer::from(3)));
        $this->assertFalse($isPowerOfTwo(Integer::from(6)));
        $this->assertFalse($isPowerOfTwo(Integer::from(10)));
        $this->assertFalse($isPowerOfTwo(Integer::from(15)));
    }

    public function testFractionImplementation(): void
    {
        // Define a simple Fraction class
        $makeFraction = static function(Integer $numerator, Integer $denominator): array {
            if ($denominator->eq(0)) {
                throw new \InvalidArgumentException('Denominator cannot be zero');
            }

            // Normalize the fraction (reduce to lowest terms)
            $gcd = static function(Integer $a, Integer $b) use (&$gcd): Integer {
                if ($b->eq(0)) {
                    return $a;
                }

                $remainder = $a->div($b)->match(
                    static function($quotient) use ($a, $b) {
                        return $a->sub($quotient->mul($b));
                    },
                    static function() {
                        return Integer::from(0);
                    },
                );

                return $gcd($b, $remainder);
            };

            // Get the GCD to reduce the fraction
            $divisor = $gcd($numerator->abs(), $denominator->abs());

            // Apply sign convention: denominator is always positive
            if ($denominator->isNegative()) {
                $numerator = $numerator->mul(-1);
                $denominator = $denominator->mul(-1);
            }

            // Reduce the fraction
            $reducedNumerator = $numerator->div($divisor)->match(
                static function($result) { return $result; },
                static function() use ($numerator) { return $numerator; },
            );

            $reducedDenominator = $denominator->div($divisor)->match(
                static function($result) { return $result; },
                static function() use ($denominator) { return $denominator; },
            );

            return ['num' => $reducedNumerator, 'den' => $reducedDenominator];
        };

        $addFractions = static function(array $f1, array $f2) use ($makeFraction): array {
            $commonDen = $f1['den']->mul($f2['den']);
            $newNum1 = $f1['num']->mul($f2['den']);
            $newNum2 = $f2['num']->mul($f1['den']);
            $sumNum = $newNum1->add($newNum2);

            return $makeFraction($sumNum, $commonDen);
        };

        $multiplyFractions = static function(array $f1, array $f2) use ($makeFraction): array {
            $newNum = $f1['num']->mul($f2['num']);
            $newDen = $f1['den']->mul($f2['den']);

            return $makeFraction($newNum, $newDen);
        };

        $f1 = $makeFraction(Integer::from(3), Integer::from(4)); // 3/4
        $this->assertSame(3, $f1['num']->toInt());
        $this->assertSame(4, $f1['den']->toInt());

        $f2 = $makeFraction(Integer::from(6), Integer::from(8)); // 6/8 -> 3/4
        $this->assertSame(3, $f2['num']->toInt());
        $this->assertSame(4, $f2['den']->toInt());

        $f3 = $makeFraction(Integer::from(-2), Integer::from(5)); // -2/5
        $this->assertSame(-2, $f3['num']->toInt());
        $this->assertSame(5, $f3['den']->toInt());

        $f4 = $makeFraction(Integer::from(2), Integer::from(-5)); // 2/-5 -> -2/5
        $this->assertSame(-2, $f4['num']->toInt());
        $this->assertSame(5, $f4['den']->toInt());

        $sum = $addFractions($f1, $f3); // 3/4 + (-2/5) = (15-8)/20 = 7/20
        $this->assertSame(7, $sum['num']->toInt());
        $this->assertSame(20, $sum['den']->toInt());

        $product = $multiplyFractions($f1, $f3); // 3/4 * (-2/5) = -6/20 = -3/10
        $this->assertSame(-3, $product['num']->toInt());
        $this->assertSame(10, $product['den']->toInt());
    }
}
