# Result

The `Result` type represents either success (`Ok<T>`) or failure (`Err<E>`). It provides a safe way to handle operations that might fail without using exceptions.

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

The Result type implements "railway-oriented programming" by explicitly representing the success or failure path of an operation. It's a sum type with two variants:
- `Ok<T>`: Represents a successful operation with a value of type T
- `Err<E>`: Represents a failed operation with an error of type E

This makes error handling explicit and type-safe, eliminating the need for exceptions in many cases.

## Creation

### Ok

Creates a new Result indicating success with a value.

```php
// Create a successful result with an integer value
$intResult = Result::ok(42);

// Create a successful result with a string value
$stringResult = Result::ok("Hello");

// Create a successful result with an object
$userResult = Result::ok(new User("Alice"));
```

### Err

Creates a new Result indicating failure with an error.

```php
// Create a failure result with a string error message
$stringError = Result::err("Not found");

// Create a failure result with an exception
$exceptionError = Result::err(new NotFoundException("User not found"));

// Create a failure result with a custom error type
$validationError = Result::err(new ValidationError(["name" => "Name is required"]));
```

## Basic Operations

### Is Ok

Checks if the Result represents success.

```php
$result = Result::ok(42);
$isOk = $result->isOk(); // true

$err = Result::err("error");
$isOk = $err->isOk(); // false
```

### Is Err

Checks if the Result represents failure.

```php
$result = Result::ok(42);
$isErr = $result->isErr(); // false

$err = Result::err("error");
$isErr = $err->isErr(); // true
```

### Is Err And

Checks if the Result is an error and the contained error value satisfies the given predicate.

```php
$result = Result::err("not found");
$isNotFound = $result->isErrAnd(fn($e) => $e === "not found"); // true
$isServerError = $result->isErrAnd(fn($e) => $e === "server error"); // false

$ok = Result::ok(42);
$isNotFound = $ok->isErrAnd(fn($e) => $e === "not found"); // false (always false for Ok)
```

### Is Ok And

Checks if the Result is successful and the contained value satisfies the given predicate.

```php
$result = Result::ok(42);
$isEven = $result->isOkAnd(fn($x) => $x % 2 === 0); // true
$isOdd = $result->isOkAnd(fn($x) => $x % 2 === 1); // false

$err = Result::err("error");
$isEven = $err->isOkAnd(fn($x) => $x % 2 === 0); // false (always false for Err)
```

### String Representation

Returns a string representation of the Result.

```php
$result = Result::ok(42);
echo $result; // "Result<integer>"

$err = Result::err("Not found");
echo $err; // "Err<string>"
```

## Pattern Matching

### Match

Applies one of two functions depending on whether the Result is Ok or Err.

```php
$result = Result::ok(42);
$output = $result->match(
    fn($value) => "Success: $value",  // Called if Ok
    fn($error) => "Error: $error"     // Called if Err
); // "Success: 42"

$err = Result::err("Not found");
$output = $err->match(
    fn($value) => "Success: $value",
    fn($error) => "Error: $error"
); // "Error: Not found"
```

Pattern matching is the safest and most idiomatic way to handle Result values, as it forces you to consider both the success and failure cases.

## Value Extraction

### Unwrap

Returns the success value or throws an exception if the Result is an error.

```php
$result = Result::ok(42);
$value = $result->unwrap(); // 42

$err = Result::err("Not found");
// $value = $err->unwrap(); // Throws RuntimeException: Cannot unwrap Err
```

**Note:** This should only be used when you are absolutely certain that the Result is Ok. It's generally safer to use `match`, `unwrapOr`, or `unwrapOrElse`.

### Unwrap Err

Returns the error value or throws an exception if the Result is a success.

```php
$err = Result::err("Not found");
$error = $err->unwrapErr(); // "Not found"

$result = Result::ok(42);
// $error = $result->unwrapErr(); // Throws RuntimeException: Cannot unwrap Ok
```

### Unwrap Or

Returns the success value or a default value if the Result is an error.

```php
$result = Result::ok(42);
$value = $result->unwrapOr(0); // 42

$err = Result::err("Not found");
$value = $err->unwrapOr(0); // 0
```

### Unwrap Or Else

Returns the success value or computes a value from a closure if the Result is an error.

```php
$result = Result::ok(42);
$value = $result->unwrapOrElse(fn() => computeDefault()); // 42

$err = Result::err("Not found");
$value = $err->unwrapOrElse(fn() => computeExpensiveDefault()); // Result of computeExpensiveDefault()
```

This is useful when the default value is expensive to compute or needs to be freshly generated each time.

## Transformation

### Map

Transforms the success value using a mapper function.

```php
$result = Result::ok(42);
$doubled = $result->map(fn($x) => $x * 2); // Ok(84)

$err = Result::err("Not found");
$doubled = $err->map(fn($x) => $x * 2); // Err("Not found")
```

