<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Str\Unit;

use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use PHPUnit\Framework\TestCase;

final class StrManipulationTest extends TestCase
{
    public function testInsertWithValidIndices(): void
    {
        $str = Str::of('Hello');
        $newStr = $str->insertAt(5, Str::of(' world'));

        $this->assertSame(
            'Hello',
            $str->toString(),
            'Original string should remain unchanged',
        );

        $this->assertEquals(
            'Hello world',
            $newStr->toString(),
            'New string should contain inserted string',
        );

        $this->assertSame($str->chars()->len()->toInt() + \mb_strlen(' world'), $newStr->chars()->len()->toInt());
    }

    public function testTake(): void
    {
        $str = Str::of('Hello World');
        $taken = $str->take(5);

        $this->assertSame(
            'Hello World',
            $str->toString(),
            'Original string should remain unchanged',
        );
        $this->assertSame('Hello', $taken->toString());
    }

    public function testTakeWithZeroLength(): void
    {
        $str = Str::of('Hello World');
        $taken = $str->take(0);

        $this->assertTrue($taken->isEmpty());
    }

    public function testTakeWithNegativeLength(): void
    {
        $str = Str::of('Hello World');
        $taken = $str->take(-5);

        $this->assertTrue($taken->isEmpty());
    }

    public function testTakeWithLengthGreaterThanString(): void
    {
        $str = Str::of('Hello');
        $taken = $str->take(10);

        $this->assertSame('Hello', $taken->toString());
    }

    public function testTakeWithUtf8(): void
    {
        $str = Str::of('H😀llo World');
        $taken = $str->take(3);

        $this->assertSame('H😀l', $taken->toString());
    }

    public function testDrop(): void
    {
        $str = Str::of('Hello World');
        $dropped = $str->skip(6);

        $this->assertSame(
            'Hello World',
            $str->toString(),
            'Original string should remain unchanged',
        );
        $this->assertSame('World', $dropped->toString());
    }

    public function testDropWithZeroLength(): void
    {
        $str = Str::of('Hello World');
        $dropped = $str->skip(0);

        $this->assertSame('Hello World', $dropped->toString());
    }

    public function testDropWithNegativeLength(): void
    {
        $str = Str::of('Hello World');
        $dropped = $str->skip(-5);

        $this->assertSame('Hello World', $dropped->toString());
    }

    public function testDropWithLengthGreaterThanString(): void
    {
        $str = Str::of('Hello');
        $dropped = $str->skip(10);

        $this->assertTrue($dropped->isEmpty());
    }

    public function testDropWithUtf8(): void
    {
        $str = Str::of('H😀llo World');
        $dropped = $str->skip(3);

        $this->assertSame('lo World', $dropped->toString());
    }

    public function testInsertWithUtf8(): void
    {
        $str = Str::of('H😄llo');
        $newStr = $str->insertAt(2, Str::of('X'));

        $this->assertEquals(
            'H😄Xllo',
            $newStr->toString(),
            'New string should contain inserted string',
        );

        $this->assertSame($str->chars()->len()->toInt() + \mb_strlen('X'), $newStr->chars()->len()->toInt());
    }

    public function testInsertAtBeginning(): void
    {
        $str = Str::of('World');
        $newStr = $str->insertAt(0, Str::of('Hello '));

        $this->assertSame('Hello World', $newStr->toString());
    }

    public function testInsertAtEnd(): void
    {
        $str = Str::of('Hello');
        $newStr = $str->insertAt(5, Str::of(' World'));

        $this->assertSame('Hello World', $newStr->toString());
    }

    public function testPush(): void
    {
        $str = Str::of('Hello')->append(Str::of(' World'));

        $this->assertSame('Hello World', $str->toString());
    }

    public function testPushWithUtf8(): void
    {
        $str = Str::of('Hello')->append(Str::of(' W😄rld'));

        $this->assertSame('Hello W😄rld', $str->toString());
    }

