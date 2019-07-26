<?php

namespace pew\tests\unit;

use pew\console\App;
use Symfony\Component\Console\Output\BufferedOutput;

class ConsoleAppTest extends \PHPUnit\Framework\TestCase
{
    public $app;

    public function setUp()
    {
        $this->app = new App(__DIR__ . "/../fixtures/", "console");
        $this->app->output = new BufferedOutput();
    }

    public function testAppBootstrap()
    {
        $commandNames = array_keys($this->app->availableCommands);

        $this->assertEquals("Console App Test", $this->app->get("app_title"));
        $this->assertEquals(["create", "test"], $commandNames);
    }

    public function testEmptyArgumentList()
    {
        $_SERVER["argv"] = ["run"];

        $this->app->run();

        $expected =
            "create" . PHP_EOL .
            "    Generates app files." . PHP_EOL .
            "test" . PHP_EOL .
            "    Test command" . PHP_EOL;

        $output = $this->app->output->fetch();

        $this->assertStringContainsString("create", $output);
        $this->assertStringContainsString("test", $output);
    }

    public function testRunCommand()
    {
        $_SERVER["argv"] = ["run", "test", "1", "2"];

        $args = $this->app->getArguments();
        $this->assertEquals("test", $args["command"]);
        $this->assertEquals([1, 2], $args["arguments"]);

        $result = $this->app->run();

        $this->assertEquals("test command result", $result);
    }

    public function testRunCommandAction()
    {
        $_SERVER["argv"] = ["run", "test:alternate"];

        $args = $this->app->getArguments();
        $this->assertEquals("test:alternate", $args["command"]);

        $result = $this->app->run();

        $this->assertEquals("alternate command result", $result);
    }

    public function testShowCommandSuggestions()
    {
        $_SERVER["argv"] = ["run", "not-found"];

        $args = $this->app->getArguments();
        $this->assertEquals("not-found", $args["command"]);

        $result = $this->app->run();
        $output = $this->app->output->fetch();

        $this->assertStringContainsString("create", $output);
        $this->assertStringContainsString("test", $output);
    }
}