Map preserves the Result structure - if the original is an Err, the result is also an Err with the same error value, and the mapper function is never called.

### Map Err

Transforms the error value using a mapper function.

```php
$result = Result::err("not found");
$withCode = $result->mapErr(fn($e) => ["code" => 404, "message" => $e]); // Err(["code" => 404, "message" => "not found"])

$ok = Result::ok(42);
$withCode = $ok->mapErr(fn($e) => ["code" => 404, "message" => $e]); // Ok(42)
```

Map Err preserves the Result structure - if the original is an Ok, the result is also an Ok with the same success value, and the mapper function is never called.

### Map Or

Transforms the success value using a mapper function or returns a default value if the Result is an error.

```php
$result = Result::ok(42);
$doubled = $result->mapOr(fn($x) => $x * 2, 0); // 84

$err = Result::err("Not found");
$doubled = $err->mapOr(fn($x) => $x * 2, 0); // 0
```

### Map Or Else

Transforms the success value using a mapper function or computes a value from the error using a second function.

```php
$result = Result::ok(42);
$doubled = $result->mapOrElse(
    fn($x) => $x * 2,
    fn($e) => strlen($e)
); // 84

$err = Result::err("Not found");
$value = $err->mapOrElse(
    fn($x) => $x * 2,
    fn($e) => strlen($e)
); // 9 (length of "Not found")
```

### Flatten

Flattens a nested Result by unwrapping one level of nesting.

```php
$nestedResult = Result::ok(Result::ok(42));
$flattened = $nestedResult->flatten(); // Ok(42)

$okValue = Result::ok(42);
$flattened = $okValue->flatten(); // Ok(42) (non-Result values remain wrapped)

$err = Result::err("Not found");
$flattened = $err->flatten(); // Err("Not found")
```

This is useful when working with functions that return Results and you want to avoid deeply nested Result structures.

## Composition

Results can be composed in various ways:

### Or and OrElse

The `or` and `orElse` methods provide alternatives when the Result is an error:

```php
// Or: Returns the Result if it is Ok, otherwise returns the provided Result
$result = Result::ok(42);
$finalResult = $result->or(Result::ok(100)); // Ok(42)

$err = Result::err("Not found");
$finalResult = $err->or(Result::ok(100)); // Ok(100)

// OrElse: Similar to or, but computes the alternative Result based on the error
$result = Result::ok(42);
$finalResult = $result->orElse(fn($e) => Result::ok(computeValue())); // Ok(42)

$err = Result::err("Not found");
$finalResult = $err->orElse(fn($e) => Result::ok("Default for error: $e")); // Ok("Default for error: Not found")
```

### Inspect and InspectErr

The `inspect` and `inspectErr` methods allow executing side effects without changing the Result:

```php
// Inspect: Execute a callback on the success value without changing the Result
$result = Result::ok(42);
$unchanged = $result->inspect(fn($value) => logger()->info("Success: $value")); // Ok(42)

// InspectErr: Execute a callback on the error value without changing the Result
$err = Result::err("Not found");
$unchanged = $err->inspectErr(fn($e) => logger()->error("Error: $e")); // Err("Not found")
```

### Option

Convert a Result to an Option:

```php
$result = Result::ok(42);
$option = $result->option(); // Some(42)

$err = Result::err("Not found");
$option = $err->option(); // None
```

This is useful when you only care about the success value and want to discard any error information.

### AndThen (Railway-Oriented Programming)

The `andThen` method is the cornerstone of composing failable operations. It calls the callback only if the Result is `Ok`, and the callback itself returns a `Result`. If any step in the chain produces an `Err`, all subsequent steps are skipped and the error propagates through.

This is the PHP equivalent of Rust's `?` operator: a way to chain operations where any step can fail, without nesting `match()` calls.

```php
// Without andThen: deeply nested and hard to read
$usernameResult = validateUsername("bob");
$validUser = $usernameResult->match(
    function($username) {
        return validateEmail("bob@example.com")->match(
            fn($email) => ["username" => $username, "email" => $email],
            fn($error) => $error
        );
    },
    fn($error) => $error
);

// With andThen: flat, readable pipeline
$result = validateUsername("bob")
    ->andThen(fn($username) => validateEmail("bob@example.com")
        ->map(fn($email) => ["username" => $username, "email" => $email])
    );
```

**Arithmetic chaining:**

```php
// Each division can fail with DivisionByZero
$result = Integer::of(100)
    ->div(2)                                    // Result::ok(Integer::of(50))
    ->andThen(fn($val) => $val->div(5))         // Result::ok(Integer::of(10))
    ->andThen(fn($val) => $val->div(0));        // Result::err(DivisionByZero)
    // The chain short-circuits at the first Err
```

**File operations pipeline:**

