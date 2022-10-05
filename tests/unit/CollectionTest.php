<?php

declare(strict_types=1);

use pew\lib\Collection;

class CollectionTest extends PHPUnit\Framework\TestCase
{
    private function getLetterCollection(): Collection
    {
        return new Collection([
            ["letter" => "Α", "type" => "uppercase"],
            ["letter" => "α", "type" => "lowercase"],
            ["letter" => "Β", "type" => "uppercase"],
            ["letter" => "β", "type" => "lowercase"],
            ["letter" => "Γ", "type" => "uppercase"],
            ["letter" => "γ", "type" => "lowercase"],
        ]);
    }

    public function testCollectionBasics(): void
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

    public function testOffsetExists(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertTrue(isset($c[0]));
        $this->assertTrue(isset($c[1]));
        $this->assertTrue(isset($c[2]));
        $this->assertTrue(isset($c[3]));
        $this->assertFalse(isset($c[4]));
        $this->assertFalse(isset($c["test"]));
    }

    public function testOffsetGet(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertEquals(0, $c[0]);
        $this->assertEquals(1, $c[1]);
        $this->assertEquals(2, $c[2]);
        $this->assertEquals(3, $c[3]);
    }

    public function testOffsetSet(): void
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

    public function testOffsetUnset(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        unset($c[2]);

        $this->assertFalse(isset($c[2]));
    }

    public function testCount(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertEquals(4, $c->count());
        unset($c[2]);

        $this->assertCount(3, $c);
    }

    public function testGetIterator(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);
        $it = $c->getIterator();

