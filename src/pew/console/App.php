<?php

namespace pew\console;

use ifcanduela\abbrev\Abbrev;

class App extends \pew\App
{
    public $availableCommands = [];

    /**
     * Run a command.
     *
     * @return mixed
     */
    public function run()
    {
        $injector = $this->container['injector'];

        $command_files = glob($this->container['app_path'] . '/commands/*Command.php');

        foreach ($command_files as $command_file) {
            $class_name = '\\app\\commands\\' . pathinfo($command_file, PATHINFO_FILENAME);
            $command = $injector->createInstance($class_name);

            $this->availableCommands[$command->name()] = $command;
        }

        $arguments = $this->getArguments();

        if (empty($arguments)) {
            foreach ($this->availableCommands as $command) {
                echo $command->name() . PHP_EOL;
                echo '    ' . $command->description() . PHP_EOL;
            }

            die();
        }

        $command = $this->findCommand($arguments['command']);

        if (!is_a($command, \pew\console\CommandInterface::class)) {
            $this->commandMissing($command);
        }

        $this->container['arguments'] = new CommandArguments($arguments['arguments']);

        return $injector->callMethod($command, 'run');
    }

    private function commandMissing($command)
    {
            echo "Command {$arguments['command']} not found" . PHP_EOL;
            echo "Did you mean:" . PHP_EOL;

            if ($command) {
                $suggestions = $command;
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
    private function getArguments()
    {
        $argv = $_SERVER['argv'];
        $script_name = $_SERVER['SCRIPT_NAME'];

        $script_name_pos = array_search($script_name, $argv, true);

        $arguments = array_slice($argv, $script_name_pos + 1);

        if ($arguments) {
            $command = array_shift($arguments);
            return compact('command', 'arguments');
        }

        return [];
    }

    /**
     * Find a command in the list of available commands.
     *
     * If a command is not found, a list of suggestions is returned.
     *
     * @param string $commandName
     * @return Command|array
     */
    private function findCommand(string $commandName)
    {
        $names = array_keys($this->availableCommands);
        $abbrev = new Abbrev($names);

        $match = $abbrev->match($commandName);

        if (!$match) {
            return  $abbrev->suggest($commandName);
        }

        return $this->availableCommands[$match];
    }
}
