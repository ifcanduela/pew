<?php

namespace pew\commands;

use pew\console\Command;
use pew\console\CommandArguments;

use Stringy\StaticStringy as S;

class CreateCommand extends Command
{
    public function name()
    {
        return 'create';
    }

    public function description()
    {
        return 'Create app files.';
    }

    public function run(CommandArguments $arguments)
    {
        if ($arguments->type) {
            return $this->{$arguments->type}($arguments);
        }

        return false;
    }

    public function model(CommandArguments $arguments)
    {
        $className = $arguments->at(0);

        if ($arguments->has(1)) {
            $tableName = $arguments->at(1);
        } else {
            $tableName = rtrim(preg_replace('~([^s])(_)~', '\1s_', S::underscored($className)), 's') . 's';
        }

        $file_contents = <<<PHP
<?php

namespace app\models;

use pew\Model;

class {$className} extends Model
{
    public \$tableName = '{$tableName}';
}

PHP;

        $filename = root('app', 'models', "{$className}.php");

        $this->createFile($file_contents, $filename);
    }

    public function controller(CommandArguments $arguments)
    {
        $className = $arguments->at(0);

        $file_contents = <<<PHP
<?php

namespace app\controllers;

use pew\Controller;

class {$className} extends Controller
{

}

PHP;

        $filename = root('app', 'controllers', "{$className}.php");
		$this->createFile($file_contents, $filename);
    }

    public function command(CommandArguments $arguments)
    {
        $className = $arguments->at(0);
        $commandName = str_replace('_', '-', S::underscored($className));

        if (substr($className, -strlen('Command')) !== 'Command') {
            $className .= 'Command';
        }

        $file_contents = <<<PHP
<?php

namespace app\commands;

use pew\console\Command;
use pew\console\CommandArguments;

class {$className} extends Command
{
    public function name()
    {
        return '{$commandName}';
    }

    public function description()
    {
        return '';
    }

    public function run(CommandArguments \$args)
    {
        echo \$this->info('{$commandName}');
    }
}

PHP;

        $filename = root('app', 'commands', "{$className}.php");
        $this->createFile($file_contents, $filename);
    }

    public function createFile(string $content, string $filename)
    {
        if (!file_exists($filename)) {
            file_put_contents($filename, $content);
            echo $this->info("{$filename} created.");
        } else {
            echo $this->error("{$filename} already exists.");
        }
    }
}
