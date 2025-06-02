<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Str\Unit;

use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use PHPUnit\Framework\TestCase;

final class StrManipulationTest extends TestCase
{
    public function testInsertWithValidIndices(): void
    {
        $str = Str::from('Hello');
        $newStr = $str->insertAt(5, Str::from(' world'));

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
        $str = Str::from('Hello World');
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
        $str = Str::from('Hello World');
        $taken = $str->take(0);

        $this->assertTrue($taken->isEmpty());
    }

    public function testTakeWithNegativeLength(): void
    {
        $str = Str::from('Hello World');
        $taken = $str->take(-5);

        $this->assertTrue($taken->isEmpty());
    }

    public function testTakeWithLengthGreaterThanString(): void
    {
        $str = Str::from('Hello');
        $taken = $str->take(10);

        $this->assertSame('Hello', $taken->toString());
    }

    public function testTakeWithUtf8(): void
    {
        $str = Str::from('H😀llo World');
        $taken = $str->take(3);

        $this->assertSame('H😀l', $taken->toString());
    }

    public function testDrop(): void
    {
        $str = Str::from('Hello World');
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
        $str = Str::from('Hello World');
        $dropped = $str->skip(0);

        $this->assertSame('Hello World', $dropped->toString());
    }

    public function testDropWithNegativeLength(): void
    {
        $str = Str::from('Hello World');
        $dropped = $str->skip(-5);

        $this->assertSame('Hello World', $dropped->toString());
    }

    public function testDropWithLengthGreaterThanString(): void
    {
        $str = Str::from('Hello');
        $dropped = $str->skip(10);

        $this->assertTrue($dropped->isEmpty());
    }

    public function testDropWithUtf8(): void
    {
        $str = Str::from('H😀llo World');
        $dropped = $str->skip(3);

        $this->assertSame('lo World', $dropped->toString());
    }

    public function testInsertWithUtf8(): void
    {
        $str = Str::from('H😄llo');
        $newStr = $str->insertAt(2, Str::from('X'));

        $this->assertEquals(
            'H😄Xllo',
            $newStr->toString(),
            'New string should contain inserted string',
        );

        $this->assertSame($str->chars()->len()->toInt() + \mb_strlen('X'), $newStr->chars()->len()->toInt());
    }

    public function testInsertAtBeginning(): void
    {
        $str = Str::from('World');
        $newStr = $str->insertAt(0, Str::from('Hello '));

        $this->assertSame('Hello World', $newStr->toString());
    }

    public function testInsertAtEnd(): void
    {
        $str = Str::from('Hello');
        $newStr = $str->insertAt(5, Str::from(' World'));

        $this->assertSame('Hello World', $newStr->toString());
    }

    public function testPush(): void
    {
        $str = Str::from('Hello')->append(Str::from(' World'));

        $this->assertSame('Hello World', $str->toString());
    }

    public function testPushWithUtf8(): void
    {
        $str = Str::from('Hello')->append(Str::from(' W😄rld'));

        $this->assertSame('Hello W😄rld', $str->toString());
    }

    public function testRemoveWithValidIndex(): void
    {
        $str = Str::from('Hello');
        $modifiedStr = $str->removeAt(1);

        $this->assertSame('Hllo', $modifiedStr->toString());
    }

    public function testRemoveWithUtf8(): void
    {
        $str = Str::from('Héllo😄');
        $modifiedStr = $str->removeAt(1);

        $this->assertSame('Hllo😄', $modifiedStr->toString());
    }

    public function testRemoveMatches(): void
    {
        $str = Str::from('Hello123World456');
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
        $str = Str::from('Hello123World456😄');
        $newStr = $str->removeMatches("/\d+/");

        $this->assertSame('HelloWorld😄', $newStr->toString());
    }

    public function testRemoveMatchesWithNoMatches(): void
    {
        $str = Str::from('HelloWorld');
        $newStr = $str->removeMatches("/\d+/");

        $this->assertSame('HelloWorld', $newStr->toString());
    }

    public function testRemoveMatchesWithInvalidPattern(): void
    {
        $str = Str::from('HelloWorld');

        $newStr = $str->removeMatches('/zzzz/');

        $this->assertSame('HelloWorld', $newStr->toString());
    }

    public function testTruncate(): void
    {
        $str = Str::from('Hello World');
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
        $str = Str::from('Hello');
        $truncated = $str->truncate(5);

        $this->assertSame('Hello', $truncated->toString());
    }

    public function testTruncateWithGreaterLength(): void
    {
        $str = Str::from('Hello');
        $truncated = $str->truncate(10);

        $this->assertSame('Hello', $truncated->toString());
    }

    public function testTruncateWithZeroLength(): void
    {
        $str = Str::from('Hello');
        $truncated = $str->truncate(0);

        $this->assertSame('', $truncated->toString());
    }

