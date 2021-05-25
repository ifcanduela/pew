<?php

namespace app\commands;

use pew\console\CommandArguments;

class OtherCommand extends \pew\console\Command
{
    public function run(CommandArguments $arguments)
    {
        return "other command result";
    }
}