        $this->assertInstanceOf(\ArrayIterator::class, $it);
    }

    public function testJsonSerialize(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);
        $this->assertEquals("[0,1,2,3]", json_encode($c));

        $c = Collection::fromArray(["alpha" => "a", "beta" => "b"]);
        $this->assertEquals('{"alpha":"a","beta":"b"}', json_encode($c));
    }

    public function testAppend(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $c = $c->append([1, 2, 3], [4], [5], [6]);

        $this->assertCount(10, $c);
        $this->assertEquals([0, 1, 2, 3, 1, 2, 3, 4, 5, 6], $c->toArray());
    }

    public function testChunk(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);
        $chunks = $c->chunk(3);

        $this->assertCount(2, $chunks);
        $this->assertEquals([[0, 1, 2], [3]], $chunks->toArray(true));
    }

    public function testField(): void
    {
        $c = Collection::fromArray([
            ["name" => "Alpha"],
            ["name" => "Beta"],
            ["name" => "Gamma"],
            3,
        ]);

        $names = $c->field("name")->toArray();
        $this->assertEquals(["Alpha", "Beta", "Gamma", null], $names);
    }

    public function testFill(): void
    {
        $c = new Collection();

        $c1 = $c->fill(5, 0);
        $this->assertEquals([0, 0, 0, 0, 0], $c1->toArray());

        $c[] = "hello";
        $c2 = $c->fill(5, fn ($i) => "hello{$i}");
        $this->assertEquals(["hello", "hello1", "hello2", "hello3", "hello4", ], $c2->toArray());
    }

    public function testFilter(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);
        $c = $c->filter(fn ($item) => $item % 2);

        $this->assertEquals([1 => 1, 3 => 3], $c->toArray());
    }

    public function testFirst(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertEquals(0, $c->first());
        $this->assertEquals([0, 1], $c->first(2)->toArray());
    }

    public function testFlatten(): void
    {
        $c = Collection::fromArray([0, [1, 2], 3]);

        $this->assertEquals([0, 1, 2, 3], $c->flatten()->toArray());
    }

    public function testGroup(): void
    {
        $c = $this->getLetterCollection();
        $callback = fn ($item) => $item["type"][0];

        $expected = [
            "uppercase" => [
                ["letter" => "Α", "type" => "uppercase"],
                ["letter" => "Β", "type" => "uppercase"],
                ["letter" => "Γ", "type" => "uppercase"],
            ],
            "lowercase" => [
                ["letter" => "α", "type" => "lowercase"],
                ["letter" => "β", "type" => "lowercase"],
                ["letter" => "γ", "type" => "lowercase"],
            ],
        ];
        $this->assertEquals($expected, $c->group("type")->toArray(true));

        $expected = [
            "u" => [
                ["letter" => "Α", "type" => "uppercase"],
                ["letter" => "Β", "type" => "uppercase"],
                ["letter" => "Γ", "type" => "uppercase"],
            ],
            "l" => [
                ["letter" => "α", "type" => "lowercase"],
                ["letter" => "β", "type" => "lowercase"],
                ["letter" => "γ", "type" => "lowercase"],
            ],
        ];
        $this->assertEquals($expected, $c->group($callback)->toArray(true));

        $data = new Collection([
            "alpha" => [100, "Alpha"],
            "beta" => [200, "Beta"],
            "gamma" => [100, "Gamma"],
            "delta" => [200, "Delta"],
        ]);
        $expected = [
            100 => [
                "alpha" => [100, "Alpha"],
                "gamma" => [100, "Gamma"],
            ],
            200 => [
                "beta" => [200, "Beta"],
                "delta" => [200, "Delta"],
            ],
        ];
        $callback = fn ($item, $index) => $item[0];
        $this->assertEquals($expected, $data->group($callback)->toArray(true));
    }

    public function testHasKey(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertTrue($c->hasKey(0));
        $this->assertFalse($c->hasKey(4));

        $c[] = 4;

        $this->assertTrue($c->hasKey(4));
    }

    public function testHasValue(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertTrue($c->hasValue(0));
        $this->assertFalse($c->hasValue(4));

        $c[] = 4;

        $this->assertTrue($c->hasValue(4));
    }

    public function testImplode(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);
        $this->assertEquals("0, 1, 2, 3", $c->implode(", "));

        $c = $this->getLetterCollection();

        $this->assertEquals("Α|α|Β|β|Γ|γ", $c->implode("|", "letter"));
        $this->assertEquals("Α (u) & α (l) & Β (u) & β (l) & Γ (u) & γ (l)", $c->implode(" & ", fn ($i) => "{$i['letter']} ({$i['type'][0]})"));
    }

    public function testIndex(): void
    {
        $c = $this->getLetterCollection();

        $this->assertEquals([
            "Α" => ["letter" => "Α", "type" => "uppercase"],
            "Β" => ["letter" => "Β", "type" => "uppercase"],
            "Γ" => ["letter" => "Γ", "type" => "uppercase"],
            "α" => ["letter" => "α", "type" => "lowercase"],
            "β" => ["letter" => "β", "type" => "lowercase"],
            "γ" => ["letter" => "γ", "type" => "lowercase"],
        ], $c->index("letter")->toArray());

        $this->assertEquals([
            "_Α" => ["letter" => "Α", "type" => "uppercase"],
            "_Β" => ["letter" => "Β", "type" => "uppercase"],
            "_Γ" => ["letter" => "Γ", "type" => "uppercase"],
            "_α" => ["letter" => "α", "type" => "lowercase"],
            "_β" => ["letter" => "β", "type" => "lowercase"],
            "_γ" => ["letter" => "γ", "type" => "lowercase"],
        ], $c->index(fn ($item) => "_" . $item["letter"])->toArray());
    }

    public function testItems(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertEquals([0, 1, 2, 3], $c->items());
    }

    public function testKeys(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertEquals([0, 1, 2, 3], $c->keys()->toArray());
    }

    public function testLast(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertEquals(3, $c->last());
        $this->assertEquals([2, 3], $c->last(2)->toArray());
    }

    public function testMap(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertEquals([0, 2, 4, 6], $c->map(fn ($n) => $n * 2)->toArray());
    }

    public function testOnly(): void
    {
        $c = Collection::fromArray([10, 11, 12, 13]);

        $this->assertEquals([0 => 10, 2 => 12], $c->only(0, 2)->toArray());
    }

    public function testPop(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $pop = $c->pop();
        $this->assertEquals(3, $pop);
        $this->assertEquals([0, 1, 2], $c->toArray());
    }

    public function testPrepend(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $c2 = $c->prepend([-1]);
        $this->assertEquals([-1, 0, 1, 2, 3], $c2->toArray());
    }

    public function testPush(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $c2 = $c->push(4);
        $this->assertEquals([0, 1, 2, 3, 4], $c2->toArray());
    }

    public function testRandom(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $random_number = $c->random();
        $this->assertTrue(is_int($random_number));

        $two_random_numbers = $c->random(2);
        $this->assertTrue($two_random_numbers->hasKey(0));
        $this->assertTrue(is_int($two_random_numbers[0]));
        $this->assertTrue($two_random_numbers->hasKey(1));
        $this->assertTrue(is_int($two_random_numbers[1]));

        $c = Collection::fromArray([]);

        $this->assertNull($c->random());
        $this->assertNull($c->random(10));
    }

    public function testReduce(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertEquals(6, $c->reduce(function ($acc, $curr) {
            $acc += $curr;

            return $acc;
        }));
    }

    public function testReverse(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $this->assertEquals([3, 2, 1, 0], $c->reverse()->toArray());
    }

    public function testShift(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);
        $s = $c->shift();
        $this->assertEquals(0, $s);
        $this->assertEquals([1, 2, 3], $c->toArray());
    }

    public function testShuffle(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3])->shuffle();

        $this->assertTrue($c->hasValue(0));
        $this->assertTrue($c->hasValue(1));
        $this->assertTrue($c->hasValue(2));
        $this->assertTrue($c->hasValue(3));
    }

    public function testSlice(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3, 4, 5, 6, 7, 8, 9]);

        $this->assertEquals([0, 1, 2, 3], $c->slice(0, 4)->toArray());
        $this->assertEquals([6, 7, 8, 9], $c->slice(-4, 4)->toArray());
        $this->assertEquals([6 => 6, 7 => 7, 8 => 8, 9 => 9], $c->slice(-4, 4, true)->toArray());
    }

    public function testSplice(): void
    {
        $c = Collection::fromArray([0, 1, 2, 3]);

        $insert = $c->splice(2, 0, 4);
        $this->assertEquals([0, 1, 4, 2, 3], $insert->toArray());

        $replace = $c->splice(2, 1, 4);
        $this->assertEquals([0, 1, 4, 3], $replace->toArray());
    }

    public function testSort(): void
    {
        $c = Collection::fromArray([3, 1, 2, 0]);

        $normalSort = $c->sort();
        $this->assertEquals([0, 1, 2, 3], $normalSort->toArray());

        $letters = Collection::fromArray([
            (object) ["position" => 0, "name" => "alpha", "letter" => "α"],
            (object) ["position" => 1, "name" => "beta", "letter" => "β"],
            (object) ["position" => 2, "name" => "delta", "letter" => "γ"],
        ]);

        $sortedLetters = $letters->sort(fn ($a, $b) => $a->position - $b->position);

        $result = $letters->toArray();
        $this->assertEquals($result, $sortedLetters->toArray());

        $moreSortedLetters = $letters->sort("position");
        $this->assertEquals($result, $moreSortedLetters->toArray());
    }

    public function testToArray(): void
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

    public function testToJson(): void
    {
        $numbers = Collection::fromArray([0, 1, 2, 3]);
        $json = $numbers->toJson();

        $this->assertEquals('[0,1,2,3]', $json);

        try {
            $f = fopen("nofile", "w");
            Collection::fromArray([$f])->toJson();
        } catch (\RuntimeException $e) {
            $this->assertEquals("JSON encoding error: Type is not supported", $e->getMessage());
            unlink("nofile");
        }
    }

    public function testUnshift(): void
    {
        $numbers = Collection::fromArray([0, 1, 2, 3]);
        $numbers->unshift(-1);

        $this->assertEquals([-1, 0, 1, 2, 3], $numbers->toArray());
    }

    public function testUntil(): void
    {
        $numbers = Collection::fromArray([0, 1, 2, 3]);
        $result = $numbers->until(fn ($item) => $item === 2)->toArray();

        $this->assertEquals([0, 1, 2], $result);
    }

    public function testValues(): void
    {
        $numbers = Collection::fromArray([2 => 0, 5 => 1, 8 => 2, 10 => 3]);

        $result = $numbers->values()->toArray();

        $this->assertEquals([0, 1, 2, 3], $result);
    }

    public function testWalk(): void
    {
        $numbers = Collection::fromArray([0, 1, 2, 3]);

        $numbers->walk(function (&$item): void {
            $item = $item * 2;
        });
        $this->assertEquals([0, 2, 4, 6], $numbers->toArray());
    }

    public function testWithout(): void
    {
        $c = new Collection([1, 2, 3, 4]);

        $this->assertEquals([0 => 1, 1 => 2, 3 => 4], $c->without(2)->toArray(true));
    }

    public function testZip(): void
    {
        $c = new Collection([1, 2, 3, 4]);

        $c = $c->zip([5, 6, 7, 8], [9, 10, 11, 12]);

        $this->assertEquals([[1, 5, 9], [2, 6, 10], [3, 7, 11], [4, 8, 12]], $c->toArray(true));
    }

    public function testClone(): void
    {
        $c = new Collection([1, 2, 3, 4]);
        $clone = clone $c;

        $this->assertEquals($c->toArray(), $clone->toArray());
    }

    public function testFindKeyAndValue(): void
    {
        $c = new Collection([1, 2, 3, 4]);

        $key = $c->findIndex(fn ($item) => $item % 2 === 0);

        $this->assertEquals(1, $key);

        $value = $c->findKey(fn ($item) => $item > 20);

        $this->assertNull($value);

        $value = $c->findValue(fn ($item) => $item % 2 === 0);

        $this->assertEquals(2, $value);

        $value = $c->findValue(fn ($item) => $item > 20);

        $this->assertNull($value);
    }
}
