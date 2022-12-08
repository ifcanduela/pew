<?php

namespace app\commands;

class TestCommand extends \pew\console\Command
{
    public string $name = "test";

    public string $description = "Test command";

    public function run()
    {
        echo "test command result";
    }

    public function alternate()
    {
        echo "alternate command result";
    }
}
