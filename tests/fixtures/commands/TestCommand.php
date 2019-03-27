<?php

namespace app\commands;

class TestCommand extends \pew\console\Command
{
    public function name()
    {
        return "test";
    }

    public function description()
    {
        return "Test command";
    }

    public function run()
    {
        return "test command result";
    }

    public function alternate()
    {
        return "alternate command result";
    }
}
