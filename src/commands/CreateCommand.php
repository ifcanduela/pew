<?php

declare(strict_types=1);

namespace pew\commands;

use pew\console\Command;
use pew\console\CommandArguments;
use pew\model\TableManager;

use function pew\str;
use function pew\slug;
use function pew\root;

class CreateCommand extends Command
{
    /** @var string */
    public string $name = "create";

    /** @var string */
    public string $description = "Generates app files.";

    /**
     * Create files for application components.
     *
     * @param CommandArguments $arguments
     * @return void
     */
    public function run(CommandArguments $arguments): void
    {
        if ($arguments->has("type")) {
            $type = $arguments->get("type");

            $this->{$type}($arguments);
        } else {
            $this->success("Create application files");

            $this->info("\ncreate:command <ClassName>");
            $this->message("    Create a console command. The suffix `Command` will be added if it's not present.</>");
            $this->info("\ncreate:controller <ClassName>");
            $this->message("    Create an action class. The suffix `Controller` is optional.");
            $this->info("\ncreate:middleware <ClassName>");
            $this->message("    Create a middleware controller with stubs for `before()` and `after()`.");
            $this->info("\ncreate:model <ClassName> [table_name]");
            $this->message("    Create a database Model class. The table name can be inferred from the class name.");
        }
    }

    /**
     * Create a command file.
     *
     * @param CommandArguments $arguments
     * @return void
     */
    public function command(CommandArguments $arguments): void
    {
        $arg = $arguments->at(0);
        $description = $arguments->get("description", "");

        if (!$arg) {
            $this->error("Missing argument: ClassName");
            die;
        }

        $className = str($arg);

        $className = $className->ensureEnd("Command");
        $commandName = slug($className->beforeLast("Command"));

        $fileContents = <<<PHP
<?php

namespace app\\commands;

use pew\\console\\Command;
use pew\\console\\CommandArguments;

class $className extends Command
{
    public \$name = "$commandName";

    public \$description = "$description";

    public function run(CommandArguments \$args)
    {
        \$this->info("$commandName");
    }
}

PHP;

        $filename = root("app", "commands", "$className.php");

        $this->createFile($fileContents, $filename);
    }

    /**
     * Create a controller file.
     *
     * @param CommandArguments $arguments
     * @return void
     */
    public function controller(CommandArguments $arguments): void
    {
        $className = $arguments->at(0);

        if (!$className) {
            $this->error("Missing argument: ClassName");
            die;
        }

        $slug = slug($className)->beforeLast("-controller");

        $fileContents = <<<PHP
<?php

namespace app\\controllers;

use pew\\Controller;

class $className extends Controller
{
    public function index()
    {
        return \$this->render("$slug/index");
    }
}

PHP;

        $filename = root("app", "controllers", "$className.php");
        $this->createFile($fileContents, $filename);
    }

    /**
     * Create a middleware file.
     *
     * @param CommandArguments $arguments
     * @return void
     */
    public function middleware(CommandArguments $arguments): void
    {
        $className = $arguments->at(0);

        $fileContents = <<<PHP
<?php

namespace app\\middleware;

use pew\\request\\Middleware;

class $className extends Middleware
{
    public function before()
    {

    }

    public function after()
    {

    }
}

PHP;

        $filename = root("app", "middleware", "$className.php");
        $this->createFile($fileContents, $filename);
    }

    /**
     * Create a model file.
     *
     * @param CommandArguments $arguments
     * @return void
     */
    public function model(CommandArguments $arguments): void
    {
        $className = $arguments->at(0);

        if ($arguments->has(1)) {
            $tableName = $arguments->at(1);
        } else {
            $tableName = TableManager::guessTableName($className);
        }

        $fileContents = <<<PHP
<?php

namespace app\\models;

use pew\\Model;

class $className extends Model
{
    public \$tableName = "$tableName";
}

PHP;

        $filename = root("app", "models", "$className.php");

        $this->createFile($fileContents, $filename);
    }

    /**
     * Create an app file.
     *
     * @param string $content
     * @param string $filename
     * @return void
     */
    protected function createFile(string $content, string $filename): void
    {
        if (!file_exists($filename)) {
            file_put_contents($filename, $content);
            $this->success("$filename created.");
        } else {
            $this->error("$filename already exists.");
        }
    }
}
