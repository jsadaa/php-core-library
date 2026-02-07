# Json Module

> [!NOTE]
> **Design Philosophy**: This module is a thin, safe wrapper around PHP's native `json_encode`, `json_decode`, and `json_validate` functions. Instead of throwing exceptions or returning `false`, all operations return `Result` types with dedicated error classes, consistent with the rest of the library.

The Json module provides immutable, type-safe JSON encoding, decoding, and validation.

## Table of Contents

- [Encoding](#encoding)
- [Decoding](#decoding)
- [Validation](#validation)
- [Error Handling](#error-handling)

## Encoding

### encode()

Encodes a PHP value to a JSON string.

```php
use Jsadaa\PhpCoreLibrary\Modules\Json\Json;

$result = Json::encode(['name' => 'Alice', 'age' => 30]);
// Result::ok('{"name":"Alice","age":30}')

$result = Json::encode(['html' => '<p>Hello</p>'], \JSON_HEX_TAG);
// Result::ok('{"html":"\u003Cp\u003EHello\u003C\/p\u003E"}')

// Str values are automatically converted
$result = Json::encode(Str::of('hello'));
// Result::ok('"hello"')

// Encoding errors return typed Result
$resource = \fopen('php://memory', 'r');
$result = Json::encode(['res' => $resource]);
// Result::err(EncodingError)
\fclose($resource);
```

### encodeToStr()

Same as `encode()`, but wraps the result in a `Str` type.

```php
$result = Json::encodeToStr(['key' => 'value']);
// Result::ok(Str::of('{"key":"value"}'))

if ($result->isOk()) {
    $json = $result->unwrap(); // Str instance
    echo $json->contains('key'); // true
}
```

### Parameters

Both encoding methods accept:

| Parameter | Type | Default | Description |
|---|---|---|---|
| `$data` | `mixed` | (required) | The value to encode |
| `$flags` | `int` | `0` | `json_encode` flags (e.g. `JSON_PRETTY_PRINT`) |
| `$depth` | `int<1, 2147483647>` | `512` | Maximum nesting depth |

> [!TIP]
> `JSON_THROW_ON_ERROR` is always added internally. You don't need to include it in your flags.

## Decoding

### decode()

Decodes a JSON string to a PHP value. Always decodes to associative arrays (not objects).

```php
$result = Json::decode('{"name":"Alice","scores":[10,20,30]}');
// Result::ok(['name' => 'Alice', 'scores' => [10, 20, 30]])

// Accepts Str type
$result = Json::decode(Str::of('[1, 2, 3]'));
// Result::ok([1, 2, 3])

// Invalid JSON returns typed error
$result = Json::decode('{not valid}');
// Result::err(DecodingError)
```

### Parameters

| Parameter | Type | Default | Description |
|---|---|---|---|
| `$json` | `string\|Str` | (required) | The JSON string to decode |
| `$flags` | `int` | `0` | `json_decode` flags |
| `$depth` | `int<1, 2147483647>` | `512` | Maximum nesting depth |

## Validation

### validate()

Validates a JSON string without decoding it. Returns `Result<Unit, ValidationError>`.

```php
use Jsadaa\PhpCoreLibrary\Primitives\Unit;

$result = Json::validate('{"valid": true}');
// Result::ok(Unit)

$result = Json::validate('{invalid}');
// Result::err(ValidationError)

// Practical usage: validate before decoding
Json::validate($input)->match(
    fn(Unit $_) => Json::decode($input)->unwrap(),
    fn(ValidationError $e) => throw $e,
);
```

> [!TIP]
> `json_validate()` (PHP 8.3+) is faster than `json_decode()` for validation only, as it doesn't allocate the decoded structure.

## Error Handling

All error types extend `RuntimeException` and include the underlying PHP JSON error message.

| Error Class | Trigger | Example Message |
|---|---|---|
| `EncodingError` | `json_encode` failure | `Json encoding error : Type is not supported` |
| `DecodingError` | `json_decode` failure | `Json decoding error : Syntax error` |
| `ValidationError` | `json_validate` failure | `Json validation error : Syntax error` |

```php
$result = Json::decode($untrustedInput);

$result->match(
    fn(mixed $data) => processData($data),
    fn(DecodingError $error) => logError($error->getMessage()),
);
```
