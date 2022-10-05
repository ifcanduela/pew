<?php

declare(strict_types=1);

use pew\console\command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\Output;

class TestOutput extends Output
{
    public function write($messages, bool $newline = false, int $options = 0): void
    {
        if (!is_array($messages)) {
            $messages = [$messages];
        }

        foreach ($messages as $message) {
            $this->doWrite($message, false);
        }
    }

    public function writeln($messages, int $options = 0): void
    {
        foreach ($messages as $message) {
            $this->doWrite($message, true);
        }
    }

    protected function doWrite(string $message, bool $newline): void
    {
        echo $message . ($newline ? PHP_EOL : "");
    }
}

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

    public function testCreateCommand(): void
    {
        $c = new class (...$this->args) extends Command {
            public function run()
            {
                return true;
            }
        };

        $this->assertInstanceOf(Command::class, $c);
        $this->assertTrue($c->run());
    }

    public function testMessages(): void
    {
        $input = new ArgvInput();
        $output = new TestOutput();

        $c = new class ($input, $output) extends Command {};

        ob_start();
        $c->message("hello");
        $result = ob_get_clean();

        $this->assertEquals("hello", trim($result));
    }

    public function testSuccessMessage(): void
    {
        $c = new class (...$this->args) extends Command {
        };
        $c->output = new TestOutput();

        ob_start();
        $c->success("hello");
        $result = ob_get_clean();

        $this->assertEquals("<success>hello</>", trim($result));
    }

    public function testInfoMessage(): void
    {
        $c = new class (...$this->args) extends Command {
        };
        $c->output = new TestOutput();

        ob_start();
        $c->info("hello");
        $result = ob_get_clean();

        $this->assertEquals("<info>hello</>", trim($result));
    }

    public function testWarningMessage(): void
    {
        $c = new class (...$this->args) extends Command {
        };
        $c->output = new TestOutput();

        ob_start();
        $c->warning("hello");
        $result = ob_get_clean();

        $this->assertEquals("<warn>hello</>", trim($result));
    }

    public function testErrorMessage(): void
    {
        $c = new class (...$this->args) extends Command {
        };
        $c->output = new TestOutput();

        ob_start();
        $c->error("hello");
        $result = ob_get_clean();

        $this->assertEquals("<error>hello</>", trim($result));
    }

    public function testLogMessage(): void
    {
        $c = new class (...$this->args) extends Command {
        };
        $c->output = new TestOutput();

        ob_start();
        $c->log("hello");
        $result = ob_get_clean();

        $this->assertEquals("<comment>hello</>", trim($result));
    }
}