    public function testRemoveWithValidIndex(): void
    {
        $str = Str::of('Hello');
        $modifiedStr = $str->removeAt(1);

        $this->assertSame('Hllo', $modifiedStr->toString());
    }

    public function testRemoveWithUtf8(): void
    {
        $str = Str::of('Héllo😄');
        $modifiedStr = $str->removeAt(1);

        $this->assertSame('Hllo😄', $modifiedStr->toString());
    }

    public function testRemoveMatches(): void
    {
        $str = Str::of('Hello123World456');
        $newStr = $str->removeMatches("/\d+/");

        $this->assertSame(
            'Hello123World456',
            $str->toString(),
            'Original string should remain unchanged',
        );
        $this->assertSame('HelloWorld', $newStr->toString());
    }

    public function testRemoveMatchesWithUtf8(): void
    {
        $str = Str::of('Hello123World456😄');
        $newStr = $str->removeMatches("/\d+/");

        $this->assertSame('HelloWorld😄', $newStr->toString());
    }

    public function testRemoveMatchesWithNoMatches(): void
    {
        $str = Str::of('HelloWorld');
        $newStr = $str->removeMatches("/\d+/");

        $this->assertSame('HelloWorld', $newStr->toString());
    }

    public function testRemoveMatchesWithInvalidPattern(): void
    {
        $str = Str::of('HelloWorld');

        $newStr = $str->removeMatches('/zzzz/');

        $this->assertSame('HelloWorld', $newStr->toString());
    }

    public function testTruncate(): void
    {
        $str = Str::of('Hello World');
        $truncated = $str->truncate(5);

        $this->assertSame(
            'Hello World',
            $str->toString(),
            'Original string should remain unchanged',
        );
        $this->assertSame('Hello', $truncated->toString());
    }

    public function testTruncateWithEqualLength(): void
    {
        $str = Str::of('Hello');
        $truncated = $str->truncate(5);

        $this->assertSame('Hello', $truncated->toString());
    }

    public function testTruncateWithGreaterLength(): void
    {
        $str = Str::of('Hello');
        $truncated = $str->truncate(10);

        $this->assertSame('Hello', $truncated->toString());
    }

    public function testTruncateWithZeroLength(): void
    {
        $str = Str::of('Hello');
        $truncated = $str->truncate(0);

        $this->assertSame('', $truncated->toString());
    }

    public function testTruncateWithNegativeLength(): void
    {
        $str = Str::of('Hello');
        $truncated = $str->truncate(-1);

        $this->assertSame('', $truncated->toString());
    }

    public function testReplace(): void
    {
        $str = Str::of('Hello World');
        $replaced = $str->replace(Str::of('World'), Str::of('PHP'));

        $this->assertSame(
            'Hello World',
            $str->toString(),
            'Original string should remain unchanged',
        );
        $this->assertSame('Hello PHP', $replaced->toString());
    }

    public function testReplaceWithMultipleOccurrences(): void
    {
        $str = Str::of('Hello World Hello');
        $replaced = $str->replace(Str::of('Hello'), Str::of('Hi'));

        $this->assertSame('Hi World Hi', $replaced->toString());
    }

    public function testReplaceWithUtf8(): void
    {
        $str = Str::of('H😀llo W😀rld');
        $replaced = $str->replace(Str::of('😀'), Str::of('e'));

        $this->assertSame('Hello Werld', $replaced->toString());
    }

    public function testReplaceWithNonExistentSearch(): void
    {
        $str = Str::of('Hello World');
        $replaced = $str->replace(Str::of('Goodbye'), Str::of('Hi'));

        $this->assertSame('Hello World', $replaced->toString());
    }

    public function testReplaceRange(): void
    {
        $str = Str::of('Hello World');
        $replaced = $str->replaceRange(6, 5, Str::of('PHP'));

        $this->assertSame(
            'Hello World',
            $str->toString(),
            'Original string should remain unchanged',
        );
        $this->assertSame('Hello PHP', $replaced->toString());
    }

