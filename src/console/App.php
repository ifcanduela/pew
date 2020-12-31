<?php declare(strict_types=1);

namespace pew\console;

use ifcanduela\abbrev\Abbrev;
use ReflectionClass;
use ReflectionMethod;
use ReflectionException;
use Stringy\Stringy as Str;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use phpDocumentor\Reflection\DocBlockFactory;

class App extends \pew\App
{
    /** @var CommandDefinition[] */
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
        $this->output->getFormatter()->setStyle("warn", new OutputFormatterStyle("black", "yellow"));
        $this->output->getFormatter()->setStyle("success", new OutputFormatterStyle("cyan", "default"));

        $this->initCommandList();
    }

    /**
     * Run a command.
     *
     * @return mixed
     */
    public function run()
    {
        $arguments = $this->getArguments();

        if (empty($arguments["command"])) {
            $this->output->writeln("<fg=cyan>Pew command runner</>");
            $this->output->writeln("The following commands are available:" . PHP_EOL);

            foreach ($this->availableCommands as $name => $commandInfo) {
                $this->output->writeln("<info>{$name}</info>");

                if ($commandInfo->description) {
                    $this->output->writeln("    " . $commandInfo->description);
                }
            }

            return null;
        }

        if (strpos($arguments["command"], ":") !== false) {
            [$commandName, $action] = explode(":", $arguments["command"]);
            $action = (string) Str::create($action)->camelize();
        } else {
            [$commandName, $action] = [$arguments["command"], "run"];
        }

        $commandInfo = $this->findCommand($commandName);

        if (!($commandInfo instanceof CommandDefinition)) {
            $this->commandMissing($commandName, $commandInfo ?? []);

            return null;
        }

        return $this->handleCommand($commandInfo, $arguments["arguments"], $action);
    }

    /**
     * Initialize the list of available commands.
     *
     * @return void
     * @throws ReflectionException
     * @throws ReflectionException
     */
    protected function initCommandList()
    {
        $appPath = $this->get("app_path");
        $appNamespace = $this->get("app_namespace");
        $commandsNamespace = $this->get("commands_namespace");
        $appCommandsNamespace = "{$appNamespace}{$commandsNamespace}\\";

        # framework-defined commands
        $commandFiles = glob(dirname(__DIR__) . "/commands/*Command.php");

        foreach ($commandFiles as $commandFile) {
            $this->addCommand($commandFile, "\\pew\\commands\\");
        }

        # app-defined commands
        $commandFiles = glob("{$appPath}/{$commandsNamespace}/*Command.php");

        foreach ($commandFiles as $commandFile) {
            $this->addCommand($commandFile, $appCommandsNamespace);
        }

        ksort($this->availableCommands);
    }

    /**
     * Register an available command.
     *
     * @param string $commandFilename
     * @param string $namespace
     * @return void
     */
    protected function addCommand(string $commandFilename, string $namespace)
    {
        # Full class name with namespace
        $className = pathinfo($commandFilename, PATHINFO_FILENAME);
        $fullClassName = "{$namespace}{$className}";

        try {
            $reflectionClass = new ReflectionClass($fullClassName);
        } catch (\ReflectionException $e) {
            $this->output->writeln("Error reading command class file: {$commandFilename} ({$namespace})");
        }

        # Figure out the base command name
        $defaultProperties = $reflectionClass->getDefaultProperties();
        $name = (string) $defaultProperties["name"] ?? Str::create($className)
            ->removeRight("Command")
            ->underscored()
            ->slugify();

        $description = $defaultProperties["description"] ?? "";

        # Add the command to the list
        $definition = new CommandDefinition($name, $fullClassName, $description);
        $this->availableCommands[$definition->name] = $definition;

        # Search for sub-commands

        # Get public methods
        $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);
        # Get a whitelist of valid command names
        $methodNames = $this->getCommandMethodNames($fullClassName);

        foreach ($methods as $method) {
            $methodName = $method->getName();

            # Check that the method is in the whitelist
            if (in_array($methodName, $methodNames, true)) {
                $commandName = $name . ":" . $methodName;
                $comment = $method->getDocComment();
                $description = "";

                if ($comment) {
                    $doc = DocBlockFactory::createInstance()->create($comment);
                    $description = $doc->getSummary();
                }

                # Add the command to the list
                $definition = new CommandDefinition($commandName, $fullClassName, $description);
                $this->availableCommands[$definition->name] = $definition;
            }
        }
    }

    /**
     * Build a list of available command method names for a class.
     *
     * @param string $className
     * @return string[]
     */
    protected function getCommandMethodNames(string $className): array
    {
        $parentMethods = get_class_methods(Command::class);
        $methods = get_class_methods($className);
        $result = [];

        foreach ($methods as $methodName) {
            if (substr($methodName, 0, 2) == "__") {
                continue;
            }

            if (in_array($methodName, ["run", "help"])) {
                continue;
            }

            if (in_array($methodName, $parentMethods)) {
                continue;
            }

            $result[] = $methodName;
        }

        return $result;
    }

    /**
     * Print a help message when no command was found.
     *
     * @param string $commandName
     * @param array $suggestions
     * @return void
     */
    protected function commandMissing(string $commandName, array $suggestions = [])
    {
        if (!$suggestions) {
            $this->output->writeln("Command <error>{$commandName}</error> not found");
            $this->output->writeln("Did you mean:");

            $suggestions = array_keys($this->availableCommands);
        } else {
            $this->output->writeln("Command <error>{$commandName}</error> is ambiguous");
            $this->output->writeln("Did you mean:");
        }

        foreach ($suggestions as $suggestion) {
            $this->output->writeln("    <info>{$suggestion}</info>");
        }
    }

    /**
     * Retrieve all arguments of a command call.
     *
     * Returns an array with `command` and `argumgents` keys
     *
     * @return array
     */
    public function getArguments(): array
    {
        $argv = $_SERVER["argv"];
        $scriptName = array_shift($argv);
        $command = count($argv) ? array_shift($argv) : null;
        $arguments = new CommandArguments($argv);

        return compact("command", "arguments");
    }

    /**
     * Find a command in the list of available commands.
     *
     * If a command is not found, a list of suggestions is returned.
     *
     * @param string $commandName
     * @return CommandDefinition|array
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
     * @param CommandDefinition $commandDefinition
     * @param CommandArguments $arguments
     * @param string $action
     * @return mixed
     */
    protected function handleCommand(CommandDefinition $commandDefinition, CommandArguments $arguments, string $action = "run")
    {
        $commandClassName = $commandDefinition->className;

        /** @var Command $command */
        $command = new $commandClassName(
            $this->input,
            $this->output
        );

        if (!is_callable([$command, $action])) {
            $this->output->writeln("Command <error>{$commandDefinition->name}:{$action}</error> not found");
            return false;
        }

        $injector = $this->get("injector");
        $this->set(CommandArguments::class, $arguments);

        if (method_exists($command, "init")) {
            $injector->callMethod($command, "init");
        }

        $result = $injector->callMethod($command, $action);

        if (method_exists($command, "finish")) {
            $injector->callMethod($command, "finish");
        }

        return $result;
    }
}
