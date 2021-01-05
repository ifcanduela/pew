<?php

namespace pew\tests\unit;

use pew\console\App;
use Symfony\Component\Console\Output\BufferedOutput;

class ConsoleAppTest extends \PHPUnit\Framework\TestCase
{
    public $app;

    public function setUp(): void
    {
        $this->app = new App(__DIR__ . "/../fixtures/", "console");
        $this->app->output = new BufferedOutput();
    }

    public function testAppBootstrap()
    {
        $commandNames = array_keys($this->app->availableCommands);

        $this->assertEquals("Console App Test", $this->app->get("app_title"));
        $this->assertEquals([
            "create",
            "create:command",
            "create:controller",
            "create:middleware",
            "create:model",
            "other",
            "routes",
            "test",
            "test:alternate",
        ], $commandNames);
    }

    public function testEmptyArgumentList()
    {
        $_SERVER["argv"] = ["run"];

        $this->app->run();
        $output = $this->app->output->fetch();

        $this->assertStringContainsString("create", $output);
        $this->assertStringContainsString("routes", $output);
        $this->assertStringContainsString("test", $output);
    }

    public function testRunCommand()
    {
        $_SERVER["argv"] = ["run", "test", "1", "2"];

        $args = $this->app->getArguments();
        $this->assertEquals("test", $args["command"]);
        $this->assertEquals(1, $args["arguments"]->at(0));
        $this->assertEquals(2, $args["arguments"]->at(1));

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

    public function testActionNotFound()
    {
        $_SERVER["argv"] = ["run", "missing"];

        $args = $this->app->getArguments();
        $this->assertEquals("missing", $args["command"]);

        $result = $this->app->run();

        $this->assertNull($result);
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
