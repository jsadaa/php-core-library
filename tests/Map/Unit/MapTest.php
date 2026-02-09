<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Map\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Map\Map;
use Jsadaa\PhpCoreLibrary\Modules\Collections\Pair;
use PHPUnit\Framework\TestCase;

class MapTest extends TestCase
{
    public function testCreation(): void
    {
        $map = Map::new();
        $this->assertTrue($map->isEmpty());
        $this->assertEquals(0, $map->size());

        $map = Map::of('key', 'value');
        $this->assertFalse($map->isEmpty());
        $this->assertEquals(1, $map->size());
        $this->assertTrue($map->containsKey('key'));
        $this->assertEquals('value', $map->get('key')->unwrap());
    }

    public function testAddAndGet(): void
    {
        $map = Map::new()
            ->add('a', 1)
            ->add('b', 2);

        $this->assertEquals(2, $map->size());
        $this->assertEquals(1, $map->get('a')->unwrap());
        $this->assertEquals(2, $map->get('b')->unwrap());

        // Test overwrite
        $map = $map->add('a', 3);
        $this->assertEquals(2, $map->size());
        $this->assertEquals(3, $map->get('a')->unwrap());
    }

    public function testRemove(): void
    {
        $map = Map::new()
            ->add('a', 1)
            ->add('b', 2)
            ->remove('a');

        $this->assertEquals(1, $map->size());
        $this->assertFalse($map->containsKey('a'));
        $this->assertTrue($map->containsKey('b'));
    }

