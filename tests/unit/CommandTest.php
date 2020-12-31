<?php

use pew\console\command;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class TestOutput implements OutputInterface
{
    public function write($messages, bool $newline = false, int $options = 0)
    {
        if (!is_array($messages)) {
            $messages = [$messages];
        }

        foreach ($messages as $message) {
            echo $message;
        }
    }

    public function writeln($messages, int $options = 0)
    {
        foreach ($messages as $message) {
            echo $message . PHP_EOL;
        }
    }

    public function setVerbosity(int $level) {}
    public function getVerbosity() {}
    public function isQuiet() {}
    public function isVerbose() {}
    public function isVeryVerbose() {}
    public function isDebug() {}
    public function setDecorated(bool $decorated) {}
    public function isDecorated() {}
    public function setFormatter(OutputFormatterInterface $formatter) {}
    public function getFormatter() {}
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

    public function testCreateCommand()
    {
        $c = new class(...$this->args) extends Command {
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
        $c = new class(...$this->args) extends Command {};
        $c->output = new TestOutput();

        ob_start();
        $c->message("hello");
        $result = ob_get_clean();

        $this->assertEquals("hello", trim($result));
    }

    public function testSuccessMessage()
    {
        $c = new class(...$this->args) extends Command {};
        $c->output = new TestOutput();

        ob_start();
        $c->success("hello");
        $result = ob_get_clean();

        $this->assertEquals("<success>hello</>", trim($result));
    }

    public function testInfoMessage()
    {
        $c = new class(...$this->args) extends Command {};
        $c->output = new TestOutput();

        ob_start();
        $c->info("hello");
        $result = ob_get_clean();

        $this->assertEquals("<info>hello</>", trim($result));
    }

    public function testWarningMessage()
    {
        $c = new class(...$this->args) extends Command {};
        $c->output = new TestOutput();

        ob_start();
        $c->warning("hello");
        $result = ob_get_clean();

        $this->assertEquals("<warn>hello</>", trim($result));
    }

    public function testErrorMessage()
    {
        $c = new class(...$this->args) extends Command {};
        $c->output = new TestOutput();

        ob_start();
        $c->error("hello");
        $result = ob_get_clean();

        $this->assertEquals("<error>hello</>", trim($result));
    }

    public function testLogMessage()
    {
        $c = new class(...$this->args) extends Command {};
        $c->output = new TestOutput();

        ob_start();
        $c->log("hello");
        $result = ob_get_clean();

        $this->assertEquals("<comment>hello</>", trim($result));
    }
}
