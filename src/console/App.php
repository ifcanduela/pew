<?php declare(strict_types=1);

namespace pew\console;

use ifcanduela\abbrev\Abbrev;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\ConsoleOutput;

use function pew\str;
use function pew\slug;

class App extends \pew\App
{
    /** @var CommandDefinition[] */
    public array $availableCommands = [];

    /** @var ArgvInput */
    public ArgvInput $input;

    /** @var Output */
    public Output $output;

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
            return $this->printHelp();
        }

        if (strpos($arguments["command"], ":") !== false) {
            [$commandName, $actionSlug] = explode(":", $arguments["command"]);
            $action = (string) str($actionSlug)->camel();
        } else {
            [$commandName, $action] = [$arguments["command"], ""];
        }

        $commandInfo = $this->findCommand($commandName, $action);

        if (!($commandInfo instanceof CommandDefinition)) {
            return $this->commandMissing($commandName, $actionSlug ?? null, $commandInfo ?? []);
        }

        return $this->handleCommand($commandInfo, $arguments["arguments"]);
    }

    /**
     * Print general command information.
     */
    protected function printHelp(): void
    {
        $this->output->writeln("<fg=cyan>Pew command runner</>");
        $this->output->writeln("The following commands are available:" . PHP_EOL);

        foreach ($this->availableCommands as $name => $commandInfo) {
            $this->printCommands($name, $commandInfo);
        }
    }

    /**
     * Print information about a command.
     *
     * @param string $commandName
     * @param array $commandInfo
     */
    protected function printCommands(string $commandName, array $commandInfo): void
    {
        $defaultCommandName = $commandInfo["default"];

        # print the default command first, if present
        if ($defaultCommandName) {
            $defaultCommand = $commandInfo["commands"][$defaultCommandName];
            $name = "<info>{$commandName}</info>";

            if ($defaultCommand->description) {
                $description = $defaultCommand->description;
                $name .= " -> {$description}";
            }
        } else {
            $name = "<comment>{$commandName}</comment>";
        }

        # base command name
        $this->output->writeln($name);

        # print any subcommands
        foreach ($commandInfo["commands"] as $subcommand => $commandDefinition) {
            # skip the default command
            if ($subcommand !== $defaultCommandName) {
                $name = "  <info>{$commandName}:{$subcommand}</info>";

                if ($commandDefinition->description) {
                    $description = $commandDefinition->description;
                    $name .= " -> {$description}";
                }

                $this->output->writeln($name);
            }
        }
    }

    /**
     * Initialize the list of available commands.
     *
     * @return void
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
        # full class name with namespace
        $className = pathinfo($commandFilename, PATHINFO_FILENAME);
        $fullClassName = "{$namespace}{$className}";

        try {
            $reflectionClass = new ReflectionClass($fullClassName);
        } catch (\ReflectionException $e) {
            $this->output->writeln("Error reading Command class file: {$commandFilename} ({$namespace})");
            return;
        }

        # figure out the base command name
        $defaultProperties = $reflectionClass->getDefaultProperties();
        $name = strlen($defaultProperties["name"])
            ? $defaultProperties["name"]
            : (string) slug($className)->beforeLast("-command");
        $defaultCommand = $defaultProperties["defaultCommand"];

        # get public methods
        $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

        $commandData = $this->getSubcommands($methods, $defaultCommand, $name, $fullClassName);

        # add all the commands to the list
        ksort($commandData["commands"]);
        $this->availableCommands[$name] = $commandData;
    }

    /**
     * Get command methods from a class.
     *
     * @param array $methods
     * @param string $defaultCommand
     * @param string $name
     * @param string $fullClassName
     * @return array
     */
    protected function getSubcommands(array $methods, string $defaultCommand, string $name, string $fullClassName): array
    {
        # get a whitelist of valid command names
        $methodNames = $this->getCommandMethodNames($fullClassName);

        $commandData = [
            "default" => false,
            "commands" => [],
        ];

        foreach ($methods as $method) {
            $methodName = $method->getName();
            $methodSlug = (string) slug($methodName);
            $isDefault = $defaultCommand === $methodName || $defaultCommand === $methodSlug;

            # check that the method is in the whitelist
            if (in_array($methodName, $methodNames, true)) {
                $commandName = $isDefault ? $name : "{$name}:{$methodSlug}";
                $comment = $method->getDocComment();
                $description = "";

                if ($comment) {
                    $doc = DocBlockFactory::createInstance()->create($comment);
                    $description = $doc->getSummary();
                }

                # create a command definition
                $definition = new CommandDefinition($commandName, $methodName, $fullClassName, $description);

                # flag the default command
                if ($isDefault) {
                    $commandData["default"] = $methodSlug;
                }

                # add the command
                $commandData["commands"][$methodSlug] = $definition;
            }
        }

        return $commandData;
    }

    /**
     * Build a list of available command method names for a class.
     *
     * @param string $className
     * @return string[]
     */
    protected function getCommandMethodNames(string $className): array
    {
        # blacklist of methods defined in the parent class
        $parentMethods = get_class_methods(Command::class);
        # whitelist of methods defined in the command class
        $methods = get_class_methods($className);
        $result = [];

        foreach ($methods as $methodName) {
            # exclude magic methods
            if (substr($methodName, 0, 2) == "__") {
                continue;
            }

            # exclude methods by name
            if (in_array($methodName, [])) {
                continue;
            }

            # exclude methods defined in the parent class
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
    protected function commandMissing(string $commandName, ?string $actionSlug, array $suggestions = [])
    {
        if ($actionSlug) {
            $commandName .= ":{$actionSlug}";
        }

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
     * @param string $subcommandName
     * @return CommandDefinition|array
     */
    protected function findCommand(string $commandName, string $subcommandName)
    {
        # Find main command by name
        $commandNames = array_keys($this->availableCommands);
        $abbrev = new Abbrev($commandNames);
        $match = $abbrev->match($commandName);

        if (!$match) {
            return $abbrev->suggest($commandName);
        }

        $command = $this->availableCommands[$match];
        $subcommandNames = array_keys($command["commands"]);

        if ($subcommandName) {
            $abbrev = new Abbrev($subcommandNames);
            $match = $abbrev->match($subcommandName);

            if ($match) {
                return $command["commands"][$match];
            }
        } elseif ($command["default"]) {
            return $command["commands"][$command["default"]];
        }

        return $abbrev->suggest($subcommandName);
    }

    /**
     * Call a method on a command instance.
     *
     * @param CommandDefinition $commandDefinition
     * @param CommandArguments $arguments
     * @return mixed
     */
    protected function handleCommand(CommandDefinition $commandDefinition, CommandArguments $arguments)
    {
        $commandClassName = $commandDefinition->className;

        /** @var Command $command */
        $command = new $commandClassName(
            $this->input,
            $this->output
        );

        if (is_callable([$command, $commandDefinition->method])) {
            $injector = $this->get("injector");
            $this->set(CommandArguments::class, $arguments);

            return $injector->callMethod($command, $commandDefinition->method);
        }

        $actionSlug = (string) slug($commandDefinition->method);
        $this->output->writeln("Command <error>{$commandDefinition->name}:{$actionSlug}</error> not found");

        return false;
    }
}
