<?php

declare(strict_types=1);

use pew\console\CommandArguments;

class CommandArgumentsTest extends PHPUnit\Framework\TestCase
{
    public function testCommandArgumentsBasics(): void
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

    public function testNamedArguments(): void
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

    public function testShortArguments(): void
    {
        $ca = new CommandArguments(["-m", "-n", "alpha"]);

        $this->assertTrue($ca->has("m"));
        $this->assertTrue($ca->has("n"));
        $this->assertTrue($ca->get("n"));
        $this->assertEquals("alpha", $ca->get(0));

        $ca = new CommandArguments(["-m=alpha", "-n=beta"]);

        $this->assertTrue($ca->has("m"));
        $this->assertTrue($ca->has("n"));
        $this->assertEquals("alpha", $ca->get("m"));
        $this->assertEquals("beta", $ca->get("n"));
    }

    public function testFormatting(): void
    {
        $ca = new CommandArguments(["--mode=test"]);

        $this->assertTrue($ca->has("mode"));
        $this->assertEquals("test", $ca->mode);

        $ca = new CommandArguments(["loose-arg"]);

        $this->assertTrue($ca->has(0));
        $this->assertEquals("loose-arg", $ca->at(0));
        $this->assertEquals("loose-arg", $ca->get(0));
    }

    public function testGetDefaultValue(): void
    {
        $ca = new CommandArguments(["--alpha", "beta"]);

        $this->assertTrue($ca->has("alpha"));
        $this->assertFalse($ca->has("beta"));
        $this->assertEquals("beta", $ca->get("alpha"));
        $this->assertEquals("gamma", $ca->get("delta", "gamma"));
    }
}
