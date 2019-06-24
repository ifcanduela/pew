<?php

namespace pew\console;

use ifcanduela\abbrev\Abbrev;
use Stringy\Stringy;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class App extends \pew\App
{
    /** @var string[] */
    public $availableCommands = [];

    /** @var ArgvInput */
    public $input;

    /** @var ConsoleOutput */
    public $output;

    public function __construct(string $appFolder, string $configFileName = "config")
    {
        parent::__construct($appFolder, $configFileName);
        $this->input = new ArgvInput();
        $this->output = new ConsoleOutput();

        $this->initCommandList();
    }

    /**
     * Run a command.
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function run()
    {
        $arguments = $this->getArguments();

        if (empty($arguments)) {
            foreach ($this->availableCommands as $name => $commandClass) {
                $r = new \ReflectionClass($commandClass);
                $props = $r->getDefaultProperties();

                $this->output->writeln("<info>$name</info>");

                if ($props["description"]) {
                    $this->output->writeln("    " . $props["description"]);
                }
            }

            return;
        }

        if (strpos($arguments["command"], ":") !== false) {
            [$commandName, $action] = explode(":", $arguments["command"]);
        } else {
            [$commandName, $action] = [$arguments["command"], "run"];
        }

        $commandClassName = $this->findCommand($commandName);

        if (!$commandClassName) {
            $this->commandMissing($commandName, $commandClassName);
            return;
        }

        return $this->handleCommand($commandClassName, $arguments, $action);
    }

    /**
     * Initialize the list of available commands.
     */
    protected function initCommandList()
    {
        $appNamespace = $this->get("app_namespace");
        $commandsNamespace = $this->get("commands_namespace");
        $appCommandsNamespace = "{$appNamespace}{$commandsNamespace}\\";

        # framework-defined commands
        $commandFiles = glob(dirname(__DIR__) . "/commands/*Command.php");

        foreach ($commandFiles as $commandFile) {
            $this->addCommand($commandFile, "\\pew\\commands\\");
        }

        # app-defined commands
        $commandFiles = glob($this->container["app_path"] . "/commands/*Command.php");

        foreach ($commandFiles as $commandFile) {
            $this->addCommand($commandFile, $appCommandsNamespace);
        }
    }

    protected function addCommand(string $commandFilename, $namespace)
    {
        $className = pathinfo($commandFilename, PATHINFO_FILENAME);
        $fullClassName = "{$namespace}{$className}";

        $r = new \ReflectionClass($fullClassName);
        $defaultProperties = $r->getDefaultProperties();
        $name = $defaultProperties["name"] ?? Stringy::create($className)->removeRight("Command")->slugify();

        $this->availableCommands[(string) $name] = $fullClassName;
    }

    protected function commandMissing(string $commandName, array $suggestions = [])
    {
        echo "Command {$commandName} not found" . PHP_EOL;
        echo "Did you mean:" . PHP_EOL;

        if (!$suggestions) {
            $suggestions = array_keys($this->availableCommands);
        }

        foreach ($suggestions as $suggestion) {
            echo "    {$suggestion}" . PHP_EOL;
        }

        return;
    }

    /**
     * Retrieve all arguments of a command call.
     *
     * @return array
     */
    public function getArguments()
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
     * @param string $commandClassName
     * @param array $arguments
     * @param string $action
     * @return mixed
     */
    protected function handleCommand(string $commandClassName, array $arguments, string $action = "run")
    {
        /** @var Command $command */
        $command = new $commandClassName(
            $this->input,
            $this->output,
            new FormatterHelper()
        );

        $injector = $this->get("injector");

        $args = new CommandArguments($arguments["arguments"], $command->getDefaultArguments());
        $this->set(CommandArguments::class, $args);

        $injector->callMethod($command, "init");
        $result = $injector->callMethod($command, $action);
        $injector->callMethod($command, "finish");

        return $result;
    }
}
