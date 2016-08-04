<?php

namespace pew\console;

class App extends \pew\App
{
    public $availableCommands = [];

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
        $this->container['arguments'] = new CommandArguments($arguments['arguments']);

        if (!$command) {
            throw new \InvalidArgumentException("Command not found: {$arguments['command']}");
        }

        $result = $injector->callMethod($command, 'run');
    }

    public function getArguments(): array
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

    public function findCommand($commandName)
    {
        return $this->availableCommands[$commandName] ?? false;
    }
}
