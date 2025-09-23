<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Json;

use Jsadaa\PhpCoreLibrary\Modules\Json\Error\DecodingError;
use Jsadaa\PhpCoreLibrary\Modules\Json\Error\EncodingError;
use Jsadaa\PhpCoreLibrary\Modules\Json\Error\ValidationError;
use Jsadaa\PhpCoreLibrary\Modules\Result\Result;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use Jsadaa\PhpCoreLibrary\Primitives\Unit;

/**
 * Json module provides safe wrapper methods for encoding, decoding, and validating JSON data.
 *
 * @psalm-immutable
 */
final readonly class Json
{
    /**
     * Encode a PHP value to JSON string.
     *
     * @param mixed|Str $data The data to encode.
     * @param int $flags Flags to pass to json_encode.
     * @param int<1, 2147483647> $depth Maximum depth of the data structure.
     * @return Result<string, EncodingError> The encoded JSON string or an error.
     */
    public static function encode(mixed | Str $data, int $flags = 0, int $depth = 512): Result {
        try {
            $encoded = \json_encode($data instanceof Str ? $data->toString() : $data, $flags | \JSON_THROW_ON_ERROR, $depth);
        } catch (\JsonException $e) {
            /** @var Result<string, EncodingError> */
            return Result::err(new EncodingError($e->getMessage()));
        }

        /** @var Result<string, EncodingError> */
        return Result::ok($encoded);
    }

    /**
     * Encode a PHP value to JSON string wrapped in a Str
     *
     * @param mixed|Str $data The data to encode.
     * @param int $flags Flags to pass to json_encode.
     * @param int<1, 2147483647> $depth Maximum depth of the data structure.
     * @return Result<Str, EncodingError> The encoded JSON Str or an error.
     */
    public static function encodeToStr(mixed | Str $data, int $flags = 0, int $depth = 512): Result {
        try {
            $encoded = \json_encode($data instanceof Str ? $data->toString() : $data, $flags | \JSON_THROW_ON_ERROR, $depth);
        } catch (\JsonException $e) {
            /** @var Result<Str, EncodingError> */
            return Result::err(new EncodingError($e->getMessage()));
        }

        // Technically it should never happen as we throw an exception on error, but psalm does not understand that.
        if ($encoded === false) {
            /** @var Result<Str, EncodingError> */
            return Result::err(new EncodingError(\json_last_error_msg()));
        }

        /** @var Result<Str, EncodingError> */
        return Result::ok(Str::of($encoded));
    }

    /**
     * Decode a JSON string to PHP value.
     *
     * @param string|Str $json The JSON string to decode.
     * @param int $flags Flags to pass to json_decode.
     * @param int<1, 2147483647> $depth Maximum depth of the data structure.
     * @return Result<mixed, DecodingError> The decoded PHP value or an error.
     */
    public static function decode(string | Str $json, int $flags = 0, int $depth = 512): Result {
        try {
            /** @var mixed */
            $decoded = \json_decode($json instanceof Str ? $json->toString() : $json, true, $depth, $flags | \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            /** @var Result<mixed, DecodingError> */
            return Result::err(new DecodingError($e->getMessage()));
        }

        /** @var Result<mixed, DecodingError> */
        return Result::ok($decoded);
    }

    /**
     * Validate a JSON string.
     *
     * @param string|Str $json The JSON string to validate.
     * @param int $flags Flags to pass to json_validate.
     * @param int<1, 2147483647> $depth Maximum depth of the data structure.
     * @return Result<Unit, ValidationError> The validation result
     */
    public static function validate(string | Str $json, int $flags = 0, int $depth = 512): Result {
        try {
            \json_validate($json instanceof Str ? $json->toString() : $json, $depth | \JSON_THROW_ON_ERROR, $flags);
        } catch (\JsonException $e) {
            /** @var Result<Unit, ValidationError> */
            return Result::err(new ValidationError($e->getMessage()));
        }

        /** @var Result<Unit, ValidationError> */
        return Result::ok(Unit::new());
    }
}
