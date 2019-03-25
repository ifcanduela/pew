<?php

namespace pew\console;

use ifcanduela\abbrev\Abbrev;

class App extends \pew\App
{
    /** @var string[] */
    public $availableCommands = [];

    /**
     * Run a command.
     *
     * @return mixed
     */
    public function run()
    {
        $this->initCommandList();
        $arguments = $this->getArguments();

        if (empty($arguments)) {
            foreach ($this->availableCommands as $command) {
                echo $command->name() . PHP_EOL;
                echo "    " . $command->description() . PHP_EOL;
            }

            die();
        }

        if (strpos($arguments["command"], ":") !== false) {
            list($commandName, $action) = explode(":", $arguments["command"]);
        } else {
            list($commandName, $action) = [$arguments["command"], "run"];
        }

        $command = $this->findCommand($commandName);

        if (!($command instanceof CommandInterface)) {
            $this->commandMissing($commandName);
        }

        $args = new CommandArguments($arguments["arguments"], $command->getDefaultArguments());
        $this->set(CommandArguments::class, $args);

        return $this->handleCommand($command, $action);
    }

    /**
     * Initialize the list of available commands.
     */
    protected function initCommandList()
    {
        $injector = $this->get("injector");
        $commandFiles = glob($this->container["app_path"] . "/commands/*Command.php");

        foreach ($commandFiles as $commandFile) {
            $className = "\\app\\commands\\" . pathinfo($commandFile, PATHINFO_FILENAME);
            /** @var Command $command */
            $command = $injector->createInstance($className);

            $this->availableCommands[$command->name()] = $command;
        }

        $commandFiles = glob(dirname(__DIR__) . "/commands/*Command.php");

        foreach ($commandFiles as $commandFile) {
            $className = "\\pew\\commands\\" . pathinfo($commandFile, PATHINFO_FILENAME);
            $command = $injector->createInstance($className);

            $this->availableCommands[$command->name()] = $command;
        }
    }

    protected function commandMissing(string $commandName)
    {
        echo "Command {$commandName} not found" . PHP_EOL;
        echo "Did you mean:" . PHP_EOL;

        if ($commandName) {
            $suggestions = $commandName;
        } else {
            $suggestions = array_keys($this->availableCommands);
        }

        foreach ($suggestions as $suggestion) {
            echo "    {$suggestion}" . PHP_EOL;
        }

        die();
    }

    /**
     * Retrieve all arguments of a command call.
     *
     * @return array
     */
    protected function getArguments()
    {
        $argv = $_SERVER["argv"];
        $scriptName = $_SERVER["SCRIPT_NAME"];
        $scriptNamePos = array_search($scriptName, $argv, true);
        $arguments = array_slice($argv, $scriptNamePos + 1);

        if ($arguments) {
            $command = array_shift($arguments);
            return compact("command", "arguments");
        }

        return [];
    }

    /**
     * Find a command in the list of available commands.
     *
     * If a command is not found, a list of suggestions is returned.
     *
     * @param string $commandName
     * @return CommandInterface|array
     */
    protected function findCommand(string $commandName)
    {
        $names = array_keys($this->availableCommands);
        $abbrev = new Abbrev($names);

        $match = $abbrev->match($commandName);

        if (!$match) {
            return  $abbrev->suggest($commandName);
        }

        return $this->availableCommands[$match];
    }

    /**
     * Call a method on a command instance.
     *
     * @param CommandInterface $command
     * @param string $action
     * @return mixed
     */
    protected function handleCommand(CommandInterface $command, string $action)
    {
        $injector = $this->get("injector");

        $injector->callMethod($command, "init");
        $result = $injector->callMethod($command, $action);
        $injector->callMethod($command, "finish");

        return $result;
    }
}