    public function testKeysAndValues(): void
    {
        $map = Map::new()
            ->add('a', 1)
            ->add('b', 2);

        $keys = $map->keys();
        $this->assertEquals(2, $keys->size());
        $this->assertTrue($keys->contains('a'));
        $this->assertTrue($keys->contains('b'));

        $values = $map->values();
        $this->assertEquals(2, $values->size());
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

    // -- Object keys --

    public function testObjectKeyOf(): void
    {
        $obj = new \stdClass();
        $map = Map::of($obj, 10);

        $this->assertEquals(1, $map->size());
        $this->assertTrue($map->containsKey($obj));
        $this->assertEquals(10, $map->get($obj)->unwrap());
    }

    public function testObjectKeyIdentityComparison(): void
    {
        $a = new \stdClass();
        $a->name = 'Alice';

        $b = new \stdClass();
        $b->name = 'Alice';

        $map = Map::of($a, 1);

        $this->assertTrue($map->containsKey($a));
        $this->assertFalse($map->containsKey($b));
        $this->assertTrue($map->get($b)->isNone());
    }

    public function testObjectKeyAddAndOverwrite(): void
    {
        $obj = new \stdClass();
        $map = Map::of($obj, 1)->add($obj, 2);

        $this->assertEquals(1, $map->size());
        $this->assertEquals(2, $map->get($obj)->unwrap());
    }

    public function testObjectKeyRemove(): void
    {
        $a = new \stdClass();
        $b = new \stdClass();
        $map = Map::of($a, 1)->add($b, 2)->remove($a);

        $this->assertEquals(1, $map->size());
        $this->assertFalse($map->containsKey($a));
        $this->assertTrue($map->containsKey($b));
    }

    public function testObjectKeyRemoveNonExistent(): void
    {
        $a = new \stdClass();
        $b = new \stdClass();
        $map = Map::of($a, 1);

        $result = $map->remove($b);
        $this->assertSame($map, $result);
        $this->assertEquals(1, $result->size());
    }

    public function testObjectKeyContainsValue(): void
    {
        $obj = new \stdClass();
        $map = Map::of($obj, 42);

        $this->assertTrue($map->containsValue(42));
        $this->assertFalse($map->containsValue(99));
    }

    public function testObjectKeyKeysAndValues(): void
    {
        $a = new \stdClass();
        $b = new \stdClass();
        $map = Map::of($a, 10)->add($b, 20);

        $values = $map->values();
        $this->assertEquals(2, $values->size());
        $this->assertTrue($values->contains(10));
        $this->assertTrue($values->contains(20));
    }

    public function testObjectKeyToArray(): void
    {
        $obj = new \stdClass();
        $map = Map::of($obj, 5);

        $array = $map->toArray();
        $this->assertCount(1, $array);
        $this->assertSame($obj, $array[0][0]);
        $this->assertEquals(5, $array[0][1]);
    }

    public function testObjectKeyFilter(): void
    {
        $a = new \stdClass();
        $a->role = 'admin';
        $b = new \stdClass();
        $b->role = 'guest';

        $map = Map::of($a, 100)->add($b, 10);
        $filtered = $map->filter(static fn($key, $value) => $value >= 50);

        $this->assertEquals(1, $filtered->size());
        $this->assertTrue($filtered->containsKey($a));
        $this->assertFalse($filtered->containsKey($b));
    }

    public function testObjectKeyMap(): void
    {
        $obj = new \stdClass();
        $map = Map::of($obj, 5);
        $doubled = $map->map(static fn($key, $value) => $value * 2);

        $this->assertEquals(10, $doubled->get($obj)->unwrap());
    }

    public function testObjectKeyFold(): void
    {
        $a = new \stdClass();
        $b = new \stdClass();
        $map = Map::of($a, 10)->add($b, 20);

        $sum = $map->fold(static fn(int $carry, $key, int $value) => $carry + $value, 0);
        $this->assertEquals(30, $sum);
    }

    public function testObjectKeyForEach(): void
    {
        $a = new \stdClass();
        $b = new \stdClass();
        $map = Map::of($a, 3)->add($b, 7);

        $total = 0;
        $map->forEach(static function($key, $value) use (&$total): void {
            $total += $value;
        });

        $this->assertEquals(10, $total);
    }

    public function testObjectKeyAppend(): void
    {
        $a = new \stdClass();
        $b = new \stdClass();
        $c = new \stdClass();

        $map1 = Map::of($a, 1)->add($b, 2);
        $map2 = Map::of($b, 20)->add($c, 3);
        $merged = $map1->append($map2);

        $this->assertEquals(3, $merged->size());
        $this->assertEquals(1, $merged->get($a)->unwrap());
        $this->assertEquals(20, $merged->get($b)->unwrap());
        $this->assertEquals(3, $merged->get($c)->unwrap());
    }

    public function testObjectKeyFind(): void
    {
        $a = new \stdClass();
        $b = new \stdClass();
        $map = Map::of($a, 5)->add($b, 15);

        $found = $map->find(static fn($key, $value) => $value > 10);
        $this->assertTrue($found->isSome());
        $pair = $found->unwrap();
        $this->assertInstanceOf(Pair::class, $pair);
        $this->assertSame($b, $pair->first());
        $this->assertEquals(15, $pair->second());

        $notFound = $map->find(static fn($key, $value) => $value > 100);
        $this->assertTrue($notFound->isNone());
    }

    public function testObjectKeyEq(): void
    {
        $a = new \stdClass();
        $b = new \stdClass();

        $map1 = Map::of($a, 1)->add($b, 2);
        $map2 = Map::of($a, 1)->add($b, 2);
        $map3 = Map::of($a, 1)->add($b, 99);

        $this->assertTrue($map1->eq($map2));
        $this->assertFalse($map1->eq($map3));
    }

    public function testObjectKeyFromKeys(): void
    {
        $a = new \stdClass();
        $b = new \stdClass();
        $map = Map::fromKeys([$a, $b], 0);

        $this->assertEquals(2, $map->size());
        $this->assertEquals(0, $map->get($a)->unwrap());
        $this->assertEquals(0, $map->get($b)->unwrap());
    }

    public function testMixedScalarAndObjectKeys(): void
    {
        $obj = new \stdClass();
        $map = Map::of('scalar', 1)->add($obj, 2);

        $this->assertEquals(2, $map->size());
        $this->assertEquals(1, $map->get('scalar')->unwrap());
        $this->assertEquals(2, $map->get($obj)->unwrap());
    }

    public function testObjectKeyClear(): void
    {
        $obj = new \stdClass();
        $map = Map::of($obj, 1)->clear();

        $this->assertTrue($map->isEmpty());
        $this->assertFalse($map->containsKey($obj));
    }

    public function testObjectKeyFlatMap(): void
    {
        $a = new \stdClass();
        $b = new \stdClass();
        $map = Map::of($a, 10)->add($b, 20);

        $result = $map->flatMap(static fn($key, $value) => Map::of($key, $value * 2));

        $this->assertEquals(2, $result->size());
        $this->assertEquals(20, $result->get($a)->unwrap());
        $this->assertEquals(40, $result->get($b)->unwrap());
    }
}
