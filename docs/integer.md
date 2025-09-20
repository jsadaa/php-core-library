# Integer

The `Integer` class is an immutable wrapper around PHP's native integers, providing safe operations with overflow handling and arithmetic functionality.

## Table of Contents

- [Creation](#creation)
- [Basic Operations](#basic-operations)
- [Inspection](#inspection)
- [Arithmetic Operations](#arithmetic-operations)
- [Overflow-Safe Arithmetic](#overflow-safe-arithmetic)
- [Saturating Arithmetic](#saturating-arithmetic)
- [Comparison](#comparison)
- [Mathematical Functions](#mathematical-functions)
- [Bit Operations](#bit-operations)
- [Conversion](#conversion)
- [Type Safety](#type-safety)

## Creation

### From Native Integer

Creates a new Integer instance from a native PHP integer.

```php
// Create from an integer value
$int = Integer::of(42);
```

## Basic Operations

### To Int

Returns the wrapped native PHP integer value.

```php
$int = Integer::of(42);
$native = $int->toInt(); // 42
```

### To Float

Converts the integer to a floating-point number.

```php
$int = Integer::of(42);
$float = $int->toFloat(); // 42.0
```

## Inspection

### Is Positive

Checks if the integer is positive (greater than 0).

```php
$int = Integer::of(42);
$isPositive = $int->isPositive(); // true

$zero = Integer::of(0);
$isPositive = $zero->isPositive(); // false
```

### Is Negative

Checks if the integer is negative (less than 0).

```php
$int = Integer::of(-42);
$isNegative = $int->isNegative(); // true

$zero = Integer::of(0);
$isNegative = $zero->isNegative(); // false
```

### Is Even

Checks if the integer is even.

```php
$int = Integer::of(42);
$isEven = $int->isEven(); // true

$odd = Integer::of(43);
$isEven = $odd->isEven(); // false
```

### Is Odd

Checks if the integer is odd.

```php
$int = Integer::of(43);
$isOdd = $int->isOdd(); // true

$even = Integer::of(42);
$isOdd = $even->isOdd(); // false
```

### Is Multiple Of

Checks if the integer is a multiple of another value.

```php
$int = Integer::of(10);
$isMultiple = $int->isMultipleOf(2); // true
$isMultiple = $int->isMultipleOf(3); // false
```

### Signum

Returns the sign of the integer as -1, 0, or 1.

```php
$negative = Integer::of(-42);
$sign = $negative->signum(); // Integer::of(-1)

$zero = Integer::of(0);
$sign = $zero->signum(); // Integer::of(0)

$positive = Integer::of(42);
$sign = $positive->signum(); // Integer::of(1)
```

## Arithmetic Operations

### Add

Adds another integer and returns the result.

```php
$int = Integer::of(5);
$sum = $int->add(3); // Integer::of(8)
$sum = $int->add(Integer::of(7)); // Integer::of(12)
```

### Subtract

Subtracts another integer and returns the result.

```php
$int = Integer::of(10);
$diff = $int->sub(3); // Integer::of(7)
$diff = $int->sub(Integer::of(5)); // Integer::of(5)
```

### Multiply

Multiplies by another integer and returns the result.

```php
$int = Integer::of(4);
$product = $int->mul(3); // Integer::of(12)
$product = $int->mul(Integer::of(5)); // Integer::of(20)
```

### Divide

Divides by another integer and returns the result. Returns an error if the divisor is zero.

```php
$int = Integer::of(10);
$resultDiv = $int->div(2); // Result::ok(Integer::of(5))
$resultDiv = $int->div(Integer::of(3)); // Result::ok(Integer::of(3)) - integer division
$resultErr = $int->div(0); // Result::err(new \InvalidArgumentException('Division by zero'))
```

### Division with Floor

Divides by another integer and returns the floor of the result. Rounds towards negative infinity.

```php
$int = Integer::of(10);
$resultDiv = $int->divFloor(3); // Result::ok(Integer::of(3))
$negative = Integer::of(-10);
$resultDiv = $negative->divFloor(3); // Result::ok(Integer::of(-4))
```

### Division with Ceiling

Divides by another integer and returns the ceiling of the result. Rounds towards positive infinity.

```php
$int = Integer::of(10);
$resultDiv = $int->divCeil(3); // Result::ok(Integer::of(4))
$negative = Integer::of(-10);
$resultDiv = $negative->divCeil(3); // Result::ok(Integer::of(-3))
```

### Absolute Value

Returns the absolute value of this integer.

```php
$int = Integer::of(-5);
$abs = $int->abs(); // Integer::of(5)
```

### Absolute Difference

Returns the absolute difference between this integer and another value.

```php
$int = Integer::of(10);
$diff = $int->absDiff(7); // Integer::of(3)
$diff = $int->absDiff(Integer::of(15)); // Integer::of(5)
```

### Power

Raises this integer to the power of the given exponent.

```php
$int = Integer::of(2);
$squared = $int->pow(2); // Integer::of(4)
$cubed = $int->pow(Integer::of(3)); // Integer::of(8)
```

### Min

Returns the minimum of this integer and another value.

```php
$int = Integer::of(10);
$min = $int->min(5); // Integer::of(5)
$min = $int->min(Integer::of(15)); // Integer::of(10)
```

### Max

Returns the maximum of this integer and another value.

```php
$int = Integer::of(10);
$max = $int->max(5); // Integer::of(10)
$max = $int->max(Integer::of(15)); // Integer::of(15)
```

### Clamp

Restricts the integer to a specified range.

```php
$int = Integer::of(10);
$clamped = $int->clamp(5, 15); // Integer::of(10) - within range
$clamped = $int->clamp(15, 20); // Integer::of(15) - below range
$clamped = $int->clamp(1, 5); // Integer::of(5) - above range
```

## Overflow-Safe Arithmetic

### Overflowing Add

Adds another integer, checking for overflow. Returns an error on overflow.

```php
$int = Integer::of(10);
$sum = $int->overflowingAdd(5); // Result::ok(Integer::of(15))

$max = Integer::of(PHP_INT_MAX);
$overflow = $max->overflowingAdd(1); // Result::err(new \OverflowException('Integer overflow'))
```

### Overflowing Subtract

Subtracts another integer, checking for overflow. Returns an error on overflow.

```php
$int = Integer::of(10);
$diff = $int->overflowingSub(5); // Result::ok(Integer::of(5))

$min = Integer::of(PHP_INT_MIN);
$overflow = $min->overflowingSub(1); // Result::err(new \OverflowException('Integer overflow'))
```

### Overflowing Multiply

Multiplies by another integer, checking for overflow. Returns an error on overflow.

```php
$int = Integer::of(10);
$product = $int->overflowingMul(5); // Result::ok(Integer::of(50))

$large = Integer::of(PHP_INT_MAX / 2 + 1);
$overflow = $large->overflowingMul(2); // Result::err(new \OverflowException('Integer overflow'))
```

### Overflowing Divide

Divides by another integer, checking for overflow and division by zero. Returns an error on either condition.

```php
$int = Integer::of(10);
$quotient = $int->overflowingDiv(2); // Result::ok(Integer::of(5))
$divByZero = $int->overflowingDiv(0); // Result::err(new \InvalidArgumentException('Division by zero'))
```

## Saturating Arithmetic

Saturating arithmetic operations clamp the result to the maximum or minimum integer value instead of overflowing.

### Saturating Add

Adds another integer, saturating at the bounds of the integer range.

```php
$int = Integer::of(10);
$sum = $int->saturatingAdd(5); // Integer::of(15)

$max = Integer::of(PHP_INT_MAX);
$saturated = $max->saturatingAdd(1); // Integer::of(PHP_INT_MAX) - saturates instead of overflowing
```

### Saturating Subtract

Subtracts another integer, saturating at the bounds of the integer range.

```php
$int = Integer::of(10);
$diff = $int->saturatingSub(5); // Integer::of(5)

$min = Integer::of(PHP_INT_MIN);
$saturated = $min->saturatingSub(1); // Integer::of(PHP_INT_MIN) - saturates instead of overflowing
```

### Saturating Multiply

Multiplies by another integer, saturating at the bounds of the integer range.

```php
$int = Integer::of(10);
$product = $int->saturatingMul(5); // Integer::of(50)

$large = Integer::of(PHP_INT_MAX / 2 + 1);
$saturated = $large->saturatingMul(3); // Integer::of(PHP_INT_MAX) - saturates instead of overflowing
```

### Saturating Divide

Divides by another integer, saturating at the bounds of the integer range and handling division by zero.

```php
$int = Integer::of(10);
$quotient = $int->saturatingDiv(2); // Integer::of(5)
$divByZero = $int->saturatingDiv(0); // Integer::of(0) - returns 0 for division by zero
```

## Comparison

### Equals

Checks if this integer equals another value.

```php
$int = Integer::of(42);
$isEqual = $int->eq(42); // true
$isEqual = $int->eq(Integer::of(42)); // true
$isEqual = $int->eq(43); // false
```

### Greater Than

Checks if this integer is greater than another value.

```php
$int = Integer::of(42);
$isGreaterThan = $int->gt(40); // true
$isGreaterThan = $int->gt(Integer::of(43)); // false
```

### Greater Than or Equal

Checks if this integer is greater than or equal to another value.

```php
$int = Integer::of(42);
$isGreaterThanOrEqual = $int->ge(42); // true
$isGreaterThanOrEqual = $int->ge(Integer::of(43)); // false
```

### Less Than

Checks if this integer is less than another value.

```php
$int = Integer::of(42);
$isLessThan = $int->lt(43); // true
$isLessThan = $int->lt(Integer::of(40)); // false
```

### Less Than or Equal

Checks if this integer is less than or equal to another value.

```php
$int = Integer::of(42);
$isLessThanOrEqual = $int->le(42); // true
$isLessThanOrEqual = $int->le(Integer::of(40)); // false
```

### Compare

Compares this integer with another value and returns -1, 0, or 1.

```php
$int = Integer::of(42);
$cmp = $int->cmp(40); // 1 (greater)
$cmp = $int->cmp(Integer::of(42)); // 0 (equal)
$cmp = $int->cmp(50); // -1 (less)
```

## Mathematical Functions

### Logarithm (Base e)

Returns the natural logarithm (base e) of this integer.

```php
$int = Integer::of(10);
$log = $int->log(); // Integer::of(2) - floor of natural log
```

### Logarithm (Base 2)

Returns the base-2 logarithm of this integer.

```php
$int = Integer::of(8);
$log2 = $int->log2(); // Integer::of(3)
```

### Logarithm (Base 10)

Returns the base-10 logarithm of this integer.

```php
$int = Integer::of(100);
$log10 = $int->log10(); // Integer::of(2)
```

### Square Root

Returns the square root of this integer, rounded down.

```php
$int = Integer::of(16);
$sqrt = $int->sqrt(); // Integer::of(4)

$int = Integer::of(10);
$sqrt = $int->sqrt(); // Integer::of(3) - floor of square root
```

## Bit Operations

### Bitwise AND

Performs a bitwise AND operation.

```php
$int = Integer::of(10); // 1010 in binary
$result = $int->and(6); // 1010 & 0110 = 0010 = Integer::of(2)
```

### Bitwise OR

Performs a bitwise OR operation.

```php
$int = Integer::of(10); // 1010 in binary
$result = $int->or(6); // 1010 | 0110 = 1110 = Integer::of(14)
```

### Bitwise XOR

Performs a bitwise XOR operation.

```php
$int = Integer::of(10); // 1010 in binary
$result = $int->xor(6); // 1010 ^ 0110 = 1100 = Integer::of(12)
```

### Bitwise NOT

Performs a bitwise NOT operation.

```php
$int = Integer::of(10);
$result = $int->not(); // ~10 = -11
```

### Left Shift

Performs a left shift operation.

```php
$int = Integer::of(5); // 101 in binary
$result = $int->leftShift(1); // 101 << 1 = 1010 = Integer::of(10)
```

### Right Shift

Performs a right shift operation.

```php
$int = Integer::of(10); // 1010 in binary
$result = $int->rightShift(1); // 1010 >> 1 = 101 = Integer::of(5)
```

## Type Safety

The Integer class provides type safety through immutable operations. All methods that perform calculations return new Integer instances rather than modifying the original, ensuring consistent behavior.

```php
$a = Integer::of(10);
$b = $a->add(5); // $b is 15, $a remains 10

// Method chaining for complex calculations
$result = Integer::of(10)
    ->mul(2)        // 20
    ->add(5)        // 25
    ->sub(3);       // 22
```

## Error Handling

The Integer class uses the Result type for operations that may fail:

1. Division operations return `Result<Integer, InvalidArgumentException>` to handle division by zero
2. Overflow-checking operations return `Result<Integer, OverflowException>` to handle integer overflow

This approach allows for clean error handling without exceptions:

```php
$result = Integer::of(10)->div(0);
$quotient = $result->match(
    fn($value) => "Result: $value",
    fn($error) => "Error: {$error->getMessage()}"
); // "Error: Division by zero"
```
