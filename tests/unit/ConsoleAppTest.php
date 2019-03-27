<?php

use pew\console\App;

class ConsoleAppTest extends \PHPUnit\Framework\TestCase
{
    public function testAppBootstrap()
    {
        $app = new App(__DIR__ . "/../fixtures/", "console");

        $commandNames = array_keys($app->availableCommands);

        $this->assertEquals("Console App Test", $app->get("app_title"));
        $this->assertEquals(["test", "create"], $commandNames);
    }

    public function testEmptyArgumentList()
    {
        $app = new App(__DIR__ . "/../fixtures/", "console");

        $_SERVER["argv"] = ["run"];

        ob_start();
        $app->run();
        $output = ob_get_clean();

        $expected =
"test" . PHP_EOL .
"    Test command" . PHP_EOL .
"create" . PHP_EOL .
"    Generates app files." . PHP_EOL;

        $this->assertEquals($expected, $output);
    }

    public function testRunCommand()
    {
        $app = new App(__DIR__ . "/../fixtures/", "console");

        $_SERVER["argv"] = ["run", "test", "1", "2"];

        $args = $app->getArguments();
        $this->assertEquals("test", $args["command"]);
        $this->assertEquals([1, 2], $args["arguments"]);

        $result = $app->run();

        $this->assertEquals("test command result", $result);
    }

    public function testRunCommandAction()
    {
        $app = new App(__DIR__ . "/../fixtures/", "console");

        $_SERVER["argv"] = ["run", "test:alternate"];

        $args = $app->getArguments();
        $this->assertEquals("test:alternate", $args["command"]);

        $result = $app->run();

        $this->assertEquals("alternate command result", $result);
    }

    public function testShowCommandSuggestions()
    {
        $app = new App(__DIR__ . "/../fixtures/", "console");

        $_SERVER["argv"] = ["run", "not-found"];

        $args = $app->getArguments();
        $this->assertEquals("not-found", $args["command"]);

        ob_start();
        $result = $app->run();
        $output = ob_get_clean();

        $expected =
"Command not-found not found" . PHP_EOL .
"Did you mean:" . PHP_EOL .
"    test" . PHP_EOL .
"    create" . PHP_EOL;

        $this->assertEquals($expected, $output);
    }
}