```php
// Create, write, and configure a file in one pipeline
$result = File::new('/path/to/config.json')
    ->andThen(fn($file) => $file->write('{"debug": true}'))
    ->andThen(fn($file) => $file->setPermissions(Permissions::create(0644)));

// If any step fails (permission denied, disk full, etc.), the Err propagates
$result->match(
    fn($file) => "Configuration file created successfully",
    fn($error) => "Failed: " . $error->getMessage()
);
```

**Input validation pipeline:**

```php
// Parse and validate in one clean chain
$result = Str::of($userInput)
    ->parseInt()                                         // Result<Integer, ParseError>
    ->andThen(fn($n) => $n->gt(0)
        ? Result::ok($n)
        : Result::err('Must be positive')
    )
    ->andThen(fn($n) => $n->le(100)
        ? Result::ok($n)
        : Result::err('Must be at most 100')
    )
    ->map(fn($n) => $n->toInt());                        // Extract the raw int

$result->match(
    fn($value) => "Valid input: $value",
    fn($error) => "Invalid: $error"
);
```

> [!TIP]
> Use `andThen()` when the callback returns a `Result`. Use `map()` when the callback returns a plain value. Think of `andThen()` as "and then try this, which might also fail" and `map()` as "and then transform the success value".

### Chaining Operations

```php
// Validation functions that return Result
function validateUsername(string $username): Result {
    return strlen($username) >= 3
        ? Result::ok($username)
        : Result::err("Username must be at least 3 characters");
}

function validateEmail(string $email): Result {
    return filter_var($email, FILTER_VALIDATE_EMAIL)
        ? Result::ok($email)
        : Result::err("Invalid email format");
}

// Chain them with andThen
$result = validateUsername("bob")
    ->andThen(fn($username) => validateEmail("bob@example.com")
        ->map(fn($email) => ["username" => $username, "email" => $email])
    );

$result->match(
    fn($data) => "Valid: {$data['username']} <{$data['email']}>",
    fn($error) => "Error: $error"
);
```

### Working with Collections

Results work well with collection types:

```php
$ids = Sequence::of(1, 2, 3, 4);

// Find users by ID (some might not exist)
$userResults = $ids->map(fn($id) => findUser($id)); // Sequence<Result<User, Error>>

// Extract only the successful results
$validUsers = $userResults
    ->filter(fn($result) => $result->isOk())
    ->map(fn($result) => $result->unwrap());
```

## Error Handling

Result is specifically designed for error handling. It makes errors explicit in function signatures and forces callers to handle potential failures.

```php
// Without Result
function divide($a, $b) {
    if ($b === 0) {
        throw new DivisionByZeroException();
    }
    return $a / $b;
}

// With Result
function divide($a, $b): Result {
    if ($b === 0) {
        return Result::err(new DivisionByZeroException());
    }
    return Result::ok($a / $b);
}

// Usage
$result = divide(10, 0);
$result->match(
    fn($value) => echo "Result: $value",
    fn($error) => echo "Error: {$error->getMessage()}"
);
```

## Type Safety

Result ensures type safety for error handling:

```php
// Without Result
function getUserName(?User $user): ?string {
    try {
        return $user->getName();
    } catch (Exception $e) {
        return null;
    }
}
// Caller doesn't know what went wrong if null is returned

// With Result
function getUserName(?User $user): Result<string, Exception> {
    if ($user === null) {
        return Result::err(new NotFoundException("User not found"));
    }
    try {
        return Result::ok($user->getName());
    } catch (Exception $e) {
        return Result::err($e);
    }
}
// Caller gets specific error information
getUserName($user)->match(
    fn($name) => echo $name,
    fn($error) => echo "Error: {$error->getMessage()}"
);
```

## Best Practices

### Do

- Use Result for operations that might fail
- Make error types explicit and descriptive
- Handle both Ok and Err cases with `match()`
- Use `unwrapOr()` or `unwrapOrElse()` for safe defaults
- Chain operations when appropriate

### Avoid

- Using `unwrap()` unless you're certain the Result is Ok
- Hiding errors by immediately defaulting without handling them
- Using overly generic error types that don't convey useful information
- Unnecessarily converting exceptions to Result when exceptions are more appropriate

### Example: Database Operation

```php
function findUser(int $id): Result<User, DatabaseError> {
    try {
        $user = $database->query("SELECT * FROM users WHERE id = ?", $id)->fetch();
        if ($user === false) {
            return Result::err(new DatabaseError("User not found", 404));
        }
        return Result::ok(new User($user));
    } catch (PDOException $e) {
        return Result::err(new DatabaseError($e->getMessage(), $e->getCode()));
    }
}

// Usage
$userResult = findUser(42);
$userResult->match(
    function($user) {
        // Handle user found
        echo "Found user: {$user->getName()}";
    },
    function($error) {
        // Handle specific error
        if ($error->getCode() === 404) {
            echo "User doesn't exist";
        } else {
            echo "Database error: {$error->getMessage()}";
        }
    }
);
```

The Result type makes error handling explicit, type-safe, and compositional, encouraging robust code that handles both success and failure paths appropriately.
