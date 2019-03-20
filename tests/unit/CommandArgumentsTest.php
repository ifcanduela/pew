<?php

use pew\console\CommandArguments;

class CommandArgumentsTest extends PHPUnit\Framework\TestCase
{
    public function testCommandArgumentsBasics()
    {
        $ca = new CommandArguments([1, 2, 3, "--no-log", "--debug"]);

        $this->assertTrue($ca->has(0));
        $this->assertTrue($ca->has(2));
        $this->assertFalse($ca->has(3));

        $this->assertEquals(1, $ca->at(0));
        $this->assertEquals(2, $ca->at(1));
        $this->assertEquals(3, $ca->at(2));
        $this->assertNull($ca->at(3));

        $this->assertEquals(false, $ca->get("log"));
        $this->assertEquals(true, $ca->get("debug"));
    }

    public function testNamedArguments()
    {
        $ca = new CommandArguments(["--mode", "test", "--no-print"]);

        $this->assertTrue($ca->has("mode"));
        $this->assertTrue($ca->has("print"));
        $this->assertFalse($ca->has("test"));
        $this->assertFalse($ca->has("no-print"));
        $this->assertFalse($ca->has("noPrint"));

        $this->assertEquals("test", $ca->mode);
        $this->assertEquals(false, $ca->print);
    }
}
