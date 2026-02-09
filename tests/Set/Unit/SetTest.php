<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Set\Unit;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Set\Set;
use PHPUnit\Framework\TestCase;

class SetTest extends TestCase
{
    public function testCreation(): void
    {
        $set = Set::of(1, 2, 3);
        $this->assertEquals(3, $set->size());
        $this->assertTrue($set->contains(1));
        $this->assertFalse($set->contains(4));
    }

    public function testUniqueness(): void
    {
        $set = Set::of(1, 1, 2);
        $this->assertEquals(2, $set->size());
    }

    public function testAddAndRemove(): void
    {
        $set = Set::of(1);
        $set = $set->add(2);
        $this->assertTrue($set->contains(2));
        $this->assertEquals(2, $set->size());

        $set = $set->remove(1);
        $this->assertFalse($set->contains(1));
        $this->assertEquals(1, $set->size());
    }

    public function testOperations(): void
    {
        $set1 = Set::of(1, 2, 3);
        $set2 = Set::of(3, 4, 5);

        $intersection = $set1->intersection($set2);
        $this->assertEquals(1, $intersection->size());
        $this->assertTrue($intersection->contains(3));

        $difference = $set1->difference($set2); // 1, 2
        $this->assertEquals(2, $difference->size());
        $this->assertTrue($difference->contains(1));
        $this->assertFalse($difference->contains(3));

        $isDisjoint = $set1->isDisjoint(Set::of(4, 5));
        $this->assertTrue($isDisjoint);
    }
}
