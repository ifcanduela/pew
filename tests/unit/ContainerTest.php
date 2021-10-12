<?php

namespace know {
    interface YouKnowWhat {}
    class YouKnowMath implements YouKnowWhat {}
    class YouKnowKarate implements YouKnowWhat {}
}

namespace {
    use pew\di\Container;

    class ContainerTest extends PHPUnit\Framework\TestCase
    {
        public function testBasics()
        {
            $c = new Container();
            $this->assertInstanceOf(Container::class, $c);

            $c->set("a-value", 1234);
            $this->assertEquals(1234, $c->get("a-value"));

            $c->set("a-callback", function () {
                return "abcd";
            });
            $this->assertEquals("abcd", $c->get("a-callback"));

            $this->assertTrue($c->has("a-value"));
            $this->assertTrue($c->has("a-callback"));
            $this->assertFalse($c->has("nothing"));
        }

        public function testImport()
        {
            $c = new Container();

            $c->merge([
                "a-value" => 1234,
                "a-callback" => function ($c) {
                    return "abcd" . $c->get("a-value");
                },
            ]);
            $this->assertEquals(1234, $c->get("a-value"));
            $this->assertEquals("abcd1234", $c->get("a-callback"));
        }

        public function testLoadFile()
        {
            $c = new Container();

            $this->assertFalse($c->loadFile("this/file/does/not.exist"));

            $c->loadFile(__DIR__ . "/../fixtures/config/test.php");

            $this->assertTrue($c->has("env"));
            $this->assertEquals("test", $c->get("env"));
            $this->assertEquals("testValue", $c->get("testKey"));
        }

        public function testLoadBadFile()
        {
            $this->expectException(\RuntimeException::class);
            $c = new Container();

            $bad_file = __DIR__ . "/../fixtures/config/bad-config.php";
            $c->loadFile($bad_file);
        }

        public function testAlias()
        {
            $c = new Container();
            $c["actual_value"] = M_PI;
            $c->alias("pi", "actual_value");

            $c["actual_factory"] = function ($c) {
                return $c["pi"];
            };
            $c->alias("fac", "actual_factory");

            $this->assertEquals(M_PI, $c["fac"]);

            $c[\know\YouKnowMath::class] = function () {
                return new \know\YouKnowMath();
            };

            $c->alias(\know\YouKnowWhat::class, \know\YouKnowMath::class);

            $this->assertInstanceOf(\know\YouKnowMath::class, $c[\know\YouKnowWhat::class]);
        }
    }
}
