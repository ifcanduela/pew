<?php

use pew\console\command;

class CommandTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateCommand()
    {
        $c = new class extends Command {
            public function name()
            {
                return "example";
            }

            public function description()
            {
                return "Example command";
            }

            public function run()
            {
                return true;
            }
        };

        $this->assertInstanceOf(Command::class, $c);
        $this->assertTrue($c->run());
    }

    public function testMessages()
    {
        $c = new class extends Command {
            public function name() {}
            public function description() {}
            public function run() {}
        };

        $this->assertEquals("hello", trim($c->message("hello")));
    }

    public function testSuccessMessage()
    {
        $c = new class extends Command {
            public function name() {}
            public function description() {}
            public function run() {}
        };

        $this->assertEquals("\033[32mhello\033[39m", trim($c->success("hello")));
    }

    public function testInfoMessage()
    {
        $c = new class extends Command {
            public function name() {}
            public function description() {}
            public function run() {}
        };

        $this->assertEquals("\033[36mhello\033[39m", trim($c->info("hello")));
    }
}