    public function testReplaceRangeWithUtf8(): void
    {
        $str = Str::of('H😀llo W😀rld');
        $replaced = $str->replaceRange(1, 2, Str::of('i'));

        $this->assertStringStartsWith('Hi', $replaced->toString());
        $this->assertSame('Hilo W😀rld', $replaced->toString());
        $this->assertSame($str->chars()->len()->toInt() - 1, $replaced->chars()->len()->toInt());
    }

    public function testClear(): void
    {
        $str = Str::of('Hello');
        $cleared = $str->clear();

        $this->assertSame(
            'Hello',
            $str->toString(),
            'Original string should remain unchanged',
        );
        $this->assertTrue($cleared->isEmpty());
        $this->assertSame('', $cleared->toString());
    }

    public function testClearOnEmptyString(): void
    {
        $str = Str::new();
        $cleared = $str->clear();

        $this->assertTrue($cleared->isEmpty());
    }

    public function testAppend(): void
    {
        $str = Str::of('Hello');
        $appended = $str->append(Str::of(' World'));

        $this->assertSame(
            'Hello',
            $str->toString(),
            'Original string should remain unchanged',
        );
        $this->assertSame('Hello World', $appended->toString());
        $this->assertSame($str->chars()->len()->toInt() + Str::of(' World')->chars()->len()->toInt(), $appended->chars()->len()->toInt());
    }

    public function testAppendWithUtf8(): void
    {
        $str = Str::of('Hello');
        $appended = $str->append(Str::of(' W😄rld'));

        $this->assertSame('Hello W😄rld', $appended->toString());
        $this->assertSame($str->chars()->len()->toInt() + Str::of(' W😄rld')->chars()->len()->toInt(), $appended->chars()->len()->toInt());
    }

    public function testAppendEmptyString(): void
    {
        $str = Str::of('Hello');
        $appended = $str->append(Str::new());

        $this->assertSame('Hello', $appended->toString());
        $this->assertSame($str->chars()->len()->toInt(), $appended->chars()->len()->toInt());
    }

    public function testAppendToEmptyString(): void
    {
        $str = Str::new();
        $appended = $str->append(Str::of('Hello'));

        $this->assertSame('Hello', $appended->toString());
        $this->assertSame(Str::of('Hello')->chars()->len()->toInt(), $appended->chars()->len()->toInt());
    }

    public function testPrepend(): void
    {
        $str = Str::of('World');
        $prepended = $str->prepend(Str::of('Hello '));

        $this->assertSame(
            'World',
            $str->toString(),
            'Original string should remain unchanged',
        );
        $this->assertSame('Hello World', $prepended->toString());
        $this->assertSame($str->chars()->len()->toInt() + Str::of('Hello ')->chars()->len()->toInt(), $prepended->chars()->len()->toInt());
    }

    public function testPrependWithUtf8(): void
    {
        $str = Str::of('World');
        $prepended = $str->prepend(Str::of('H😄llo '));

        $this->assertSame('H😄llo World', $prepended->toString());
        $this->assertSame($str->chars()->len()->toInt() + Str::of('H😄llo ')->chars()->len()->toInt(), $prepended->chars()->len()->toInt());
    }

    public function testPrependEmptyString(): void
    {
        $str = Str::of('World');
        $prepended = $str->prepend(Str::new());

        $this->assertSame('World', $prepended->toString());
        $this->assertSame($str->chars()->len()->toInt(), $prepended->chars()->len()->toInt());
    }

    public function testPrependToEmptyString(): void
    {
        $str = Str::new();
        $prepended = $str->prepend(Str::of('Hello'));

        $this->assertSame('Hello', $prepended->toString());
        $this->assertSame(Str::of('Hello')->chars()->len()->toInt(), $prepended->chars()->len()->toInt());
    }
}
