<?php

namespace app\commands;

class TestCommand extends \pew\console\Command
{
    public $name = "test";

    public $description = "Test command";

    public function run()
    {
        return "test command result";
    }

    public function alternate()
    {
        return "alternate command result";
    }
}
