<?php

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

        $c->import([
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

    /**
     * @expectedException \RuntimeException
     */
    public function testLoadBadFile()
    {
        $c = new Container();

        $bad_file = __DIR__ . "/../fixtures/config/bad-config.php";
        $c->loadFile($bad_file);
        // $this->assertInstanceOf(\RuntimeException::class, $e);
        // $this->assertEquals("Definitions file ${bad_file} must return an array", $e->getMessage());
    }
}
