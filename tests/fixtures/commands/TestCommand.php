<?php

namespace app\commands;

class TestCommand extends \pew\console\Command
{
    public $name = "test";

    public $description = "Test command";

    public function run()
    {
        echo "test command result";
    }

    public function alternate()
    {
        echo "alternate command result";
    }
}
