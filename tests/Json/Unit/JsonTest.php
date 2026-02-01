<?php

declare(strict_types=1);

namespace Jsadaa\PhpCoreLibrary\Tests\Json\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Json\Json;
use Jsadaa\PhpCoreLibrary\Modules\Json\Error\DecodingError;
use Jsadaa\PhpCoreLibrary\Modules\Json\Error\EncodingError;
use Jsadaa\PhpCoreLibrary\Modules\Json\Error\ValidationError;
use Jsadaa\PhpCoreLibrary\Modules\Result\Err;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Json module.
 */
final class JsonTest extends TestCase
{

    public function testEncode(): void
    {
        $data = ['foo' => 'bar', 'baz' => 123];
        $result = Json::encode($data);

        $this->assertTrue($result->isOk());
        $this->assertJsonStringEqualsJsonString('{"foo":"bar","baz":123}', $result->unwrap());
    }

    public function testEncodeStr(): void
    {
        $result = Json::encodeToStr(['test' => true]);

        $this->assertTrue($result->isOk());
        $this->assertInstanceOf(Str::class, $result->unwrap());
        $this->assertEquals('{"test":true}', $result->unwrap()->toString());
    }

    public function testEncodeError(): void
    {
        // Resources cannot be JSON encoded
        $resource = fopen('php://memory', 'r');
        $result = Json::encode(['res' => $resource]);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(EncodingError::class, $result->unwrapErr());
        fclose($resource);
    }

    public function testDecode(): void
    {
        $json = '{"foo":"bar","baz":123}';
        $result = Json::decode($json);

        $this->assertTrue($result->isOk());
        $this->assertEquals(['foo' => 'bar', 'baz' => 123], $result->unwrap());
    }

    public function testDecodeStr(): void
    {
        $json = Str::of('{"foo":"bar"}');
        $result = Json::decode($json);

        $this->assertTrue($result->isOk());
        $this->assertEquals(['foo' => 'bar'], $result->unwrap());
    }

    public function testDecodeError(): void
    {
        $json = '{invalid json}';
        $result = Json::decode($json);

        $this->assertTrue($result->isErr());
        $this->assertInstanceOf(DecodingError::class, $result->unwrapErr());
    }

    public function testValidate(): void
    {
        $this->assertTrue(Json::validate('{"foo":"bar"}')->isOk());
        $this->assertTrue(Json::validate('{invalid}')->isErr());
        $this->assertInstanceOf(ValidationError::class, Json::validate('{invalid}')->unwrapErr());
    }
}
