<?php

use pew\lib\Collection;

class CollectionTest extends PHPUnit\Framework\TestCase
{
    private function getLetterCollection(): Collection
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
        $this->assertCount(4, $c);

        $c = Collection::create([0, 1, 2, 3]);
        $this->assertInstanceOf(Collection::class, $c);
        $this->assertCount(4, $c);

        $c = Collection::fromArray([0, 1, 2, 3]);
        $this->assertInstanceOf(Collection::class, $c);
        $this->assertCount(4, $c);
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

        $this->assertCount(3, $c);
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

        $this->assertCount(10, $c);
        $this->assertEquals([0, 1, 2, 3, 1, 2, 3, 4, 5, 6], $c->toArray());
    }

    public function testChunk()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);
        $chunks = $c->chunk(3);

        $this->assertCount(2, $chunks);
        $this->assertEquals([[0, 1, 2], [3]], $chunks->toArray(true));
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
        ], $c->group("type")->toArray(true));

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
        })->toArray(true));
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
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertEquals(3, $c->last());
        $this->assertEquals([2, 3], $c->last(2)->toArray());
    }

    public function testMap()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertEquals([0, 2, 4, 6], $c->map(function ($n) {
            return $n * 2;
        })->toArray());
    }

    public function testOnly()
    {
        $c = Collection::fromArray([10, 11, 12, 13]);

        $this->assertEquals([0 => 10, 2 => 12], $c->only(0, 2)->toArray());
    }

    public function testPop()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $pop = $c->pop();
        $this->assertEquals(3, $pop);
        $this->assertEquals([0, 1, 2], $c->toArray());
    }

    public function testPrepend()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $c2 = $c->prepend([-1]);
        $this->assertEquals([-1, 0, 1, 2, 3], $c2->toArray());
    }

    public function testPush()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $c2 = $c->push(4);
        $this->assertEquals([0, 1, 2, 3, 4], $c2->toArray());
    }

    public function testRandom()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $random_number = $c->random();
        $this->assertTrue(is_int($random_number));

        $two_random_numbers = $c->random(2);
        $this->assertTrue($two_random_numbers->hasKey(0));
        $this->assertTrue(is_int($two_random_numbers[0]));
        $this->assertTrue($two_random_numbers->hasKey(1));
        $this->assertTrue(is_int($two_random_numbers[1]));

    }

    public function testReduce()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertEquals(6, $c->reduce(function ($acc, $curr) {
            $acc += $curr;
            return $acc;
        }));
    }

    public function testReverse()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertEquals([3, 2, 1, 0], $c->reverse()->toArray());
    }

    public function testShift()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);
        $s = $c->shift();
        $this->assertEquals(0, $s);
        $this->assertEquals([1, 2, 3], $c->toArray());
    }

    public function testShuffle()
    {
        $c = Collection::fromArray([0, 1, 2, 3])->shuffle();

        $this->assertTrue($c->hasValue(0));
        $this->assertTrue($c->hasValue(1));
        $this->assertTrue($c->hasValue(2));
        $this->assertTrue($c->hasValue(3));
    }

    public function testSlice()
    {
        $c = Collection::fromArray([0, 1, 2, 3, 4, 5, 6, 7, 8, 9]);

        $this->assertEquals([0, 1, 2, 3], $c->slice(0, 4)->toArray());
        $this->assertEquals([6, 7, 8, 9], $c->slice(-4, 4)->toArray());
        $this->assertEquals([6 => 6, 7 => 7, 8 => 8, 9 => 9], $c->slice(-4, 4, true)->toArray());
    }

    public function testSplice()
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $insert = $c->splice(2, 0, 4);
        $this->assertEquals([0, 1, 4, 2, 3], $insert->toArray());

        $replace = $c->splice(2, 1, 4);
        $this->assertEquals([0, 1, 4, 3], $replace->toArray());
    }

    public function testSort()
    {
        $c = Collection::fromArray([3, 1, 2, 0]);

        $normalSort = $c->sort();
        $this->assertEquals([0, 1, 2, 3], $normalSort->toArray());

        $letters = Collection::fromArray([
            (object) ["position" => 0, "name" => "alpha", "letter" => "α"],
            (object) ["position" => 1, "name" => "beta", "letter" => "β"],
            (object) ["position" => 2, "name" => "delta", "letter" => "γ"],
        ]);

        $sortedLetters = $letters->sort(function ($a, $b) {
            return $a->position - $b->position;
        });

        $result = $letters->toArray();
        $this->assertEquals($result, $sortedLetters->toArray());

        $moreSortedLetters = $letters->sort("position");
        $this->assertEquals($result, $moreSortedLetters->toArray());
    }

    public function testToArray()
    {
        $numbers = Collection::fromArray([0, 1, 2, 3]);
        $this->assertEquals([0, 1, 2, 3], $numbers->toArray());

        $letters = Collection::fromArray(["a" => "alpha", "b" => "beta"]);
        $this->assertEquals(["a" => "alpha", "b" => "beta"], $letters->toArray());

        $collections = Collection::fromArray([
            "a" => Collection::fromArray([1, "alpha"]),
            "b" => Collection::fromArray([2, "beta"]),
        ]);

        $asArray = $collections->toArray();
        $this->assertTrue(is_array($asArray));
        $this->assertInstanceOf(Collection::class, $asArray["a"]);

        $asArrayRecursive = $collections->toArray(true);
        $this->assertTrue(is_array($asArrayRecursive));
        $this->assertTrue(is_array($asArrayRecursive["a"]));
    }

    public function testToJson()
    {
        $numbers = Collection::fromArray([0, 1, 2, 3]);
        $json = $numbers->toJson();

        $this->assertEquals('[0,1,2,3]', $json);
    }

    public function testUnshift()
    {
        $numbers = Collection::fromArray([0, 1, 2, 3]);
        $numbers->unshift(-1);

        $this->assertEquals([-1, 0, 1, 2, 3], $numbers->toArray());
    }

    public function testUntil()
    {
        $numbers = Collection::fromArray([0, 1, 2, 3]);
        $result = $numbers->until(function ($item) {
            return $item === 2;
        })->toArray();

        $this->assertEquals([0, 1, 2], $result);
    }

    public function testValues()
    {
        $numbers = Collection::fromArray([2 => 0, 5 => 1, 8 => 2, 10 => 3]);

        $result = $numbers->values()->toArray();

        $this->assertEquals([0, 1, 2, 3], $result);
    }

    public function testWalk()
    {
        $numbers = Collection::fromArray([0, 1, 2, 3]);

        $numbers->walk(function (&$item) {
            $item = $item * 2;
        });
        $this->assertEquals([0, 2, 4, 6], $numbers->toArray());
    }

    public function testWithout()
    {
        $c = new Collection([1, 2, 3, 4]);

        $this->assertEquals([0 => 1, 1 => 2, 3 => 4], $c->without(2)->toArray(true));
    }

    public function testZip()
    {
        $c = new Collection([1, 2, 3, 4]);

        $c = $c->zip([5, 6, 7, 8], [9, 10, 11, 12]);

        $this->assertEquals([[1, 5, 9], [2, 6, 10], [3, 7, 11], [4, 8, 12]], $c->toArray(true));
    }

    public function testClone()
    {
        $c = new Collection([1, 2, 3, 4]);
        $clone = clone $c;

        $this->assertEquals($c->toArray(), $clone->toArray());
    }

    public function testFindKeyAndValue()
    {
        $c = new Collection([1, 2, 3, 4]);

        $key = $c->findIndex(function ($item) {
            return $item % 2 === 0;
        });

        $this->assertEquals($key, 1);


        $value = $c->findValue(function ($item) {
            return $item % 2 === 0;
        });

        $this->assertEquals($value, 2);
    }
}