    public function testTruncateWithNegativeLength(): void
    {
        $str = Str::from('Hello');
        $truncated = $str->truncate(-1);

        $this->assertSame('', $truncated->toString());
    }

    public function testReplace(): void
    {
        $str = Str::from('Hello World');
        $replaced = $str->replace(Str::from('World'), Str::from('PHP'));

        $this->assertSame(
            'Hello World',
            $str->toString(),
            'Original string should remain unchanged',
        );
        $this->assertSame('Hello PHP', $replaced->toString());
    }

    public function testReplaceWithMultipleOccurrences(): void
    {
        $str = Str::from('Hello World Hello');
        $replaced = $str->replace(Str::from('Hello'), Str::from('Hi'));

        $this->assertSame('Hi World Hi', $replaced->toString());
    }

    public function testReplaceWithUtf8(): void
    {
        $str = Str::from('H😀llo W😀rld');
        $replaced = $str->replace(Str::from('😀'), Str::from('e'));

        $this->assertSame('Hello Werld', $replaced->toString());
    }

    public function testReplaceWithNonExistentSearch(): void
    {
        $str = Str::from('Hello World');
        $replaced = $str->replace(Str::from('Goodbye'), Str::from('Hi'));

        $this->assertSame('Hello World', $replaced->toString());
    }

    public function testReplaceRange(): void
    {
        $str = Str::from('Hello World');
        $replaced = $str->replaceRange(6, 5, Str::from('PHP'));

        $this->assertSame(
            'Hello World',
            $str->toString(),
            'Original string should remain unchanged',
        );
        $this->assertSame('Hello PHP', $replaced->toString());
    }

    public function testReplaceRangeWithUtf8(): void
    {
        $str = Str::from('H😀llo W😀rld');
        $replaced = $str->replaceRange(1, 2, Str::from('i'));

        $this->assertStringStartsWith('Hi', $replaced->toString());
        $this->assertSame('Hilo W😀rld', $replaced->toString());
        $this->assertSame($str->chars()->len()->toInt() - 1, $replaced->chars()->len()->toInt());
    }

    public function testClear(): void
    {
        $str = Str::from('Hello');
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
        $str = Str::from('Hello');
        $appended = $str->append(Str::from(' World'));

        $this->assertSame(
            'Hello',
            $str->toString(),
            'Original string should remain unchanged',
        );
        $this->assertSame('Hello World', $appended->toString());
        $this->assertSame($str->chars()->len()->toInt() + Str::from(' World')->chars()->len()->toInt(), $appended->chars()->len()->toInt());
    }

    public function testAppendWithUtf8(): void
    {
        $str = Str::from('Hello');
        $appended = $str->append(Str::from(' W😄rld'));

        $this->assertSame('Hello W😄rld', $appended->toString());
        $this->assertSame($str->chars()->len()->toInt() + Str::from(' W😄rld')->chars()->len()->toInt(), $appended->chars()->len()->toInt());
    }

    public function testAppendEmptyString(): void
    {
        $str = Str::from('Hello');
        $appended = $str->append(Str::new());

        $this->assertSame('Hello', $appended->toString());
        $this->assertSame($str->chars()->len()->toInt(), $appended->chars()->len()->toInt());
    }

    public function testAppendToEmptyString(): void
    {
        $str = Str::new();
        $appended = $str->append(Str::from('Hello'));

        $this->assertSame('Hello', $appended->toString());
        $this->assertSame(Str::from('Hello')->chars()->len()->toInt(), $appended->chars()->len()->toInt());
    }

    public function testPrepend(): void
    {
        $str = Str::from('World');
        $prepended = $str->prepend(Str::from('Hello '));

        $this->assertSame(
            'World',
            $str->toString(),
            'Original string should remain unchanged',
        );
        $this->assertSame('Hello World', $prepended->toString());
        $this->assertSame($str->chars()->len()->toInt() + Str::from('Hello ')->chars()->len()->toInt(), $prepended->chars()->len()->toInt());
    }

    public function testPrependWithUtf8(): void
    {
        $str = Str::from('World');
        $prepended = $str->prepend(Str::from('H😄llo '));

        $this->assertSame('H😄llo World', $prepended->toString());
        $this->assertSame($str->chars()->len()->toInt() + Str::from('H😄llo ')->chars()->len()->toInt(), $prepended->chars()->len()->toInt());
    }

    public function testPrependEmptyString(): void
    {
        $str = Str::from('World');
        $prepended = $str->prepend(Str::new());

        $this->assertSame('World', $prepended->toString());
        $this->assertSame($str->chars()->len()->toInt(), $prepended->chars()->len()->toInt());
    }

    public function testPrependToEmptyString(): void
    {
        $str = Str::new();
        $prepended = $str->prepend(Str::from('Hello'));

        $this->assertSame('Hello', $prepended->toString());
        $this->assertSame(Str::from('Hello')->chars()->len()->toInt(), $prepended->chars()->len()->toInt());
    }
}
