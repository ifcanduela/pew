<?php

namespace pew\commands;

use pew\console\Command;
use pew\console\CommandArguments;

use Stringy\Stringy as S;

class CreateCommand extends Command
{
    public function name()
    {
        return "create";
    }

    public function description()
    {
        return "Generates app files.";
    }

    public function run(CommandArguments $arguments)
    {
        if ($arguments->has("type")) {
            $type = $arguments->get("type");

            return $this->{$type}($arguments);
        }

        echo $this->infoBox("Create application files");

        echo $this->messageBox(
            "  - create:command <ClassName>",
            "    Generates a console command. The suffix `Command` will be added if it's not present.",
            "  - create:controller <ClassName>",
            "    Generate an action class. The suffix `Controller` is optional.",
            "  - create:middleware <ClassName>",
            "    Generate a middleware controller with stubs for `before()` and `after()`.",
            "  - create:model <ClassName> [table_name]",
            "    Generate a database Model class. The table name can be inferred from the class name."
        );

        return false;
    }

    public function command(CommandArguments $arguments)
    {
        $arg = $arguments->at(0);

        if (!$arg) {
            echo $this->errorBox("Missing argument: ClassName");
            die;
        }

        $className = S::create($arg);

        $commandName = $className->removeRight("Command")->dasherize();
        $className = $className->ensureRight("Command");

        $fileContents = <<<PHP
<?php

namespace app\commands;

use pew\console\Command;
use pew\console\CommandArguments;

class {$className} extends Command
{
    public function name()
    {
        return "{$commandName}";
    }

    public function description()
    {
        return "";
    }

    public function run(CommandArguments \$args)
    {
        echo \$this->info("{$commandName}");
    }
}

PHP;

        $filename = root("app", "commands", "{$className}.php");

        $this->createFile($fileContents, $filename);
    }

    public function controller(CommandArguments $arguments)
    {
        $className = $arguments->at(0);

        if (!$className) {
            echo $this->errorBox("Missing argument: ClassName");
            die;
        }

        $fileContents = <<<PHP
<?php

namespace app\controllers;

use pew\Controller;

class {$className} extends Controller
{

}

PHP;

        $filename = root("app", "controllers", "{$className}.php");
        $this->createFile($fileContents, $filename);
    }

    public function middleware(CommandArguments $arguments)
    {
        $className = $arguments->at(0);

        $fileContents = <<<PHP
<?php

namespace app\middleware;

class OnlyAuthenticated extends \\request\\Middlewaree
{
    public function before()
    {

    }

    public function after()
    {

    }
}


PHP;

        $filename = root("app", "middleware", "{$className}.php");
        $this->createFile($fileContents, $filename);
    }

    public function model(CommandArguments $arguments)
    {
        $className = $arguments->at(0);

        if ($arguments->has(1)) {
            $tableName = $arguments->at(1);
        } else {
            $tableName = rtrim(preg_replace('~([^s])(_)~', '\1s_', S::create($className)->underscored()), "s") . "s";
        }

        $fileContents = <<<PHP
<?php

namespace app\models;

use pew\Model;

class {$className} extends Model
{
    public \$tableName = "{$tableName}";
}

PHP;

        $filename = root("app", "models", "{$className}.php");

        $this->createFile($fileContents, $filename);
    }

    public function createFile(string $content, string $filename)
    {
        if (!file_exists($filename)) {
            file_put_contents($filename, $content);
            echo $this->successBox("{$filename} created.");
        } else {
            echo $this->errorBox("{$filename} already exists.");
        }
    }
}
