<?php

use pew\model\Collection;

class CollectionTest extends PHPUnit\Framework\TestCase
{
    private function getLetterCollection()
    {
        return new Collection([
            ["letter" => "Α", "type"=> "uppercase"],
            ["letter" => "α", "type"=> "lowercase"],
            ["letter" => "Β", "type"=> "uppercase"],
            ["letter" => "β", "type"=> "lowercase"],
            ["letter" => "Γ", "type"=> "uppercase"],
            ["letter" => "γ", "type"=> "lowercase"],
        ]);
    }

    public function testCollectionBasics()
    {
        $c = new Collection([0, 1, 2, 3]);
        $this->assertInstanceOf(Collection::class, $c);
        $this->assertEquals(4, count($c));

        $c = Collection::create([0, 1, 2, 3]);
        $this->assertInstanceOf(Collection::class, $c);
        $this->assertEquals(4, count($c));

        $c = Collection::fromArray([0, 1, 2, 3]);
        $this->assertInstanceOf(Collection::class, $c);
        $this->assertEquals(4, count($c));
    }

    public function testOffsetExists()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertTrue(isset($c[0]));
        $this->assertTrue(isset($c[1]));
        $this->assertTrue(isset($c[2]));
        $this->assertTrue(isset($c[3]));
        $this->assertFalse(isset($c[4]));
        $this->assertFalse(isset($c["test"]));
    }

    public function testOffsetGet()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertEquals(0, $c[0]);
        $this->assertEquals(1, $c[1]);
        $this->assertEquals(2, $c[2]);
        $this->assertEquals(3, $c[3]);
    }

    public function testOffsetSet()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $c[0] = 1;
        $c[1] = 2;
        $c[2] = 3;
        $c[3] = 4;
        $c["test"] = "test";
        $c[] = 5;

        $this->assertEquals(2, $c[1]);
        $this->assertEquals(3, $c[2]);
        $this->assertEquals(4, $c[3]);
        $this->assertEquals(5, $c[4]);
        $this->assertEquals("test", $c["test"]);
    }

    public function testOffsetUnset()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        unset($c[2]);

        $this->assertFalse(isset($c[2]));
    }

    public function testCount()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertEquals(4, $c->count());
        unset($c[2]);

        $this->assertEquals(3, count($c));
    }

    public function testGetIterator()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);
        $it = $c->getIterator();

        $this->assertInstanceOf(\ArrayIterator::class, $it);
    }

    public function testJsonSerialize()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);
        $this->assertEquals("[0,1,2,3]", json_encode($c));

        $c = Collection::fromArray(["alpha" => "a", "beta" => "b"]);
        $this->assertEquals('{"alpha":"a","beta":"b"}', json_encode($c));
    }

    public function testAppend()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $c = $c->append([1, 2, 3], [4], [5], [6]);

        $this->assertEquals(10, count($c));
        $this->assertEquals([0, 1, 2, 3, 1, 2, 3, 4, 5, 6], $c->toArray());
    }

    public function testChunk()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);
        $chunks = $c->chunk(3);

        $this->assertEquals(2, count($chunks));
        $this->assertEquals([[0, 1, 2], [3]], $chunks->toArray());
    }

    public function testField()
    {
        $c = Collection::fromArray([
            ["name" => "Alpha"],
            ["name" => "Beta"],
            ["name" => "Gamma"],
            3
        ]);

        $names = $c->field("name")->toArray();
        $this->assertEquals(["Alpha", "Beta", "Gamma", null], $names);
    }

    public function testFill()
    {
        $c = new Collection();

        $c1 = $c->fill(5, 0);
        $this->assertEquals([0, 0, 0, 0, 0], $c1->toArray());

        $c[] = "hello";
        $c2 = $c->fill(5, function ($i) { return "hello{$i}"; });
        $this->assertEquals(["hello", "hello1", "hello2", "hello3", "hello4", ], $c2->toArray());
    }

    public function testFilter()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);
        $c = $c->filter(function ($item) { return $item % 2; });

        $this->assertEquals([1 => 1, 3 => 3], $c->toArray());
    }

    public function testFirst()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertEquals(0, $c->first());
        $this->assertEquals([0, 1], $c->first(2)->toArray());
    }

    public function testFlatten()
    {
        $c = Collection::fromArray([0, [1, 2], 3]);

        $this->assertEquals([0, 1, 2, 3], $c->flatten()->toArray());
    }

    public function testGroup()
    {
        $c = $this->getLetterCollection();

        $this->assertEquals([
            "uppercase" => [
                ["letter" => "Α", "type"=> "uppercase"],
                ["letter" => "Β", "type"=> "uppercase"],
                ["letter" => "Γ", "type"=> "uppercase"],
            ],
            "lowercase" => [
                ["letter" => "α", "type"=> "lowercase"],
                ["letter" => "β", "type"=> "lowercase"],
                ["letter" => "γ", "type"=> "lowercase"],
            ],
        ], $c->group("type")->toArray());


        $this->assertEquals([
            "u" => [
                ["letter" => "Α", "type"=> "uppercase"],
                ["letter" => "Β", "type"=> "uppercase"],
                ["letter" => "Γ", "type"=> "uppercase"],
            ],
            "l" => [
                ["letter" => "α", "type"=> "lowercase"],
                ["letter" => "β", "type"=> "lowercase"],
                ["letter" => "γ", "type"=> "lowercase"],
            ],
        ], $c->group(function ($item) {
            return $item["type"][0];
        })->toArray());
    }

    public function testHasKey()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertTrue($c->hasKey(0));
        $this->assertFalse($c->hasKey(4));

        $c[] = 4;

        $this->assertTrue($c->hasKey(4));
    }

    public function testHasValue()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertTrue($c->hasValue(0));
        $this->assertFalse($c->hasValue(4));

        $c[] = 4;

        $this->assertTrue($c->hasValue(4));
    }

    public function testImplode()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);
        $this->assertEquals("0, 1, 2, 3", $c->implode(", "));

        $c = $this->getLetterCollection();

        $this->assertEquals("Α|α|Β|β|Γ|γ", $c->implode("|", "letter"));
        $this->assertEquals("Α (u) & α (l) & Β (u) & β (l) & Γ (u) & γ (l)", $c->implode(" & ", function ($i) {
            return "{$i['letter']} ({$i['type'][0]})";
        }));
    }

    public function testIndex()
    {
        $c = $this->getLetterCollection();

        $this->assertEquals([
            "Α" => ["letter" => "Α", "type"=> "uppercase"],
            "Β" => ["letter" => "Β", "type"=> "uppercase"],
            "Γ" => ["letter" => "Γ", "type"=> "uppercase"],
            "α" => ["letter" => "α", "type"=> "lowercase"],
            "β" => ["letter" => "β", "type"=> "lowercase"],
            "γ" => ["letter" => "γ", "type"=> "lowercase"],
        ], $c->index("letter")->toArray());

        $this->assertEquals([
            "_Α" => ["letter" => "Α", "type"=> "uppercase"],
            "_Β" => ["letter" => "Β", "type"=> "uppercase"],
            "_Γ" => ["letter" => "Γ", "type"=> "uppercase"],
            "_α" => ["letter" => "α", "type"=> "lowercase"],
            "_β" => ["letter" => "β", "type"=> "lowercase"],
            "_γ" => ["letter" => "γ", "type"=> "lowercase"],
        ], $c->index(function ($item) {
            return "_" . $item["letter"];
        })->toArray());
    }

    public function testItems()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertEquals([0, 1, 2, 3], $c->items());
    }

    public function testKeys()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertEquals([0, 1, 2, 3], $c->keys()->toArray());
    }

    public function testLast()
    {
        $this->markTestIncomplete("Not yet implemented");
    }

    public function testMap()
    {
        $this->markTestIncomplete("Not yet implemented");
    }

    public function testOnly()
    {
        $this->markTestIncomplete("Not yet implemented");
    }

    public function testPop()
    {
        $this->markTestIncomplete("Not yet implemented");
    }

    public function testPrepend()
    {
        $this->markTestIncomplete("Not yet implemented");
    }

    public function testPush()
    {
        $this->markTestIncomplete("Not yet implemented");
    }

    public function testRandom()
    {
        $this->markTestIncomplete("Not yet implemented");
    }

    public function testReduce()
    {
        $this->markTestIncomplete("Not yet implemented");
    }

    public function testReverse()
    {
        $this->markTestIncomplete("Not yet implemented");
    }

    public function testShift()
    {
        $this->markTestIncomplete("Not yet implemented");
    }

    public function testShuffle()
    {
        $this->markTestIncomplete("Not yet implemented");
    }

    public function testSlice()
    {
        $this->markTestIncomplete("Not yet implemented");
    }

    public function testSplice()
    {
        $this->markTestIncomplete("Not yet implemented");
    }

    public function testSort()
    {
        $this->markTestIncomplete("Not yet implemented");
    }

    public function testToArray()
    {
        $this->markTestIncomplete("Not yet implemented");
    }

    public function testToJson()
    {
        $this->markTestIncomplete("Not yet implemented");
    }

    public function testUnshift()
    {
        $this->markTestIncomplete("Not yet implemented");
    }

    public function testUntil()
    {
        $this->markTestIncomplete("Not yet implemented");
    }

    public function testValues()
    {
        $this->markTestIncomplete("Not yet implemented");
    }

    public function testWalk()
    {
        $this->markTestIncomplete("Not yet implemented");
    }

    public function testWithout()
    {
        $c = new Collection([1, 2, 3, 4]);

        $this->assertEquals([0 => 1, 1 => 2, 3 => 4], $c->without(2)->toArray());
    }

    public function testZip()
    {
        $c = new Collection([1, 2, 3, 4]);

        $c = $c->zip([5, 6, 7, 8], [9, 10, 11, 12]);

        $this->assertEquals([[1, 5, 9], [2, 6, 10], [3, 7, 11], [4, 8, 12]], $c->toArray());
    }

    public function testTestClone()
    {
        $this->markTestIncomplete("Not yet implemented");
    }
}