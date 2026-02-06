<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Map\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Map\Map;
use PHPUnit\Framework\TestCase;

class MapTest extends TestCase
{
    public function testCreation(): void
    {
        $map = Map::new();
        $this->assertTrue($map->isEmpty());
        $this->assertEquals(0, $map->size()->toInt());

        $map = Map::of('key', 'value');
        $this->assertFalse($map->isEmpty());
        $this->assertEquals(1, $map->size()->toInt());
        $this->assertTrue($map->containsKey('key'));
        $this->assertEquals('value', $map->get('key')->unwrap());
    }

    public function testAddAndGet(): void
    {
        $map = Map::new()
            ->add('a', 1)
            ->add('b', 2);

        $this->assertEquals(2, $map->size()->toInt());
        $this->assertEquals(1, $map->get('a')->unwrap());
        $this->assertEquals(2, $map->get('b')->unwrap());

        // Test overwrite
        $map = $map->add('a', 3);
        $this->assertEquals(2, $map->size()->toInt());
        $this->assertEquals(3, $map->get('a')->unwrap());
    }

    public function testRemove(): void
    {
        $map = Map::new()
            ->add('a', 1)
            ->add('b', 2)
            ->remove('a');

        $this->assertEquals(1, $map->size()->toInt());
        $this->assertFalse($map->containsKey('a'));
        $this->assertTrue($map->containsKey('b'));
    }

    public function testKeysAndValues(): void
    {
        $map = Map::new()
            ->add('a', 1)
            ->add('b', 2);

        $keys = $map->keys();
        $this->assertEquals(2, $keys->size()->toInt());
        $this->assertTrue($keys->contains('a'));
        $this->assertTrue($keys->contains('b'));

        $values = $map->values();
        $this->assertEquals(2, $values->size()->toInt());
        $this->assertTrue($values->contains(1));
        $this->assertTrue($values->contains(2));
    }

    public function testToArray(): void
    {
        $map = Map::new()
            ->add('a', 1);

        $array = $map->toArray();
        $this->assertCount(1, $array);
        $this->assertEquals(['a', 1], $array[0]);
    }

    public function testContainsValue(): void
    {
        $map = Map::new()->add('a', 1);
        $this->assertTrue($map->containsValue(1));
        $this->assertFalse($map->containsValue(2));
    }
}
