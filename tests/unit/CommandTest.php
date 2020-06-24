<?php

use pew\console\command;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Helper\FormatterHelper;

class CommandTest extends \PHPUnit\Framework\TestCase
{
    public $args;

    public function setUp(): void
    {
        $this->args = [
            new ArgvInput(),
            new ConsoleOutput(),
            new FormatterHelper(),
        ];
    }

    public function testCreateCommand()
    {
        $c = new class(...$this->args) extends Command {
            public function run() { return true; }
        };

        $this->assertInstanceOf(Command::class, $c);
        $this->assertTrue($c->run());
    }

    // public function testMessages()
    // {
    //     $c = new class(...$this->args) extends Command {};

    //     ob_start();
    //     $c->message("hello");
    //     $result = ob_get_clean();

    //     $this->assertEquals("hello", trim($result));
    // }

    // public function testSuccessMessage()
    // {
    //     $c = new class(...$this->args) extends Command {};

    //     ob_start();
    //     $c->success("hello");
    //     $result = ob_get_clean();

    //     $this->assertEquals("\033[32mhello\033[39m", trim($result));
    // }

    // public function testInfoMessage()
    // {
    //     $c = new class(...$this->args) extends Command {};

    //     ob_start();
    //     $c->info("hello");
    //     $result = ob_get_clean();

    //     $this->assertEquals("\033[36mhello\033[39m", trim($result));
    // }
}
