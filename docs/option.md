# Option

The `Option` type represents an optional value that can be either `Some(value)` or `None`. It provides a safe way to handle values that might not exist without using null references.

## Table of Contents

- [Overview](#overview)
- [Creation](#creation)
- [Basic Operations](#basic-operations)
- [Pattern Matching](#pattern-matching)
- [Value Extraction](#value-extraction)
- [Transformation](#transformation)
- [Composition](#composition)
- [Error Handling](#error-handling)
- [Type Safety](#type-safety)
- [Best Practices](#best-practices)

## Overview

The Option type implements the "null object pattern" in a functional way, making code safer by forcing explicit handling of the absence of values. It eliminates null reference errors and provides a rich set of operations for working with optional values.

Option is implemented as a sum type with two variants:
- `Some<T>`: Contains a value of type T
- `None`: Represents the absence of a value

## Creation

### Some

Creates a new Option containing a value.

```php
// Create an Option with an integer value
$intOption = Option::some(42);

// Create an Option with a string value
$stringOption = Option::some("Hello");

// Create an Option with an object
$userOption = Option::some(new User("Alice"));
```

### None

Creates a new Option with no value.

```php
// Create an empty Option
$none = Option::none();
```

## Basic Operations

### Is Some

Checks if the Option contains a value.

```php
$option = Option::some(42);
$isSome = $option->isSome(); // true

$none = Option::none();
$isSome = $none->isSome(); // false
```

### Is None

Checks if the Option contains no value.

```php
$option = Option::some(42);
$isNone = $option->isNone(); // false

$none = Option::none();
$isNone = $none->isNone(); // true
```

### Is Some And

Checks if the Option is Some and the contained value satisfies the given predicate.

```php
$option = Option::some(42);
$isEven = $option->isSomeAnd(fn($x) => $x % 2 === 0); // true
$isOdd = $option->isSomeAnd(fn($x) => $x % 2 === 1);  // false

$none = Option::none();
$isEven = $none->isSomeAnd(fn($x) => $x % 2 === 0);   // false
```

### Is None Or

Checks if the Option is None or the contained value satisfies the given predicate.

```php
$option = Option::some(42);
$isEvenOrNone = $option->isNoneOr(fn($x) => $x % 2 === 0); // true
$isOddOrNone = $option->isNoneOr(fn($x) => $x % 2 === 1);  // false

$none = Option::none();
$predicate = $none->isNoneOr(fn($x) => false); // true (always true for None)
```

### String Representation

Returns a string representation of the Option.

```php
$option = Option::some(42);
echo $option; // "Some<integer>"

$none = Option::none();
echo $none; // "None"
```

## Pattern Matching

### Match

Applies one of two functions depending on whether the Option is Some or None.

```php
$option = Option::some(42);
$result = $option->match(
    fn($value) => "Got value: $value",  // Called if Some
    fn() => "No value present"          // Called if None
); // "Got value: 42"

$none = Option::none();
$result = $none->match(
    fn($value) => "Got value: $value",
    fn() => "No value present"
); // "No value present"
```

Pattern matching is the safest and most idiomatic way to handle Option values, as it forces you to consider both the Some and None cases.

## Value Extraction

### Unwrap

Returns the contained value or throws an exception if None.

```php
$option = Option::some(42);
$value = $option->unwrap(); // 42

$none = Option::none();
// $value = $none->unwrap(); // Throws RuntimeException: Cannot unwrap None
```

**Note:** This should only be used when you are absolutely certain that the Option is Some. It's generally safer to use `match`, `unwrapOr`, or `unwrapOrElse`.

### Unwrap Or

Returns the contained value or a default value if None.

```php
$option = Option::some(42);
$value = $option->unwrapOr(0); // 42

$none = Option::none();
$value = $none->unwrapOr(0); // 0
```

### Unwrap Or Else

Returns the contained value or computes a value from a closure if None.

```php
$option = Option::some(42);
$value = $option->unwrapOrElse(fn() => computeDefault()); // 42

$none = Option::none();
$value = $none->unwrapOrElse(fn() => computeExpensiveDefault()); // Result of computeExpensiveDefault()
```

This is useful when the default value is expensive to compute or needs to be freshly generated each time.

## Transformation

### Map

Transforms the contained value using a mapper function.

```php
$option = Option::some(42);
$doubled = $option->map(fn($x) => $x * 2); // Some(84)

$none = Option::none();
$doubled = $none->map(fn($x) => $x * 2); // None
```

Map preserves the Option structure - if the original is None, the result is also None, and the mapper function is never called.

### Map Or

Transforms the contained value using a mapper function or returns a default value if None.

```php
$option = Option::some(42);
$doubled = $option->mapOr(fn($x) => $x * 2, 0); // 84

$none = Option::none();
$doubled = $none->mapOr(fn($x) => $x * 2, 0);   // 0
```

### Map Or Else

Transforms the contained value using a mapper function or computes a value from a closure if None.

```php
$option = Option::some(42);
$doubled = $option->mapOrElse(
    fn($x) => $x * 2,
    fn() => computeDefault()
); // 84

$none = Option::none();
$doubled = $none->mapOrElse(
    fn($x) => $x * 2,
    fn() => computeExpensiveDefault()
); // Result of computeExpensiveDefault()
```

This is useful when the default value is expensive to compute or needs to be freshly generated each time.

### Filter

Filters the Option based on a predicate function.

```php
$option = Option::some(42);
$isEven = $option->filter(fn($x) => $x % 2 === 0); // Some(42)
$isOdd = $option->filter(fn($x) => $x % 2 === 1);  // None

$none = Option::none();
$filtered = $none->filter(fn($x) => true); // None
```

If the Option is Some and the predicate returns true, the result is the original Option. If the predicate returns false or the Option is None, the result is None.

### Flatten

Flattens a nested Option by unwrapping one level of nesting.

```php
$nestedOption = Option::some(Option::some(42));
$flattened = $nestedOption->flatten(); // Some(42)

$someValue = Option::some(42);
$flattened = $someValue->flatten(); // Some(42) (non-Option values remain wrapped)

$none = Option::none();
$flattened = $none->flatten(); // None
```

This is useful when working with functions that return Options and you want to avoid deeply nested Option structures.

### Inspect

Executes a side effect on the Option's value if it is Some, without changing the Option itself.

```php
$option = Option::some(42);
$unchanged = $option->inspect(fn($x) => logger()->info("Found value: $x")); // Some(42)

$none = Option::none();
$unchanged = $none->inspect(fn($x) => logger()->info("This won't execute")); // None
```

This is useful for adding logging or debugging without affecting the Option structure or flow.

## Composition

Options can be composed in various ways:

### Or and OrElse

The `or` and `orElse` methods provide alternatives when the Option is None:

```php
// Or: Returns the Option if it is Some, otherwise returns the provided Option
$option = Option::some(42);
$result = $option->or(Option::some(100)); // Some(42)

$none = Option::none();
$result = $none->or(Option::some(100));   // Some(100)

// OrElse: Similar to or, but lazily evaluates the alternative
$option = Option::some(42);
$result = $option->orElse(fn() => Option::some(computeValue())); // Some(42)

$none = Option::none();
$result = $none->orElse(fn() => Option::some(computeValue()));   // Some(result of computeValue())
```

These methods are useful for providing fallbacks when working with multiple optional values.

### Chaining Operations

```php
$userOption = getUserById($id); // Option<User>

$emailOption = $userOption
    ->filter(fn($user) => $user->isActive())
    ->map(fn($user) => $user->getEmail()); // Option<string>
```

This will return the email as Some only if the user exists and is active, otherwise None.

### Working with Collections

Options work well with collection types:

```php
$users = Vec::from($user1, $user2, $user3);

// Get the emails of all active users
$activeEmails = $users
    ->map(fn($user) => Option::some($user)
        ->filter(fn($u) => $u->isActive())
        ->map(fn($u) => $u->getEmail())
    )
    ->filter(fn($opt) => $opt->isSome())
    ->map(fn($opt) => $opt->unwrap());
```

## Error Handling

Option is designed for handling the absence of values, not for errors. For error handling, consider using the `Result` type instead.

```php
// Option for absence of value
$userOption = findUser($id); // Option<User>

// Result for operations that may fail with an error
$result = validateUser($user); // Result<User, ValidationError>
```

### Converting to Result

Options can be converted to Results using `okOr` and `okOrElse`:

```php
// okOr: Transforms an Option into a Result, providing an error value if None
$option = Option::some(42);
$result = $option->okOr("Value not found"); // Result::ok(42)

$none = Option::none();
$result = $none->okOr("Value not found");   // Result::err("Value not found")

// okOrElse: Similar to okOr, but lazily evaluates the error value
$option = Option::some(42);
$result = $option->okOrElse(fn() => generateError()); // Result::ok(42)

$none = Option::none();
$result = $none->okOrElse(fn() => generateError());   // Result::err(result of generateError())
```

This is useful when you need to propagate errors with specific error information.

## Type Safety

Option ensures type safety even for nullable values:

```php
// Without Option
function getUserName(?User $user): ?string {
    return $user ? $user->getName() : null;
}
// Caller has to remember to check for null
$name = getUserName($user);
if ($name !== null) {
    echo $name;
}

// With Option
function getUserName(Option<User> $user): Option<string> {
    return $user->map(fn($u) => $u->getName());
}
// Type system enforces handling of the None case
getUserName($userOption)->match(
    fn($name) => echo $name,
    fn() => echo "No name available"
);
```

## Best Practices

### Do

- Use Option when a value might be absent
- Handle both Some and None cases with `match()`
- Use `unwrapOr()` or `unwrapOrElse()` for safe defaults
- Chain operations with `map()` and `filter()`

### Avoid

- Using `unwrap()` unless you're certain the Option is Some
- Using Option for error handling (use Result instead)
- Deeply nesting Option operations (prefer flat chains)

### Example: Optional Configuration

```php
$config = loadConfig(); // Option<Config>

$port = $config
    ->map(fn($c) => $c->getServerSettings())
    ->map(fn($s) => $s->getPort())
    ->unwrapOr(8080); // Default port if any part of the chain is None
```

The Option type makes your code more robust by forcing explicit handling of the absence of values, eliminating null reference errors, and providing a rich set of operations for working with optional values in a functional style.