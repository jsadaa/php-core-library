<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Modules\Json;

use Jsadaa\PhpCoreLibrary\Modules\Json\Error\DecodingError;
use Jsadaa\PhpCoreLibrary\Modules\Json\Error\EncodingError;
use Jsadaa\PhpCoreLibrary\Modules\Json\Error\ValidationError;
use Jsadaa\PhpCoreLibrary\Modules\Result\Result;

/**
 * Json module provides safe wrapper methods for encoding, decoding, and validating JSON data.
 *
 * @psalm-immutable
 */
final class Json {
    /**
     * Encode a PHP value to JSON string.
     *
     * @param mixed $data The data to encode.
     * @param int $flags Flags to pass to json_encode.
     * @param int<1, 2147483647> $depth Maximum depth of the data structure.
     * @return Result<string, EncodingError>
     */
    public static function encode(mixed $data, int $flags = 0, int $depth = 512): Result {
        try {
            $encoded = \json_encode($data, $flags | \JSON_THROW_ON_ERROR, $depth);
        } catch (\JsonException $e) {
            /** @var Result<string, EncodingError> */
            return Result::err(new EncodingError($e->getMessage()));
        }

        /** @var Result<string, EncodingError> */
        return Result::ok($encoded);
    }

    /**
     * Decode a JSON string to PHP value.
     *
     * @param string $json The JSON string to decode.
     * @param int $flags Flags to pass to json_decode.
     * @param int<1, 2147483647> $depth Maximum depth of the data structure.
     * @return Result<mixed, DecodingError>
     */
    public static function decode(string $json, int $flags = 0, int $depth = 512): Result {
        try {
            /** @var mixed */
            $decoded = \json_decode($json, true, $depth, $flags | \JSON_THROW_ON_ERROR);
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
     * @param string $json The JSON string to validate.
     * @param int $flags Flags to pass to json_validate.
     * @param int<1, 2147483647> $depth Maximum depth of the data structure.
     * @return Result<null, ValidationError>
     */
    public static function validate(string $json, int $flags = 0, int $depth = 512): Result {
        try {
            \json_validate($json, $depth | \JSON_THROW_ON_ERROR, $flags);
        } catch (\JsonException $e) {
            /** @var Result<null, ValidationError> */
            return Result::err(new ValidationError($e->getMessage()));
        }

        /** @var Result<null, ValidationError> */
        return Result::ok(null);
    }
}
