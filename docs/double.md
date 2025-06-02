# Double

The `Double` class is an immutable wrapper around PHP's floating-point numbers, providing safe operations with  mathematical functionality and proper error handling.

## Table of Contents

- [Creation](#creation)
- [Basic Operations](#basic-operations)
- [Inspection](#inspection)
- [Arithmetic Operations](#arithmetic-operations)
- [Comparison](#comparison)
- [Mathematical Functions](#mathematical-functions)
  - [Exponential and Logarithmic](#exponential-and-logarithmic)
  - [Trigonometric](#trigonometric)
  - [Hyperbolic](#hyperbolic)
  - [Rounding](#rounding)
- [Constants](#constants)
- [Special Operations](#special-operations)
- [Type Safety](#type-safety)

## Creation

### From Value

Creates a new Double instance from a native PHP float, integer, or another Double.

```php
// Create from floating-point value
$double = Double::from(3.14);

// Create from integer value
$fromInt = Double::from(42);

// Create copy of existing Double
$copy = Double::from($double);
```

## Basic Operations

### To Float

Returns the wrapped native PHP float value.

```php
$double = Double::from(3.14);
$native = $double->toFloat(); // 3.14
```

### To Int

Converts the floating-point value to an integer by truncation.

```php
$double = Double::from(3.99);
$nativeInt = $double->toInt(); // 3 (native int, truncated)

$negative = Double::from(-2.7);
$negativeInt = $negative->toInt(); // -2 (native int, truncated)
```

## Inspection

### Is Positive

Checks if the Double is positive (greater than 0).

```php
$positive = Double::from(42.5);
$isPositive = $positive->isPositive(); // true

$zero = Double::from(0);
$isZeroPositive = $zero->isPositive(); // false
```

### Is Negative

Checks if the Double is negative (less than 0).

```php
$negative = Double::from(-3.14);
$isNegative = $negative->isNegative(); // true

$zero = Double::from(0);
$isZeroNegative = $zero->isNegative(); // false
```

### Is Finite

Checks if the Double is a finite number (not infinity and not NaN).

```php
$regular = Double::from(42.0);
$isFinite = $regular->isFinite(); // true

$infinity = Double::infinity();
$isFinite = $infinity->isFinite(); // false
```

### Is Infinite

Checks if the Double is infinity (positive or negative).

```php
$regular = Double::from(42.0);
$isInfinite = $regular->isInfinite(); // false

$infinity = Double::infinity();
$isInfinite = $infinity->isInfinite(); // true
```

### Is NaN

Checks if the Double is Not a Number (NaN).

```php
$regular = Double::from(42.0);
$isNaN = $regular->isNan(); // false

$nan = Double::nan();
$isNaN = $nan->isNan(); // true
```

### Is Integer

Checks if the Double is an integer value (has no fractional part).

```php
$integer = Double::from(42.0);
$isInteger = $integer->isInteger(); // true

$fractional = Double::from(42.5);
$isInteger = $fractional->isInteger(); // false
```

## Arithmetic Operations

### Add

Adds another value to this Double.

```php
$double = Double::from(5.5);
$sum1 = $double->add(2.5); // Double::from(8.0)
$sum2 = $double->add(Double::from(3.3)); // Double::from(8.8)
```

### Subtract

Subtracts another value from this Double.

```php
$double = Double::from(10.5);
$diff1 = $double->sub(3.2); // Double::from(7.3)
$diff2 = $double->sub(Double::from(5.5)); // Double::from(5.0)
```

### Multiply

Multiplies this Double by another value.

```php
$double = Double::from(3.5);
$product1 = $double->mul(2); // Double::from(7.0)
$product2 = $double->mul(Double::from(1.5)); // Double::from(5.25)
```

### Divide

Divides this Double by another value. Returns an error if the divisor is zero.

```php
$double = Double::from(10.0);
$result1 = $double->div(2.0); // Result::ok(Double::from(5.0))
$result2 = $double->div(Double::from(5.0)); // Result::ok(Double::from(2.0))
$error = $double->div(0.0); // Result::err(\InvalidArgumentException)
```

### Absolute Value

Returns the absolute value of this Double.

```php
$negative = Double::from(-2.5);
$absolute = $negative->abs(); // Double::from(2.5)
```

### Absolute Difference

Returns the absolute difference between this Double and another value.

```php
$double = Double::from(10.5);
$diff1 = $double->absDiff(7.2); // Double::from(3.3)
$diff2 = $double->absDiff(Double::from(15.8)); // Double::from(5.3)
```

### Power

Raises this Double to the power of the exponent.

```php
$double = Double::from(2.0);
$squared = $double->pow(2); // Double::from(4.0)
$cubed = $double->pow(3); // Double::from(8.0)
$sqRoot = $double->pow(0.5); // Double::from(1.41421356...)
```

### Remainder

Returns the floating-point remainder of the division operation.

```php
$double = Double::from(10.0);
$result = $double->rem(3.0); // Double::from(1.0)
```

**Note:** For division by zero, returns NaN (unlike `div()` which returns an error).

### Min

Returns the minimum of this Double and another value.

```php
$double = Double::from(10.5);
$min = $double->min(5.5); // Double::from(5.5)
$min = $double->min(Double::from(15.5)); // Double::from(10.5)
```

### Max

Returns the maximum of this Double and another value.

```php
$double = Double::from(10.5);
$max = $double->max(5.5); // Double::from(10.5)
$max = $double->max(Double::from(15.5)); // Double::from(15.5)
```

### Clamp

Restricts this Double to a specified range.

```php
$double = Double::from(5.5);
$clamped1 = $double->clamp(0, 10); // Double::from(5.5) - within range
$clamped2 = $double->clamp(6, 10); // Double::from(6.0) - below min
$clamped3 = $double->clamp(0, 5); // Double::from(5.0) - above max
```

### Map

Applies a function to the Double's value and returns a new Double.

```php
$double = Double::from(3.14);
$rounded = $double->map(fn(float $x): float => round($x, 1)); // Double::from(3.1)
$doubled = $double->map(fn(float $x): float => $x * 2); // Double::from(6.28)
```

## Comparison

### Equals

Checks if this Double is equal to another value.

```php
$double = Double::from(3.14);
$isEqual = $double->eq(3.14); // true
$isEqual = $double->eq(Double::from(3.14)); // true
$isEqual = $double->eq(3.15); // false
```

### Greater Than

Checks if this Double is greater than another value.

```php
$double = Double::from(3.14);
$isGreaterThan = $double->gt(3.0); // true
$isGreaterThan = $double->gt(Double::from(4.0)); // false
```

### Greater Than or Equal

Checks if this Double is greater than or equal to another value.

```php
$double = Double::from(3.14);
$isGreaterThanOrEqual = $double->ge(3.14); // true
$isGreaterThanOrEqual = $double->ge(Double::from(4.0)); // false
```

### Less Than

Checks if this Double is less than another value.

```php
$double = Double::from(3.14);
$isLessThan = $double->lt(4.0); // true
$isLessThan = $double->lt(Double::from(3.0)); // false
```

### Less Than or Equal

Checks if this Double is less than or equal to another value.

```php
$double = Double::from(3.14);
$isLessThanOrEqual = $double->le(3.14); // true
$isLessThanOrEqual = $double->le(Double::from(3.0)); // false
```

### Compare

Compares this Double with another value and returns -1, 0, or 1.

```php
$double = Double::from(3.14);
$cmp = $double->cmp(3.0); // 1 (greater)
$cmp = $double->cmp(Double::from(3.14)); // 0 (equal)
$cmp = $double->cmp(4.0); // -1 (less)
```

### Approximate Equality

Checks if this Double is approximately equal to another value, within a specified epsilon.

```php
$double = Double::from(0.1 + 0.2); // Due to floating-point precision, this is not exactly 0.3
$isApproxEqual = $double->approxEq(0.3); // true (uses default epsilon)
$isApproxEqual = $double->approxEq(0.31, 0.001); // false (outside the epsilon range)
```

**Note:** This is useful when comparing floating-point values that may have precision errors.

### Signum

Returns -1.0 if the Double is negative, 0.0 if zero, or 1.0 if positive.

```php
$negative = Double::from(-3.14);
$sign = $negative->signum(); // Double::from(-1.0)

$zero = Double::from(0.0);
$sign = $zero->signum(); // Double::from(0.0)

$positive = Double::from(3.14);
$sign = $positive->signum(); // Double::from(1.0)
```

## Mathematical Functions

### Exponential and Logarithmic

#### Natural Logarithm (base e)

Returns the natural logarithm (base e) of this Double.

```php
$double = Double::from(Math.E);
$ln = $double->ln(); // Double::from(1.0)
```

#### Logarithm (custom base)

Returns the logarithm with the specified base.

```php
$double = Double::from(100.0);
$log = $double->log(10.0); // Double::from(2.0)
```

#### Logarithm (base 2)

Returns the base-2 logarithm of this Double.

```php
$double = Double::from(8.0);
$log2 = $double->log2(); // Double::from(3.0)
```

#### Logarithm (base 10)

Returns the base-10 logarithm of this Double.

```php
$double = Double::from(100.0);
$log10 = $double->log10(); // Double::from(2.0)
```

#### Exponential Function

Returns e raised to the power of this Double.

```php
$double = Double::from(1.0);
$exp = $double->exp(); // Double::from(2.718281828...)
```

#### Square Root

Returns the square root of this Double.

```php
$double = Double::from(16.0);
$sqrt = $double->sqrt(); // Double::from(4.0)
```

#### Cube Root

Returns the cube root of this Double.

```php
$double = Double::from(27.0);
$cbrt = $double->cbrt(); // Double::from(3.0)
```

### Trigonometric

#### Sine

Returns the sine of this Double (in radians).

```php
$double = Double::from(Math.PI / 2);
$sin = $double->sin(); // Double::from(1.0)
```

#### Cosine

Returns the cosine of this Double (in radians).

```php
$double = Double::from(0.0);
$cos = $double->cos(); // Double::from(1.0)
```

#### Tangent

Returns the tangent of this Double (in radians).

```php
$double = Double::from(Math.PI / 4);
$tan = $double->tan(); // Double::from(1.0)
```

#### Arcsine

Returns the arcsine of this Double.

```php
$double = Double::from(1.0);
$asin = $double->asin(); // Double::from(Math.PI / 2)
```

#### Arccosine

Returns the arccosine of this Double.

```php
$double = Double::from(1.0);
$acos = $double->acos(); // Double::from(0.0)
```

#### Arctangent

Returns the arctangent of this Double.

```php
$double = Double::from(1.0);
$atan = $double->atan(); // Double::from(Math.PI / 4)
```

#### Arctangent2

Returns the angle component of the point (x,y) in polar coordinates.

```php
$y = Double::from(1.0);
$x = Double::from(1.0);
$atan2 = $y->atan2($x); // Double::from(Math.PI / 4)
```

### Hyperbolic

#### Hyperbolic Sine

Returns the hyperbolic sine of this Double.

```php
$double = Double::from(0.0);
$sinh = $double->sinh(); // Double::from(0.0)
```

#### Hyperbolic Cosine

Returns the hyperbolic cosine of this Double.

```php
$double = Double::from(0.0);
$cosh = $double->cosh(); // Double::from(1.0)
```

#### Hyperbolic Tangent

Returns the hyperbolic tangent of this Double.

```php
$double = Double::from(0.0);
$tanh = $double->tanh(); // Double::from(0.0)
```

### Rounding

#### Round

Rounds this Double to the nearest integer.

```php
$double = Double::from(3.5);
$rounded = $double->round(); // Double::from(4.0)
```

#### Floor

Returns the largest integer less than or equal to this Double.

```php
$double = Double::from(3.7);
$floored = $double->floor(); // Double::from(3.0)
```

#### Ceiling

Returns the smallest integer greater than or equal to this Double.

```php
$double = Double::from(3.2);
$ceiled = $double->ceil(); // Double::from(4.0)
```

#### Truncate

Returns the integer part of this Double by removing the fractional digits.

```php
$double = Double::from(3.7);
$truncated = $double->trunc(); // Double::from(3.0)

$negative = Double::from(-3.7);
$truncated = $negative->trunc(); // Double::from(-3.0)
```

#### Fractional Part

Returns the fractional part of this Double.

```php
$double = Double::from(3.75);
$fractional = $double->fract(); // Double::from(0.75)
```

## Constants

### Pi

Returns the mathematical constant π.

```php
$pi = Double::pi(); // Double::from(3.14159265...)
```

### E

Returns the mathematical constant e.

```php
$e = Double::e(); // Double::from(2.71828182...)
```

### Infinity

Returns positive infinity.

```php
$infinity = Double::infinity(); // Double::from(INF)
```

### Negative Infinity

Returns negative infinity.

```php
$negInfinity = Double::negInfinity(); // Double::from(-INF)
```

### NaN

Returns Not a Number (NaN).

```php
$nan = Double::nan(); // Double::from(NaN)
```

## Special Operations

### To Radians

Converts degrees to radians.

```php
$degrees = Double::from(180.0);
$radians = $degrees->toRadians(); // Double::from(Math.PI)
```

### To Degrees

Converts radians to degrees.

```php
$radians = Double::from(Math.PI);
$degrees = $radians->toDegrees(); // Double::from(180.0)
```

## Type Safety

The Double class provides type safety through immutable operations. All methods that perform calculations return new Double instances rather than modifying the original, ensuring consistent behavior.

```php
$a = Double::from(10.5);
$b = $a->add(5.5); // $b is 16.0, $a remains 10.5

// Method chaining for complex calculations
$result = Double::from(10.0)
    ->mul(2.0)       // 20.0
    ->add(5.0)       // 25.0
    ->sqrt();        // 5.0
```

## Error Handling

The Double class uses the Result type for operations that may fail:

- Division by zero returns `Result<Double, InvalidArgumentException>`
- Mathematical functions with invalid inputs (like negative square root) handle errors appropriately

This approach allows for clean error handling without exceptions:

```php
$result = Double::from(10.0)->div(0.0);
$quotient = $result->match(
    fn($value) => "Result: {$value->toFloat()}",
    fn($error) => "Error: {$error->getMessage()}"
); // "Error: Division by zero"
```
